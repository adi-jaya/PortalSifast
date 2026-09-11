<?php

use App\Models\User;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    if (DB::connection() instanceof SQLiteConnection) {
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('YEARWEEK', function ($date, $mode = 0) {
            return $date ? date('oW', strtotime((string) $date)) : null;
        });
        $pdo->sqliteCreateFunction('DATE_FORMAT', function ($date, $format) {
            if (! $date) {
                return null;
            }
            $format = str_replace(['%Y', '%m', '%d'], ['Y', 'm', 'd'], (string) $format);

            return date($format, strtotime((string) $date));
        });
    }
});

it('shares can_manage_portals permission as true for admin and false for staff', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('permissions.can_manage_portals', true)
        );

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('permissions.can_manage_portals', false)
        );
});

it('evaluates can_manage_portals as false for guest requests in inertia middleware', function (): void {
    $request = \Illuminate\Http\Request::create('/dashboard');
    $request->setLaravelSession(app('session')->driver());
    $middleware = new \App\Http\Middleware\HandleInertiaRequests();

    $shared = $middleware->share($request);

    expect($shared['permissions']['can_manage_portals'])->toBeFalse();
});
