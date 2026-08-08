<?php

namespace App\Support;

final class TelegramWorkNudgeCopy
{
    public static function groupBroadcast(): string
    {
        return "🤖 Jarvis PortalSifast di sini.\n"
            ."Halo gais — sekarang lagi ngerjakan apa nih?\n"
            ."Ada yang bisa aku bantu masukkan ke portal?\n\n"
            ."👉 Balas DM aku (bukan di grup ini) biar aku catat / buatkan tiket.\n"
            .'Kalau males buka web, cukup chat aku. Semudah itu.';
    }

    public static function dmNudge(string $name): string
    {
        $first = trim(explode(' ', $name)[0] ?: $name);

        return "🤖 Halo {$first}. Jarvis di sini.\n"
            ."Lagi ngerjakan apa nih? Ceritain singkat aja.\n"
            ."Aku catat ke portal — kalau perlu tiket, tinggal bilang Ya.\n\n"
            .'Ketik /batal kalau mau batalin mode ini.';
    }

    public static function workSavedAskTicket(): string
    {
        return "✅ Catatan masuk.\n"
            ."Mau aku buatkan tiket di PortalSifast juga?\n\n"
            ."Balas Ya / Tidak — atau pencet tombol di bawah.\n"
            .'(Jarvis mode: seminimal mungkin ngetik.)';
    }

    public static function askRequester(): string
    {
        return "Siapa pemohonnya?\n"
            ."Ketik nama / NIK / email — aku cari di portal, terus pencet tombolnya.\n\n"
            ."Atau ketik sendiri kalau pemohonnya kamu.\n"
            .'Ketik /batal untuk batal.';
    }

    public static function requesterNotFound(string $query): string
    {
        return "Tidak ketemu pemohon untuk \"{$query}\".\n"
            .'Coba nama lain / NIK, ketik sendiri, atau /batal.';
    }

    public static function requesterPickPrompt(int $count): string
    {
        return "Ketemu {$count} orang. Pencet yang benar — atau ketik kata kunci lain / sendiri.";
    }

    /**
     * @param  list<array{id: int, label: string}>  $candidates
     * @return array{inline_keyboard: list<list<array{text: string, callback_data: string}>>}
     */
    public static function requesterChoiceKeyboard(array $candidates): array
    {
        $rows = [];
        foreach ($candidates as $candidate) {
            $rows[] = [[
                'text' => $candidate['label'],
                'callback_data' => 'nudge:req:'.$candidate['id'],
            ]];
        }
        $rows[] = [[
            'text' => '🔎 Cari lagi',
            'callback_data' => 'nudge:req_search_again',
        ]];

        return ['inline_keyboard' => $rows];
    }

    public static function ticketSkipped(): string
    {
        return 'Sip. Cukup catatan dulu. Kalau nanti perlu tiket, ketik /tiket atau tunggu nudge berikutnya. 💪';
    }

    public static function cancelled(): string
    {
        return 'Mode Jarvis dibatalin. Santai — aku standby lagi nanti.';
    }

    public static function needLinkedAccount(): string
    {
        return 'Akun Telegram belum terhubung. Buka Pengaturan → Telegram di portal dulu, biar aku bisa bantu.';
    }

    /**
     * @return array{inline_keyboard: list<list<array{text: string, callback_data: string}>>}
     */
    public static function ticketChoiceKeyboard(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Ya, buat tiket', 'callback_data' => 'nudge:ticket_yes'],
                    ['text' => '📝 Tidak, cukup catatan', 'callback_data' => 'nudge:ticket_no'],
                ],
            ],
        ];
    }
}
