<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tickets:auto-close')->daily();

$dailyItReportTimes = config('services.telegram-bot-api.daily_it_report_times', ['23:00']);
if (! is_array($dailyItReportTimes) || $dailyItReportTimes === []) {
    $dailyItReportTimes = ['23:00'];
}
foreach ($dailyItReportTimes as $time) {
    if (! is_string($time) || $time === '') {
        continue;
    }
    Schedule::command('tickets:daily-it-report')->dailyAt($time);
}

$workNudgeCron = config('services.telegram-bot-api.work_nudge_cron', '0 7,11,15,19,23 * * *');
if (is_string($workNudgeCron) && $workNudgeCron !== '') {
    Schedule::command('tickets:work-nudge')
        ->cron($workNudgeCron)
        ->when(fn (): bool => (bool) config('services.telegram-bot-api.work_nudge_enabled', true));
}

// Check pending panic reports every 2 minutes
Schedule::command('panic:check-pending')->everyTwoMinutes();

// Check for overdue tickets and create notifications every 5 minutes
Schedule::command('notifications:check-overdue')->everyFiveMinutes();

Schedule::command('instagram:sync-feed')
    ->everyThirtyMinutes()
    ->when(fn (): bool => (bool) config('services.instagram.enabled'));

Schedule::command('rss:sync-external')
    ->hourly()
    ->when(fn (): bool => (bool) config('services.external_rss.enabled'));

Schedule::command('monitoring:mark-offline')->everyMinute();

Schedule::command('monitoring:prune-metric-samples')->dailyAt('02:15');
