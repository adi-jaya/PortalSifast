<?php

namespace App\Actions\Auth;

use Illuminate\Auth\Events\Lockout;
use Laravel\Fortify\Contracts\LockoutResponse;
use Laravel\Fortify\LoginRateLimiter;

class EnsureLoginIsNotProgressivelyLocked
{
    public function __construct(protected LoginRateLimiter $limiter) {}

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        if (! $this->limiter->tooManyAttempts($request)) {
            return $next($request);
        }

        event(new Lockout($request));

        return app(LockoutResponse::class);
    }
}
