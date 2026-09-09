<?php

use App\Models\Portal;
use Database\Seeders\PortalSeeder;

it('seeds all 8 portal groups idempotently with proper configurations', function (): void {
    $this->seed(PortalSeeder::class);

    $expectedSlugs = [
        'sirika-bkkbn',
        'siga-kemendukbangga',
        'siha-kemenkes',
        'mpdn-kemenkes',
        'sitb-kemenkes',
        'sigizi-kemenkes',
        'satusehat-kemenkes',
        'mutufasyankes-ikp',
        'mutufasyankes-ppra',
        'mutufasyankes-simar',
        'sirs-online',
    ];

    foreach ($expectedSlugs as $slug) {
        expect(Portal::where('slug', $slug)->exists())
            ->toBeTrue("Portal with slug {$slug} was not seeded.");
    }

    // Verifikasi konfigurasi SIRS Online (SPA tanpa attribute name/id)
    $sirs = Portal::where('slug', 'sirs-online')->first();
    expect($sirs->form_config['is_spa'])->toBeTrue()
        ->and($sirs->form_config['username_field']['selectors'])->toContain("input[type='email']")
        ->and($sirs->form_config['password_field']['selectors'])->toContain("input[type='password']");

    // Menjalankan seeder kedua kali tidak boleh menimbulkan error duplikasi
    $this->seed(PortalSeeder::class);
    expect(Portal::where('slug', 'sirs-online')->count())->toBe(1);
});

it('can be run through DatabaseSeeder', function (): void {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    expect(Portal::count())->toBe(11);
});
