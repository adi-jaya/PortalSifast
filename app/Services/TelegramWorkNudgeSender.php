<?php

namespace App\Services;

use App\Models\User;
use App\Support\TelegramBotConfig;
use App\Support\TelegramWorkNudgeConversation;
use App\Support\TelegramWorkNudgeCopy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TelegramWorkNudgeSender
{
    /**
     * Kirim siaran grup + DM ke staff terhubung; set state awaiting_work.
     *
     * @return array{group: bool, dms: int, skipped: int}
     */
    public function send(): array
    {
        $token = TelegramBotConfig::token();
        if ($token === null) {
            Log::warning('Work nudge dilewati: token Telegram kosong.');

            return ['group' => false, 'dms' => 0, 'skipped' => 0];
        }

        $depId = (string) config('services.telegram-bot-api.work_nudge_dep', 'IT');
        $groupSent = $this->sendGroup($token);
        $dms = 0;
        $skipped = 0;

        $staff = User::query()
            ->where('role', 'staff')
            ->when($depId !== '', fn ($q) => $q->where('dep_id', $depId))
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->get(['id', 'name', 'telegram_chat_id']);

        foreach ($staff as $user) {
            $chatId = (string) $user->telegram_chat_id;
            $ok = $this->sendMessage($token, $chatId, TelegramWorkNudgeCopy::dmNudge($user->name));
            if ($ok) {
                TelegramWorkNudgeConversation::startAwaitingWork($chatId, $user->id);
                $dms++;
            } else {
                $skipped++;
            }
        }

        return ['group' => $groupSent, 'dms' => $dms, 'skipped' => $skipped];
    }

    private function sendGroup(string $token): bool
    {
        $chatId = config('services.telegram-bot-api.tickets_group_chat_id');
        if (! is_string($chatId) || $chatId === '') {
            return false;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => TelegramWorkNudgeCopy::groupBroadcast(),
        ];

        $threadId = TelegramBotConfig::ticketsGroupThreadId();
        if ($threadId !== null) {
            $payload['message_thread_id'] = $threadId;
        }

        return $this->postSendMessage($token, $payload);
    }

    private function sendMessage(string $token, string $chatId, string $text): bool
    {
        return $this->postSendMessage($token, [
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postSendMessage(string $token, array $payload): bool
    {
        $url = rtrim((string) config('services.telegram-bot-api.base_uri', 'https://api.telegram.org'), '/')."/bot{$token}/sendMessage";

        try {
            $response = Http::asForm()->timeout(15)->post($url, $payload);
            $okFlag = $response->json('ok');
            if (! $response->successful() || $okFlag === false) {
                Log::warning('Work nudge Telegram gagal', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'chat_id' => $payload['chat_id'] ?? null,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Work nudge Telegram exception', ['message' => $e->getMessage()]);

            return false;
        }
    }
}
