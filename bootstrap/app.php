<?php

use App\Http\Middleware\AuthenticateAgent;
use App\Http\Middleware\AuthenticateBerkasScanAgent;
use App\Http\Middleware\EnsureAsetAccess;
use App\Http\Middleware\EnsureBerkasKepegawaianAccess;
use App\Http\Middleware\EnsureDriverChecklistAccess;
use App\Http\Middleware\EnsureInventarisAccess;
use App\Http\Middleware\EnsureMonitoringAccess;
use App\Http\Middleware\EnsureMonitoringKategoriAccess;
use App\Http\Middleware\EnsurePatroliAccess;
use App\Http\Middleware\EnsurePayrollAccess;
use App\Http\Middleware\EnsureSimmutuInputAccess;
use App\Http\Middleware\EnsureSimmutuManageAccess;
use App\Http\Middleware\EnsureSimmutuViewAccess;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsOfficer;
use App\Http\Middleware\EnsureWebOfficialAdminAccess;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\TrackUserActivity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // API SiFast (frontend kepegawaian) memakai Bearer token saja; tidak perlu CSRF cookie
        $middleware->validateCsrfTokens(['api/sifast/*', 'api/agent/*', 'api/berkas-scan/*']);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'officer' => EnsureUserIsOfficer::class,
            'admin' => EnsureUserIsAdmin::class,
            'inventaris.access' => EnsureInventarisAccess::class,
            'aset.access' => EnsureAsetAccess::class,
            'payroll.access' => EnsurePayrollAccess::class,
            'patroli.access' => EnsurePatroliAccess::class,
            'driver.access' => EnsureDriverChecklistAccess::class,
            'monitoring.access' => EnsureMonitoringAccess::class,
            'monitoring.kategori' => EnsureMonitoringKategoriAccess::class,
            'berkas-kepegawaian.access' => EnsureBerkasKepegawaianAccess::class,
            'simmutu.view' => EnsureSimmutuViewAccess::class,
            'simmutu.manage' => EnsureSimmutuManageAccess::class,
            'simmutu.input' => EnsureSimmutuInputAccess::class,
            'webofficial.admin' => EnsureWebOfficialAdminAccess::class,
            'auth.agent' => AuthenticateAgent::class,
            'auth.berkas-scan-agent' => AuthenticateBerkasScanAgent::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            TrackUserActivity::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if (! app()->environment(['local', 'testing']) && in_array($response->getStatusCode(), [500, 503, 404, 403])) {
                return Inertia::render('errors/error', [
                    'status' => $response->getStatusCode(),
                ])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })->create();
