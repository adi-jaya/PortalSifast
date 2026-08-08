<?php

namespace App\Console\Commands;

use App\Notifications\DailyItTicketReportTelegramNotification;
use App\Services\DailyItTicketReportAggregator;
use App\Support\TelegramBotConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class TicketsDailyItReportCommand extends Command
{
    protected $signature = 'tickets:daily-it-report
                            {--date= : Tanggal laporan Y-m-d (default: hari ini; jendela 00:00 sampai saat ini / akhir hari jika tanggal lampau)}
                            {--dry-run : Hanya tampilkan ringkasan di console, jangan kirim Telegram}';

    protected $description = 'Kirim laporan harian IT (tiket dibuat/di-assign per teknisi) ke grup Telegram tiket.';

    public function handle(DailyItTicketReportAggregator $aggregator): int
    {
        $dateOption = $this->option('date');
        $moment = is_string($dateOption) && $dateOption !== ''
            ? Carbon::parse($dateOption, config('app.timezone'))
            : now();

        if (is_string($dateOption) && $dateOption !== '' && ! $moment->isToday()) {
            $moment = $moment->endOfDay();
        }

        $depId = config('services.telegram-bot-api.daily_it_report_dep', 'IT');
        $depId = is_string($depId) ? $depId : 'IT';

        $rows = $aggregator->forDay($moment, $depId === '' ? null : $depId);

        $this->info('Laporan Harian IT — '.$moment->format('d/m/Y H:i').' (dep: '.$depId.')');
        if ($rows === []) {
            $this->line('(kosong)');
        } else {
            foreach ($rows as $row) {
                $this->line($row['name'].' '.$row['count'].' tiket');
            }
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: tidak mengirim ke Telegram.');

            return self::SUCCESS;
        }

        $token = TelegramBotConfig::token();
        $chatId = config('services.telegram-bot-api.tickets_group_chat_id');

        if ($token === null) {
            $this->error('Token bot kosong. Set TELEGRAM_BOT_TOKEN di .env.');

            return self::FAILURE;
        }

        if (! is_string($chatId) || $chatId === '') {
            $this->error('TELEGRAM_TICKETS_GROUP_CHAT_ID kosong di .env / config.');

            return self::FAILURE;
        }

        Notification::route('telegram', $chatId)
            ->notify(new DailyItTicketReportTelegramNotification($moment, $rows));

        $this->info('Laporan terkirim ke grup Telegram (chat_id: '.$chatId.').');

        return self::SUCCESS;
    }
}
