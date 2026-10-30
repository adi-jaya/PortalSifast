<?php

namespace App\Actions\Auth;

use App\Support\Auth\ProgressiveLoginFailure;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as FortifyRedirectIfTwoFactorAuthenticatable;

class RedirectIfTwoFactorAuthenticatable extends FortifyRedirectIfTwoFactorAuthenticatable
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return never
     */
    protected function throwFailedAuthenticationException($request)
    {
        ProgressiveLoginFailure::throw($this->limiter, $request);
    }
}
