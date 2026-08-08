<?php

namespace App\Notifications;

use App\Support\DailyItTicketReportCopy;
use App\Support\TelegramBotConfig;
use Carbon\CarbonInterface;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

/**
 * Laporan harian IT: jumlah tiket dibuat/di-assign hari itu per teknisi.
 *
 * @param  list<array{assignee_id: int, name: string, count: int}>  $rows
 */
class DailyItTicketReportTelegramNotification extends Notification
{
    /**
     * @param  list<array{assignee_id: int, name: string, count: int}>  $rows
     */
    public function __construct(
        public CarbonInterface $moment,
        public array $rows,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $local = $this->moment->timezone(config('app.timezone'));
        $stamp = $local->format('d/m/Y H:i');
        $body = DailyItTicketReportCopy::buildBody($this->rows, (int) $local->format('z'));

        $message = TelegramMessage::create()
            ->normal()
            ->content("📋 Laporan Harian IT\n{$stamp}\n\n{$body}");

        return TelegramBotConfig::applyGroupMessageOptions($message);
    }
}
