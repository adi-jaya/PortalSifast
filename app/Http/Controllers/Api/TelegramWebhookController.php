<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelegramTicketCreator;
use App\Support\TelegramBotConfig;
use App\Support\TelegramWorkNudgeConversation;
use App\Support\TelegramWorkNudgeCopy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class TelegramWebhookController extends Controller
{
    private const LINK_PREFIX = 'link_';

    /**
     * Handle incoming Telegram bot webhook (POST from Telegram servers).
     * - /start link_XXX: hubungkan akun
     * - /tiket|/ticket: buat tiket
     * - callback_query nudge:*: wizard Jarvis
     * - state wizard DM: catatan → optional tiket
     * - pesan teks lain (tanpa state): WorkNote
     */
    public function __invoke(Request $request): Response
    {
        $payload = $request->all();
        $token = TelegramBotConfig::token();
        if ($token === null || $token === '') {
            Log::warning('Telegram webhook dilewati: token bot kosong (config/.env).');

            return response('', 204);
        }

        if (isset($payload['callback_query']) && is_array($payload['callback_query'])) {
            $this->handleCallbackQuery($payload['callback_query'], $token);

            return response('', 204);
        }

        $message = $payload['message'] ?? null;
        if (! is_array($message)) {
            return response('', 204);
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = trim((string) ($message['text'] ?? ''));
        if ($chatId === '' || $text === '') {
            return response('', 204);
        }

        if (str_starts_with($text, '/start')) {
            $this->handleStart($chatId, $text, $token);

            return response('', 204);
        }

        if (preg_match('/^\/batal\b/i', $text) === 1) {
            TelegramWorkNudgeConversation::clear($chatId);
            $this->sendTelegram($token, $chatId, TelegramWorkNudgeCopy::cancelled(), false);

            return response('', 204);
        }

        if (preg_match('/^\/(tiket|ticket)\b/i', $text) === 1) {
            TelegramWorkNudgeConversation::clear($chatId);
            $this->handleTicketCommand($chatId, $text, $token);

            return response('', 204);
        }

        $state = TelegramWorkNudgeConversation::get($chatId);
        if ($state !== null) {
            $this->handleNudgeWizardMessage($chatId, $text, $token, $state);

            return response('', 204);
        }

        $this->handleMessage($chatId, $text, $token);

        return response('', 204);
    }

    /**
     * @param  array<string, mixed>  $callback
     */
    private function handleCallbackQuery(array $callback, string $token): void
    {
        $data = (string) ($callback['data'] ?? '');
        $chatId = (string) ($callback['message']['chat']['id'] ?? '');
        $callbackId = (string) ($callback['id'] ?? '');

        if ($callbackId !== '') {
            $this->answerCallbackQuery($token, $callbackId);
        }

        if ($chatId === '' || ! str_starts_with($data, 'nudge:')) {
            return;
        }

        $state = TelegramWorkNudgeConversation::get($chatId);
        if ($state === null || ($state['step'] ?? null) !== TelegramWorkNudgeConversation::STEP_AWAITING_TICKET_CHOICE) {
            $this->sendTelegram($token, $chatId, 'Sesi Jarvis sudah habis/berubah. Tunggu nudge berikutnya atau ketik kerjaanmu biasa.', false);

            return;
        }

        if ($data === 'nudge:ticket_yes') {
            $this->beginRequesterStep($chatId, $token, $state);

            return;
        }

        if ($data === 'nudge:ticket_no') {
            TelegramWorkNudgeConversation::clear($chatId);
            $this->sendTelegram($token, $chatId, TelegramWorkNudgeCopy::ticketSkipped(), false);
        }
    }

    /**
     * @param  array{step: string, user_id: int, work_text?: string, note_id?: int}  $state
     */
    private function handleNudgeWizardMessage(string $chatId, string $text, string $token, array $state): void
    {
        $user = $this->findUserByTelegramChatId($chatId);
        if (! $user) {
            TelegramWorkNudgeConversation::clear($chatId);
            $this->sendTelegram($token, $chatId, TelegramWorkNudgeCopy::needLinkedAccount(), false);

            return;
        }

        $step = $state['step'] ?? '';

        if ($step === TelegramWorkNudgeConversation::STEP_AWAITING_WORK) {
            $note = $user->workNotes()->create([
                'title' => Str::limit(str_replace(["\r", "\n"], ' ', $text), 255, '...'),
                'icon' => '🤖',
                'content' => [
                    ['id' => uniqid('tg', true), 'type' => 'text', 'content' => $text],
                ],
            ]);

            TelegramWorkNudgeConversation::put($chatId, [
                'step' => TelegramWorkNudgeConversation::STEP_AWAITING_TICKET_CHOICE,
                'user_id' => $user->id,
                'work_text' => $text,
                'note_id' => $note->id,
            ]);

            $this->sendTelegram(
                $token,
                $chatId,
                TelegramWorkNudgeCopy::workSavedAskTicket(),
                false,
                TelegramWorkNudgeCopy::ticketChoiceKeyboard()
            );

            return;
        }

        if ($step === TelegramWorkNudgeConversation::STEP_AWAITING_TICKET_CHOICE) {
            if ($this->isYes($text)) {
                $this->beginRequesterStep($chatId, $token, $state);

                return;
            }

            if ($this->isNo($text)) {
                TelegramWorkNudgeConversation::clear($chatId);
                $this->sendTelegram($token, $chatId, TelegramWorkNudgeCopy::ticketSkipped(), false);

                return;
            }

            $this->sendTelegram(
                $token,
                $chatId,
                "Balas Ya / Tidak, atau pencet tombolnya ya.\nKetik /batal untuk batal.",
                false,
                TelegramWorkNudgeCopy::ticketChoiceKeyboard()
            );

            return;
        }

        if ($step === TelegramWorkNudgeConversation::STEP_AWAITING_REQUESTER) {
            $workText = trim((string) ($state['work_text'] ?? ''));
            if ($workText === '') {
                TelegramWorkNudgeConversation::clear($chatId);
                $this->sendTelegram($token, $chatId, 'Sesi hilang. Ceritain kerjaanmu lagi nanti ya.', false);

                return;
            }

            $requestedBy = $this->resolveRequestedBy($text, $user);
            $title = Str::limit(str_replace(["\r", "\n"], ' ', $workText), 255, '...');

            try {
                $ticket = app(TelegramTicketCreator::class)->create($user, $title, $workText, $requestedBy);
            } catch (RuntimeException $e) {
                $this->sendTelegram($token, $chatId, $e->getMessage()."\n\nCoba lagi, atau ketik /batal.", false);

                return;
            } catch (\Throwable $e) {
                Log::error('Telegram Jarvis tiket gagal', ['exception' => $e->getMessage()]);
                $this->sendTelegram($token, $chatId, 'Gagal menyimpan tiket. Coba lagi atau buat lewat web.', false);

                return;
            }

            TelegramWorkNudgeConversation::clear($chatId);
            $ticketUrl = route('tickets.show', $ticket);
            $this->sendTelegram(
                $token,
                $chatId,
                "✅ Tiket jadi: {$ticket->ticket_number}\n{$ticketUrl}\n\nJarvis sudah bantu masukkan. Finalisasi pemohon di web kalau perlu. 💪",
                false
            );
        }
    }

    /**
     * @param  array{step: string, user_id: int, work_text?: string, note_id?: int}  $state
     */
    private function beginRequesterStep(string $chatId, string $token, array $state): void
    {
        $next = [
            'step' => TelegramWorkNudgeConversation::STEP_AWAITING_REQUESTER,
            'user_id' => (int) $state['user_id'],
            'work_text' => (string) ($state['work_text'] ?? ''),
        ];
        if (isset($state['note_id'])) {
            $next['note_id'] = (int) $state['note_id'];
        }

        TelegramWorkNudgeConversation::put($chatId, $next);
        $this->sendTelegram($token, $chatId, TelegramWorkNudgeCopy::askRequester(), false);
    }

    private function resolveRequestedBy(string $text, User $user): string
    {
        $normalized = mb_strtolower(trim($text));
        if (in_array($normalized, ['sendiri', 'saya', 'gue', 'aku', 'internal'], true)) {
            return $user->name.' - '.($user->dep_id ?: 'IT').' (internal)';
        }

        if (preg_match('/^diminta\s+oleh\s*[:：]\s*(.+)$/iu', trim($text), $m) === 1) {
            return trim($m[1]);
        }

        return trim($text);
    }

    private function isYes(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        return in_array($t, ['ya', 'y', 'yes', 'iya', 'yup', 'ok', 'oke', 'buat', 'buat tiket'], true);
    }

    private function isNo(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        return in_array($t, ['tidak', 'tdk', 'no', 'n', 'ga', 'gak', 'ngga', 'engga', 'skip', 'cukup'], true);
    }

    private function handleStart(string $chatId, string $text, string $token): void
    {
        $parts = preg_split('/\s+/', $text, 2);
        $param = isset($parts[1]) ? trim($parts[1]) : '';

        if (str_starts_with($param, self::LINK_PREFIX)) {
            $linkToken = substr($param, strlen(self::LINK_PREFIX));
            $userId = Cache::pull('telegram_link:'.$linkToken);
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->update(['telegram_chat_id' => $chatId]);
                    $this->sendTelegram($token, $chatId, "✅ *Akun terhubung!*\n\nAnda akan menerima notifikasi tiket baru di sini.\n\nKirim pesan apa saja untuk menyimpan sebagai *Catatan Kerja*.\nAtau `/tiket Judul` + baris `Diminta oleh:` untuk buat tiket.\nNudge Jarvis juga akan DM kamu tiap beberapa jam.", true);

                    return;
                }
            }
            $this->sendTelegram($token, $chatId, 'Link kedaluwarsa atau tidak valid. Buka lagi *Pengaturan → Telegram* di portal dan klik Hubungkan.', true);

            return;
        }

        $this->sendTelegram($token, $chatId, "Halo! 👋\n\n• *Hubungkan akun:* Buka *Pengaturan → Telegram* di portal, lalu klik *Hubungkan*.\n• *Catatan kerja:* kirim pesan teks ke bot ini.\n• *Tiket cepat:* `/tiket Judul` lalu baris `Diminta oleh: Nama - Unit`.\n• *Jarvis:* balas nudge DM — aku bantu catat & opsional buatkan tiket.", true);
    }

    private function handleTicketCommand(string $chatId, string $text, string $token): void
    {
        $user = $this->findUserByTelegramChatId($chatId);
        if (! $user) {
            $this->sendTelegram($token, $chatId, 'Belum terhubung. Kirim /start dan ikuti petunjuk, atau hubungkan akun lewat Pengaturan → Telegram di portal.', false);

            return;
        }

        ['title' => $title, 'description' => $description, 'requestedBy' => $requestedBy] = $this->parseTicketCommand($text);
        if ($title === '') {
            $this->sendTelegram(
                $token,
                $chatId,
                "Format pembuatan tiket:\n/tiket Judul masalah\nDeskripsi (opsional)\n\nContoh:\n/tiket Printer IGD error\nDiminta oleh: Budi - IGD\nKertas macet terus.",
                false
            );

            return;
        }

        if ($requestedBy === '') {
            $this->sendTelegram(
                $token,
                $chatId,
                "Mohon lengkapi baris wajib:\nDiminta oleh: Nama - Unit\n\nContoh:\n/tiket Printer IGD error\nDiminta oleh: Budi - IGD\nKertas macet terus.",
                false
            );

            return;
        }

        try {
            $ticket = app(TelegramTicketCreator::class)->create($user, $title, $description, $requestedBy);
        } catch (RuntimeException $e) {
            $this->sendTelegram($token, $chatId, $e->getMessage(), false);

            return;
        } catch (\Throwable $e) {
            Log::error('Telegram /tiket: gagal menyimpan tiket', ['exception' => $e->getMessage()]);
            $this->sendTelegram(
                $token,
                $chatId,
                'Gagal menyimpan tiket ke portal. Coba lagi atau buat tiket lewat web. Jika terus berulang, hubungi admin.',
                false
            );

            return;
        }

        $ticketUrl = route('tickets.show', $ticket);
        $this->sendTelegram(
            $token,
            $chatId,
            "✅ Berhasil. Nomor tiket: {$ticket->ticket_number}\n\nBuka di portal (menu Tiket):\n{$ticketUrl}\n\nPemohon di sistem sementara = akun Anda. Ubah pemohon di Edit tiket bila perlu.",
            false
        );
    }

    private function handleMessage(string $chatId, string $text, string $token): void
    {
        $user = $this->findUserByTelegramChatId($chatId);
        if (! $user) {
            $this->sendTelegram($token, $chatId, 'Belum terhubung. Kirim /start dan ikuti petunjuk, atau hubungkan akun lewat Pengaturan → Telegram di portal.', false);

            return;
        }

        $title = str_replace(["\r", "\n"], ' ', $text);
        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 252).'...';
        }
        if ($title === '') {
            $title = 'Catatan dari Telegram';
        }

        $blocks = [
            ['id' => uniqid('tg', true), 'type' => 'text', 'content' => $text],
        ];

        $note = $user->workNotes()->create([
            'title' => $title,
            'icon' => '📱',
            'content' => $blocks,
        ]);

        $url = route('catatan.index').'?note='.$note->id;
        $this->sendTelegram($token, $chatId, "✅ Catatan tersimpan.\n\n[Buka di Portal]({$url})");
    }

    /**
     * @return array{title: string, description: string, requestedBy: string}
     */
    private function parseTicketCommand(string $text): array
    {
        $raw = (string) preg_replace('/^\/(tiket|ticket)\b/i', '', $text);
        $raw = trim($raw);
        if ($raw === '') {
            return ['title' => '', 'description' => '', 'requestedBy' => ''];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $title = trim((string) array_shift($lines));
        $descriptionLines = [];
        $requestedBy = '';
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($requestedBy === '' && preg_match('/^diminta\s+oleh\s*[:：]\s*(.+)$/iu', $trimmedLine, $matches) === 1) {
                $requestedBy = trim((string) $matches[1]);

                continue;
            }

            $descriptionLines[] = $line;
        }
        $description = trim(implode("\n", $descriptionLines));

        return [
            'title' => Str::limit($title, 255, '...'),
            'description' => $description,
            'requestedBy' => Str::limit($requestedBy, 255, '...'),
        ];
    }

    private function findUserByTelegramChatId(string $chatId): ?User
    {
        return User::query()->where('telegram_chat_id', $chatId)->first();
    }

    /**
     * @param  array<string, mixed>|null  $replyMarkup
     */
    private function sendTelegram(string $token, string $chatId, string $text, bool $useMarkdown = true, ?array $replyMarkup = null): void
    {
        $url = rtrim((string) config('services.telegram-bot-api.base_uri', 'https://api.telegram.org'), '/')."/bot{$token}/sendMessage";
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        if ($useMarkdown) {
            $payload['parse_mode'] = 'Markdown';
        }
        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup, JSON_THROW_ON_ERROR);
        }

        try {
            Http::asForm()->timeout(15)->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('Telegram sendMessage gagal', ['message' => $e->getMessage()]);
        }
    }

    private function answerCallbackQuery(string $token, string $callbackId): void
    {
        $url = rtrim((string) config('services.telegram-bot-api.base_uri', 'https://api.telegram.org'), '/')."/bot{$token}/answerCallbackQuery";

        try {
            Http::asForm()->timeout(10)->post($url, [
                'callback_query_id' => $callbackId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram answerCallbackQuery gagal', ['message' => $e->getMessage()]);
        }
    }
}
