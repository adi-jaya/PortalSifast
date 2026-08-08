<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('login page renders soft shell auth experience', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/login')
        );
});

test('authenticated settings profile uses soft shell app layout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/profile')
        );
});

test('design tokens use clean white canvas fresh blue and inter', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('--primary: #2563EB')
        ->toContain('--sidebar: #1D4ED8')
        ->toContain('--background: #F8FAFC')
        ->toContain('--card: #FFFFFF')
        ->toContain('Inter, ui-sans-serif')
        ->toContain('--text-h1: 3rem')
        ->toContain('.card-brand')
        ->toContain('.data-table')
        ->toContain('h-[52px]')
        ->toContain('linear-gradient(90deg, #EFF6FF');
});

test('blade loads inter font family', function () {
    $blade = file_get_contents(resource_path('views/app.blade.php'));

    expect($blade)->toContain('family=Inter:wght@100;200;300;400;500;600;700;800');
});

test('icon well component provides solid icon containers', function () {
    $iconWell = file_get_contents(resource_path('js/components/icon-well.tsx'));

    expect($iconWell)
        ->toContain('sidebar-active')
        ->toContain('brand')
        ->toContain('strokeWidth = 2');
});

test('sidebar branding uses portal sifast naming', function () {
    $portalNav = file_get_contents(resource_path('js/lib/portal-nav.ts'));
    $sidebar = file_get_contents(resource_path('js/components/template-sidebar.tsx'));

    expect($portalNav)
        ->toContain("export const APP_NAME = 'Portal Sifast'")
        ->toContain("export const APP_SUBTITLE = 'RS Aisyiyah Siti Fatimah'");

    expect($sidebar)
        ->toContain('IconWell')
        ->toContain('APP_NAME')
        ->toContain('APP_SUBTITLE');
});

test('data table primitives match soft shell table spec', function () {
    $table = file_get_contents(resource_path('js/components/ui/table.tsx'));
    $pagination = file_get_contents(resource_path('js/components/data-table-pagination.tsx'));
    $search = file_get_contents(resource_path('js/components/data-table-search.tsx'));
    $skeleton = file_get_contents(resource_path('js/components/data-table-skeleton.tsx'));
    $rowAction = file_get_contents(resource_path('js/components/row-action-button.tsx'));
    $badge = file_get_contents(resource_path('js/components/ui/badge.tsx'));

    expect($table)->toContain('data-table');
    expect($pagination)->toContain('rounded-[10px]');
    expect($search)->toContain('h-11');
    expect($skeleton)->toContain('rows = 5');
    expect($rowAction)->toContain('size-9');
    expect($badge)->toContain('rounded-full');
});
