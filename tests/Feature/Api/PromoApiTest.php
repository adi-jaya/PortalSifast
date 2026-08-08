<?php

use App\Models\User;
use App\Models\WebOfficialPromo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->pemohon = User::factory()->pemohon()->create();
});

it('lists active promos publicly', function (): void {
    WebOfficialPromo::factory()->create([
        'title' => 'Promo Aktif',
        'slug' => 'promo-aktif',
    ]);
    WebOfficialPromo::factory()->inactive()->create([
        'title' => 'Promo Nonaktif',
        'slug' => 'promo-nonaktif',
    ]);
    WebOfficialPromo::factory()->expired()->create([
        'title' => 'Promo Expired',
        'slug' => 'promo-expired',
    ]);

    $this->getJson('/api/promosi')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'promo-aktif');
});

it('supports featured filter for public promos', function (): void {
    WebOfficialPromo::factory()->featured()->create([
        'title' => 'Promo Featured',
        'slug' => 'promo-featured',
    ]);
    WebOfficialPromo::factory()->create([
        'title' => 'Promo Biasa',
        'slug' => 'promo-biasa',
        'is_featured' => false,
    ]);

    $this->getJson('/api/promosi?featured=true')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'promo-featured');
});

it('shows promo detail by slug', function (): void {
    Sanctum::actingAs($this->admin);

    $response = $this->postJson('/api/admin/promosi', [
        'title' => 'Paket Medical Check Up Hemat',
        'label' => 'PROMO SPESIAL',
        'excerpt' => 'Cek kesehatan menyeluruh dengan harga spesial.',
        'body' => '<p>Detail promo lengkap</p>',
        'cover' => 'https://cdn.rsasitifatimah.id/promosi/mcu.jpg',
        'startDate' => now()->subDay()->toIso8601String(),
        'endDate' => now()->addDays(10)->toIso8601String(),
        'isFeatured' => true,
        'sortOrder' => 1,
        'isActive' => true,
    ])->assertCreated();

    $slug = $response->json('data.slug');

    $this->getJson('/api/promosi/'.$slug)
        ->assertSuccessful()
        ->assertJsonPath('data.slug', $slug)
        ->assertJsonPath('data.body', '<p>Detail promo lengkap</p>');
});

it('admin can create promo', function (): void {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/admin/promosi', [
        'title' => 'Paket Persalinan Nyaman',
        'label' => 'PROMO SPESIAL',
        'excerpt' => 'Promo paket persalinan dengan fasilitas lengkap.',
        'body' => '<p>Detail paket persalinan</p>',
        'cover' => 'https://cdn.rsasitifatimah.id/promosi/persalinan.jpg',
        'startDate' => now()->toIso8601String(),
        'endDate' => now()->addMonth()->toIso8601String(),
        'isFeatured' => true,
        'sortOrder' => 2,
        'isActive' => true,
    ])->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.slug', 'paket-persalinan-nyaman');

    $this->assertDatabaseHas('web_official_promos', [
        'title' => 'Paket Persalinan Nyaman',
        'is_featured' => true,
        'is_active' => true,
    ]);
});

it('non admin cannot access admin promosi endpoints', function (): void {
    Sanctum::actingAs($this->pemohon);

    $this->getJson('/api/admin/promosi')->assertForbidden();
});
