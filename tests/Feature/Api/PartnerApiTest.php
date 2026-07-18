<?php

use App\Models\User;
use App\Models\WebOfficialPartner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->pemohon = User::factory()->pemohon()->create();
});

it('lists active partners publicly', function (): void {
    WebOfficialPartner::factory()->create([
        'name' => 'BPJS Kesehatan',
        'slug' => 'bpjs-kesehatan',
        'category' => 'Asuransi',
    ]);
    WebOfficialPartner::factory()->inactive()->create([
        'name' => 'Rekanan Nonaktif',
        'slug' => 'rekanan-nonaktif',
    ]);

    $this->getJson('/api/rekanan')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'bpjs-kesehatan')
        ->assertJsonPath('data.0.websiteUrl', fn ($value) => $value === null || is_string($value));
});

it('filters partners by category', function (): void {
    WebOfficialPartner::factory()->create([
        'name' => 'Bank Syariah',
        'slug' => 'bank-syariah',
        'category' => 'Bank',
    ]);
    WebOfficialPartner::factory()->create([
        'name' => 'Asuransi A',
        'slug' => 'asuransi-a',
        'category' => 'Asuransi',
    ]);

    $this->getJson('/api/rekanan?category=Bank')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'bank-syariah');
});

it('shows partner detail by slug', function (): void {
    WebOfficialPartner::factory()->create([
        'name' => 'PT Mitra Sehat',
        'slug' => 'pt-mitra-sehat',
        'description' => 'Mitra layanan kesehatan.',
        'website_url' => 'https://mitrasehat.example',
    ]);

    $this->getJson('/api/rekanan/pt-mitra-sehat')
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'PT Mitra Sehat')
        ->assertJsonPath('data.websiteUrl', 'https://mitrasehat.example');
});

it('returns 404 for inactive partner detail', function (): void {
    WebOfficialPartner::factory()->inactive()->create([
        'slug' => 'rekanan-tersembunyi',
    ]);

    $this->getJson('/api/rekanan/rekanan-tersembunyi')
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('admin can create partner via api', function (): void {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/admin/rekanan', [
        'name' => 'Bank Muamalat',
        'category' => 'Bank',
        'logo' => 'https://cdn.rsasitifatimah.id/rekanan/muamalat.png',
        'websiteUrl' => 'https://bankmuamalat.co.id',
        'sortOrder' => 1,
        'isActive' => true,
    ])->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.slug', 'bank-muamalat');

    $this->assertDatabaseHas('web_official_partners', [
        'name' => 'Bank Muamalat',
        'category' => 'Bank',
        'is_active' => true,
    ]);
});

it('non admin cannot access admin rekanan endpoints', function (): void {
    Sanctum::actingAs($this->pemohon);

    $this->getJson('/api/admin/rekanan')->assertForbidden();
});

test('admin can list partners in web panel', function (): void {
    WebOfficialPartner::factory()->create(['name' => 'Mitra Utama']);

    actingAs($this->admin)
        ->get('/web-official/rekanan')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('web-official/rekanan/index')
            ->has('partners.data', 1)
            ->where('partners.data.0.name', 'Mitra Utama'));
});

test('admin can create partner via web panel', function (): void {
    actingAs($this->admin)
        ->post('/web-official/rekanan', [
            'name' => 'Asuransi Sejahtera',
            'category' => 'Asuransi',
            'logo' => 'https://cdn.rsasitifatimah.id/rekanan/sejahtera.png',
            'website_url' => 'https://asuransi-sejahtera.example',
            'sort_order' => 1,
            'is_active' => true,
        ])
        ->assertRedirect(route('web-official.rekanan.index'));

    $this->assertDatabaseHas('web_official_partners', [
        'name' => 'Asuransi Sejahtera',
        'is_active' => true,
    ]);
});
