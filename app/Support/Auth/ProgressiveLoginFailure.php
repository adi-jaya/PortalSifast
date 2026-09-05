<?php

namespace App\Support\Auth;

use App\Auth\ProgressiveLoginRateLimiter;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

class ProgressiveLoginFailure
{
    /**
     * @throws ValidationException
     */
    public static function throw(LoginRateLimiter $limiter, $request): never
    {
        $limiter->increment($request);

        if ($limiter->tooManyAttempts($request)) {
            event(new Lockout($request));

            $seconds = $limiter->availableIn($request);
            $minutes = max(1, (int) ceil($seconds / 60));

            throw ValidationException::withMessages([
                Fortify::username() => [
                    "Terlalu banyak percobaan gagal. Silakan coba lagi dalam {$minutes} menit ({$seconds} detik).",
                ],
            ])->status(Response::HTTP_TOO_MANY_REQUESTS);
        }

        $message = trans('auth.failed');

        if ($limiter instanceof ProgressiveLoginRateLimiter && $limiter->shouldWarn($request)) {
            $remaining = $limiter->remainingAttempts($request);
            $message .= " Peringatan: sisa {$remaining} percobaan sebelum akun dikunci sementara.";
        }

        throw ValidationException::withMessages([
            Fortify::username() => [$message],
        ]);
    }
}
