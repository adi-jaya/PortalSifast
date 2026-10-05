<?php

namespace App\Actions\Auth;

use App\Support\Auth\ProgressiveLoginFailure;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\StatefulGuard;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

class AttemptLoginWithProgressiveLock
{
    public function __construct(
        protected StatefulGuard $guard,
        protected LoginRateLimiter $limiter,
    ) {}

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        if (Fortify::$authenticateUsingCallback) {
            return $this->handleUsingCustomCallback($request, $next);
        }

        if ($this->guard->attempt(
            $request->only(Fortify::username(), 'password'),
            $request->boolean('remember'),
        )) {
            return $next($request);
        }

        $this->throwFailedAuthenticationException($request);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return mixed
     */
    protected function handleUsingCustomCallback($request, $next)
    {
        $user = call_user_func(Fortify::$authenticateUsingCallback, $request);

        if (! $user) {
            $this->fireFailedEvent($request);

            return $this->throwFailedAuthenticationException($request);
        }

        $this->guard->login($user, $request->boolean('remember'));

        return $next($request);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     */
    protected function throwFailedAuthenticationException($request): never
    {
        ProgressiveLoginFailure::throw($this->limiter, $request);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     */
    protected function fireFailedEvent($request): void
    {
        event(new Failed($this->guard->name ?? config('fortify.guard'), null, [
            Fortify::username() => $request->{Fortify::username()},
            'password' => $request->password,
        ]));
    }
}
