<?php

namespace App\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

class ProgressiveLoginRateLimiter extends LoginRateLimiter
{
    public function attempts(Request $request): int
    {
        return (int) Cache::get($this->attemptsCacheKey($request), 0);
    }

    public function tooManyAttempts(Request $request): bool
    {
        $lockedUntil = Cache::get($this->lockCacheKey($request));

        return is_int($lockedUntil) && $lockedUntil > now()->getTimestamp();
    }

    public function increment(Request $request): void
    {
        $maxAttempts = (int) config('login_protection.max_attempts', 5);
        $attempts = $this->attempts($request) + 1;

        Cache::put(
            $this->attemptsCacheKey($request),
            $attempts,
            now()->addDay(),
        );

        if ($attempts >= $maxAttempts) {
            $this->applyLockout($request);
        }
    }

    public function availableIn(Request $request): int
    {
        $lockedUntil = Cache::get($this->lockCacheKey($request));

        if (! is_int($lockedUntil)) {
            return 0;
        }

        return max(0, $lockedUntil - now()->getTimestamp());
    }

    public function clear(Request $request): void
    {
        Cache::forget($this->attemptsCacheKey($request));
        Cache::forget($this->lockCacheKey($request));
        Cache::forget($this->levelCacheKey($request));
    }

    public function remainingAttempts(Request $request): int
    {
        $maxAttempts = (int) config('login_protection.max_attempts', 5);

        return max(0, $maxAttempts - $this->attempts($request));
    }

    public function shouldWarn(Request $request): bool
    {
        $warnAfter = (int) config('login_protection.warn_after', 3);

        return $this->attempts($request) >= $warnAfter && ! $this->tooManyAttempts($request);
    }

    public function lockoutSecondsForNextCycle(Request $request): int
    {
        /** @var list<int> $delays */
        $delays = array_values(config('login_protection.lockout_seconds', [120, 300, 600, 1200, 1800]));
        $level = (int) Cache::get($this->levelCacheKey($request), 0);
        $index = min($level, count($delays) - 1);

        return (int) $delays[$index];
    }

    protected function applyLockout(Request $request): void
    {
        $seconds = $this->lockoutSecondsForNextCycle($request);
        $level = (int) Cache::get($this->levelCacheKey($request), 0);

        Cache::put(
            $this->lockCacheKey($request),
            now()->getTimestamp() + $seconds,
            $seconds,
        );

        Cache::put(
            $this->levelCacheKey($request),
            $level + 1,
            now()->addDay(),
        );

        Cache::forget($this->attemptsCacheKey($request));
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());
    }

    protected function attemptsCacheKey(Request $request): string
    {
        return 'login_protection:attempts:'.$this->throttleKey($request);
    }

    protected function lockCacheKey(Request $request): string
    {
        return 'login_protection:locked_until:'.$this->throttleKey($request);
    }

    protected function levelCacheKey(Request $request): string
    {
        return 'login_protection:lockout_level:'.$this->throttleKey($request);
    }
}
