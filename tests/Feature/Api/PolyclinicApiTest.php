<?php

use App\Models\User;
use App\Models\WebOfficialPolyclinic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->pemohon = User::factory()->pemohon()->create();
});

it('lists curated active polyclinics publicly', function (): void {
    WebOfficialPolyclinic::factory()->create([
        'kd_poli' => 'INT1',
        'slug' => 'klinik-penyakit-dalam',
        'simrs_name' => 'Klinik Penyakit Dalam',
    ]);

    WebOfficialPolyclinic::factory()->inactive()->create([
        'kd_poli' => 'OBG1',
        'slug' => 'klinik-kandungan',
        'simrs_name' => 'Klinik Kandungan',
    ]);

    $this->getJson('/api/poliklinik')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'klinik-penyakit-dalam');
});

it('shows polyclinic detail and doctors payload key', function (): void {
    WebOfficialPolyclinic::factory()->create([
        'kd_poli' => 'INT1',
        'slug' => 'klinik-penyakit-dalam',
        'simrs_name' => 'Klinik Penyakit Dalam',
        'long_description' => 'Tentang layanan penyakit dalam.',
    ]);

    $this->getJson('/api/poliklinik/klinik-penyakit-dalam')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.slug', 'klinik-penyakit-dalam')
        ->assertJsonPath('data.longDescription', 'Tentang layanan penyakit dalam.')
        ->assertJsonStructure([
            'data' => ['slug', 'kdPoli', 'name', 'shortDescription', 'longDescription', 'doctors'],
        ]);
});

it('returns not found for unknown polyclinic slug', function (): void {
    $this->getJson('/api/poliklinik/tidak-ada')
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('admin can create polyclinic content', function (): void {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/admin/poliklinik', [
        'kdPoli' => 'INT1',
        'label' => 'KLINIK SPESIALIS',
        'nameOverride' => 'Klinik Penyakit Dalam',
        'shortDescription' => 'Layanan penyakit dalam untuk diagnosis dan terapi komprehensif.',
        'longDescription' => 'Penjelasan lengkap layanan penyakit dalam.',
        'photo' => 'https://cdn.rsasitifatimah.id/poliklinik/penyakit-dalam.jpg',
        'sortOrder' => 1,
        'isActive' => true,
    ])->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.kdPoli', 'INT1');

    $this->assertDatabaseHas('web_official_polyclinics', [
        'kd_poli' => 'INT1',
        'is_active' => true,
    ]);
});

it('admin can read available simrs polyclinic list endpoint', function (): void {
    Sanctum::actingAs($this->admin);

    $this->getJson('/api/admin/poliklinik/available')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data']);
});

it('does not keep empty simrs polyclinic cache entries', function (): void {
    Illuminate\Support\Facades\Cache::put('simrs.polyclinic.active', [], now()->addHour());

    $service = app(App\Services\Simrs\PolyclinicService::class);
    $result = $service->listAllActive();

    expect(Illuminate\Support\Facades\Cache::get('simrs.polyclinic.active'))->not->toBe([]);

    if ($result !== []) {
        expect(Illuminate\Support\Facades\Cache::get('simrs.polyclinic.active'))->toBe($result);
    }
});

it('non admin cannot access admin polyclinic endpoints', function (): void {
    Sanctum::actingAs($this->pemohon);

    $this->getJson('/api/admin/poliklinik')->assertForbidden();
});
