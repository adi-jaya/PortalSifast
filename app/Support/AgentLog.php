<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes to the agent log channel without ever failing the HTTP request.
 * (Monolog permission errors previously turned successful heartbeats into HTTP 500.)
 */
final class AgentLog
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function write(string $level, string $message, array $context = []): void
    {
        try {
            Log::channel('agent')->log($level, $message, $context);
        } catch (Throwable $exception) {
            try {
                Log::log($level, '[agent] '.$message, $context);
            } catch (Throwable) {
                report($exception);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function debug(string $message, array $context = []): void
    {
        self::write('debug', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }
}
