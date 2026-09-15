<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    config()->set('inertia.testing.ensure_pages_exist', false);

    $this->staff = User::factory()->create([
        'role' => 'staff',
        'email' => 'staff.portal.ctrl@rsasf.co.id',
    ]);

    $this->admin = User::factory()->create([
        'role' => 'admin',
        'email' => 'admin.portal.ctrl@rsasf.co.id',
    ]);
});

it('redirects unauthenticated guests to login', function (): void {
    $this->get(route('portal-pelaporan.index'))
        ->assertRedirect(route('login'));
});

it('allows authenticated staff to render portal-pelaporan index with authorized portals', function (): void {
    $allowedPortal = Portal::factory()->create([
        'name' => 'SIRS Online',
        'category' => 'Kemenkes',
        'is_active' => true,
    ]);

    $disallowedPortal = Portal::factory()->create([
        'name' => 'MPDN Kemenkes',
        'category' => 'Kemenkes',
        'is_active' => true,
    ]);

    UserPortalCredential::factory()->create([
        'user_id' => $this->staff->id,
        'portal_id' => $allowedPortal->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $this->actingAs($this->staff)
        ->get(route('portal-pelaporan.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal-pelaporan/index')
            ->has('portals', 1)
            ->where('portals.0.name', 'SIRS Online')
            ->has('categories', 1)
            ->where('categories.0', 'Kemenkes')
            ->has('filters', fn (Assert $filters) => $filters
                ->where('category', 'all')
                ->where('search', '')
            )
        );
});

it('allows admin to see all active portals in portal-pelaporan index', function (): void {
    Portal::factory()->create(['name' => 'SIRS Online', 'is_active' => true]);
    Portal::factory()->create(['name' => 'SIRIKA', 'is_active' => true]);
    Portal::factory()->create(['name' => 'Inactive Portal', 'is_active' => false]);

    $this->actingAs($this->admin)
        ->get(route('portal-pelaporan.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal-pelaporan/index')
            ->has('portals', 2)
        );
});

it('passes query filters category and search to aggregator service and props', function (): void {
    $p1 = Portal::factory()->create(['name' => 'SIRS Online', 'category' => 'Kemenkes', 'is_active' => true]);
    $p2 = Portal::factory()->create(['name' => 'SIRIKA BKKBN', 'category' => 'BKKBN', 'is_active' => true]);

    foreach ([$p1, $p2] as $p) {
        UserPortalCredential::factory()->create([
            'user_id' => $this->staff->id,
            'portal_id' => $p->id,
            'is_active' => true,
        ]);
    }

    $this->actingAs($this->staff)
        ->get(route('portal-pelaporan.index', ['category' => 'BKKBN', 'search' => 'SIRIKA']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal-pelaporan/index')
            ->has('portals', 1)
            ->where('portals.0.name', 'SIRIKA BKKBN')
            ->where('filters.category', 'BKKBN')
            ->where('filters.search', 'SIRIKA')
        );
});
