<?php

namespace App\Providers;

use App\Actions\Auth\AttemptLoginWithProgressiveLock;
use App\Actions\Auth\EnsureLoginIsNotProgressivelyLocked;
use App\Actions\Auth\RedirectIfTwoFactorAuthenticatable;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Auth\ProgressiveLoginRateLimiter;
use App\Http\Responses\ProgressiveLockoutResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\LockoutResponse as LockoutResponseContract;
use Laravel\Fortify\Contracts\RedirectsIfTwoFactorAuthenticatable as RedirectsIfTwoFactorAuthenticatableContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginRateLimiter::class, ProgressiveLoginRateLimiter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(LockoutResponseContract::class, ProgressiveLockoutResponse::class);
        $this->app->singleton(RedirectsIfTwoFactorAuthenticatableContract::class, RedirectIfTwoFactorAuthenticatable::class);

        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureLoginPipeline();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register'));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Safety net against request floods; progressive lockout handles auth failures.
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(30)->by($throttleKey);
        });
    }

    /**
     * Use progressive lockout instead of Fortify's fixed 5/minute LoginRateLimiter.
     */
    private function configureLoginPipeline(): void
    {
        Fortify::authenticateThrough(function (Request $request) {
            return array_filter([
                EnsureLoginIsNotProgressivelyLocked::class,
                config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
                Features::enabled(Features::twoFactorAuthentication())
                    ? RedirectsIfTwoFactorAuthenticatableContract::class
                    : null,
                AttemptLoginWithProgressiveLock::class,
                PrepareAuthenticatedSession::class,
            ]);
        });
    }
}
