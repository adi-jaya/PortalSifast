<?php

namespace App\Http\Responses;

use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LockoutResponse as LockoutResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

class ProgressiveLockoutResponse implements LockoutResponseContract
{
    public function __construct(protected LoginRateLimiter $limiter) {}

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $seconds = $this->limiter->availableIn($request);
        $minutes = max(1, (int) ceil($seconds / 60));

        throw ValidationException::withMessages([
            Fortify::username() => [
                "Terlalu banyak percobaan gagal. Silakan coba lagi dalam {$minutes} menit ({$seconds} detik).",
            ],
        ])->status(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
