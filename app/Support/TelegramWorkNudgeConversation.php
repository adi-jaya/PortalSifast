<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * State percakapan wizard Jarvis (DM saja).
 *
 * @phpstan-type NudgeState array{
 *     step: 'awaiting_work'|'awaiting_ticket_choice'|'awaiting_requester',
 *     user_id: int,
 *     work_text?: string,
 *     note_id?: int
 * }
 */
final class TelegramWorkNudgeConversation
{
    public const STEP_AWAITING_WORK = 'awaiting_work';

    public const STEP_AWAITING_TICKET_CHOICE = 'awaiting_ticket_choice';

    public const STEP_AWAITING_REQUESTER = 'awaiting_requester';

    private const TTL_SECONDS = 7200;

    public static function cacheKey(string $chatId): string
    {
        return 'telegram_work_nudge:'.$chatId;
    }

    /**
     * @return NudgeState|null
     */
    public static function get(string $chatId): ?array
    {
        $state = Cache::get(self::cacheKey($chatId));

        return is_array($state) ? $state : null;
    }

    /**
     * @param  NudgeState  $state
     */
    public static function put(string $chatId, array $state): void
    {
        Cache::put(self::cacheKey($chatId), $state, self::TTL_SECONDS);
    }

    public static function clear(string $chatId): void
    {
        Cache::forget(self::cacheKey($chatId));
    }

    public static function startAwaitingWork(string $chatId, int $userId): void
    {
        self::put($chatId, [
            'step' => self::STEP_AWAITING_WORK,
            'user_id' => $userId,
        ]);
    }
}
