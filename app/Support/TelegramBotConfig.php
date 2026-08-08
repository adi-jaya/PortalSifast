<?php

namespace App\Support;

use NotificationChannels\Telegram\TelegramMessage;

final class TelegramBotConfig
{
    /**
     * Token bot. Urutan: config (termasuk hasil config:cache) → getenv → isi file .env.
     *
     * Fallback file .env berguna bila `config:cache` dibuat saat token belum ada; tanpa ini queue worker
     * tidak memuat .env dan token di config bisa tetap kosong.
     */
    public static function token(): ?string
    {
        $fromConfig = config('services.telegram-bot-api.token');
        if (is_string($fromConfig) && $fromConfig !== '') {
            return $fromConfig;
        }

        $fromEnv = getenv('TELEGRAM_BOT_TOKEN');
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }

        return self::readTokenFromDotEnvFile();
    }

    public static function hasToken(): bool
    {
        return self::token() !== null;
    }

    /**
     * Token + message_thread_id forum (jika TELEGRAM_TICKETS_GROUP_THREAD_ID di-set).
     */
    public static function applyGroupMessageOptions(TelegramMessage $message): TelegramMessage
    {
        $token = self::token();
        if ($token !== null) {
            $message->token($token);
        }

        $threadId = config('services.telegram-bot-api.tickets_group_thread_id');
        if (is_numeric($threadId) && (int) $threadId > 0) {
            $message->options(['message_thread_id' => (int) $threadId]);
        }

        return $message;
    }

    private static function readTokenFromDotEnvFile(): ?string
    {
        $path = base_path('.env');
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return null;
        }

        foreach (explode("\n", $content) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (! preg_match('/^TELEGRAM_BOT_TOKEN\s*=\s*(.*)$/', $line, $m)) {
                continue;
            }
            $raw = trim($m[1]);
            if ($raw === '') {
                return null;
            }
            if (str_starts_with($raw, '"') && str_ends_with($raw, '"') && strlen($raw) >= 2) {
                $raw = stripcslashes(substr($raw, 1, -1));
            } elseif (str_starts_with($raw, "'") && str_ends_with($raw, "'") && strlen($raw) >= 2) {
                $raw = substr($raw, 1, -1);
            }

            return $raw !== '' ? $raw : null;
        }

        return null;
    }
}
