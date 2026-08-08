<?php

namespace App\Console\Commands;

use App\Services\TelegramWorkNudgeSender;
use Illuminate\Console\Command;

class TicketsWorkNudgeCommand extends Command
{
    protected $signature = 'tickets:work-nudge
                            {--dry-run : Hanya tampilkan ringkasan, jangan kirim Telegram}';

    protected $description = 'Pengingat Jarvis: tanya kerjaan tim IT di grup + DM (wizard catatan/tiket).';

    public function handle(TelegramWorkNudgeSender $sender): int
    {
        if (! (bool) config('services.telegram-bot-api.work_nudge_enabled', true)) {
            $this->warn('TELEGRAM_WORK_NUDGE_ENABLED=false — dilewati.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $dep = (string) config('services.telegram-bot-api.work_nudge_dep', 'IT');
            $this->info("Dry-run work nudge (dep={$dep}). Tidak mengirim Telegram.");

            return self::SUCCESS;
        }

        $result = $sender->send();
        $this->info('Grup: '.($result['group'] ? 'terkirim' : 'skip/gagal'));
        $this->info('DM sukses: '.$result['dms'].' | gagal: '.$result['skipped']);

        return self::SUCCESS;
    }
}
