<?php

use App\Enums\WebOfficialArticleCategory;
use App\Enums\WebOfficialArticleStatus;
use App\Models\User;
use App\Models\WebOfficialArticle;
use App\Models\WebOfficialRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->pemohon = User::factory()->pemohon()->create();
});

it('lists published articles publicly', function (): void {
    WebOfficialArticle::factory()->published()->create([
        'title' => 'Berita Terbaru',
        'slug' => 'berita-terbaru',
        'category' => WebOfficialArticleCategory::Berita,
    ]);
    WebOfficialArticle::factory()->create([
        'title' => 'Draft Artikel',
        'slug' => 'draft-artikel',
        'status' => WebOfficialArticleStatus::Draft,
    ]);

    $response = $this->getJson('/api/informasi');

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'berita-terbaru');
});

it('hides expired promo articles from public list', function (): void {
    WebOfficialArticle::factory()->published()->create([
        'slug' => 'promo-lama',
        'category' => WebOfficialArticleCategory::Promo,
        'valid_until' => now()->subDay(),
    ]);

    $this->getJson('/api/informasi')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('shows published article by slug', function (): void {
    WebOfficialArticle::factory()->published()->create([
        'slug' => 'paket-mcu',
        'title' => 'Paket MCU',
        'body' => '<p>Isi artikel</p>',
    ]);

    $this->getJson('/api/informasi/paket-mcu')
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'Paket MCU')
        ->assertJsonPath('data.body', '<p>Isi artikel</p>');
});

it('returns not found for missing article slug', function (): void {
    $this->getJson('/api/informasi/tidak-ada')
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('admin can create article', function (): void {
    Sanctum::actingAs($this->admin);

    $response = $this->postJson('/api/admin/informasi', [
        'title' => 'Paket Medical Check Up Hemat',
        'category' => 'Promo',
        'excerpt' => 'Cek kesehatan menyeluruh dengan harga spesial.',
        'body' => 'Pemeriksaan meliputi konsultasi dokter dan laboratorium.',
        'cover' => 'https://cdn.rsasitifatimah.id/informasi/promo-mcu.jpg',
        'validUntil' => now()->addMonths(3)->toIso8601String(),
        'status' => 'draft',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.slug', 'paket-medical-check-up-hemat');

    $this->assertDatabaseHas('web_official_articles', [
        'title' => 'Paket Medical Check Up Hemat',
        'status' => 'draft',
    ]);
});

it('non admin cannot access admin informasi endpoints', function (): void {
    Sanctum::actingAs($this->pemohon);

    $this->getJson('/api/admin/informasi')->assertForbidden();
});

it('lists active rooms publicly', function (): void {
    WebOfficialRoom::factory()->create(['slug' => 'vip-a', 'name' => 'VIP A']);
    WebOfficialRoom::factory()->inactive()->create(['slug' => 'nonaktif', 'name' => 'Nonaktif']);

    $this->getJson('/api/kamar-inap')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'vip-a');
});

it('shows active room by slug', function (): void {
    WebOfficialRoom::factory()->create([
        'slug' => 'president-suite',
        'name' => 'President Suite',
        'price' => 2_500_000,
    ]);

    $this->getJson('/api/kamar-inap/president-suite')
        ->assertSuccessful()
        ->assertJsonPath('data.price', 2500000);
});

it('admin can create room', function (): void {
    Sanctum::actingAs($this->admin);

    $response = $this->postJson('/api/admin/kamar-inap', [
        'name' => 'Deluxe Room',
        'description' => 'Kamar nyaman dengan fasilitas lengkap.',
        'price' => 800000,
        'photo' => 'https://cdn.rsasitifatimah.id/kamar/deluxe.jpg',
        'facilities' => ['AC', 'TV', 'Kamar Mandi Dalam'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.slug', 'deluxe-room');

    $this->assertDatabaseHas('web_official_rooms', [
        'name' => 'Deluxe Room',
        'price' => 800000,
    ]);
});

it('admin can patch room price', function (): void {
    Sanctum::actingAs($this->admin);
    $room = WebOfficialRoom::factory()->create(['price' => 800000]);

    $this->patchJson("/api/admin/kamar-inap/{$room->id}", ['price' => 850000])
        ->assertSuccessful()
        ->assertJsonPath('data.price', 850000);
});

it('admin delete room soft deactivates', function (): void {
    Sanctum::actingAs($this->admin);
    $room = WebOfficialRoom::factory()->create(['is_active' => true]);

    $this->deleteJson("/api/admin/kamar-inap/{$room->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.deleted', true);

    expect($room->fresh()->is_active)->toBeFalse();
});

it('admin can upload media image', function (): void {
    \Illuminate\Support\Facades\Storage::fake('public');
    Sanctum::actingAs($this->admin);

    $file = \Illuminate\Http\UploadedFile::fake()->image('cover.jpg', 800, 600)->size(500);

    $response = $this->post('/api/admin/media/upload', [
        'file' => $file,
        'folder' => 'informasi',
    ], [
        'Accept' => 'application/json',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['url', 'path', 'original_name', 'mime_type', 'size']]);

    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($response->json('data.path'));
});

it('admin can upload promo media image', function (): void {
    \Illuminate\Support\Facades\Storage::fake('public');
    Sanctum::actingAs($this->admin);

    $file = \Illuminate\Http\UploadedFile::fake()->image('promo.jpg', 1200, 630)->size(700);

    $response = $this->post('/api/admin/media/upload', [
        'file' => $file,
        'folder' => 'promosi',
    ], [
        'Accept' => 'application/json',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    expect($response->json('data.path'))->toStartWith('webofficial/promosi/');
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($response->json('data.path'));
});

it('admin can upload polyclinic media image', function (): void {
    \Illuminate\Support\Facades\Storage::fake('public');
    Sanctum::actingAs($this->admin);

    $file = \Illuminate\Http\UploadedFile::fake()->image('poliklinik.jpg', 1200, 630)->size(700);

    $response = $this->post('/api/admin/media/upload', [
        'file' => $file,
        'folder' => 'poliklinik',
    ], [
        'Accept' => 'application/json',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    expect($response->json('data.path'))->toStartWith('webofficial/poliklinik/');
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($response->json('data.path'));
});

it('non admin cannot upload media', function (): void {
    Sanctum::actingAs($this->pemohon);

    $file = \Illuminate\Http\UploadedFile::fake()->image('cover.jpg');

    $this->post('/api/admin/media/upload', ['file' => $file], ['Accept' => 'application/json'])
        ->assertForbidden();
});

it('authenticated user can logout and revoke token', function (): void {
    $token = $this->admin->createToken('api-login')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/logout')
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect(\Laravel\Sanctum\PersonalAccessToken::findToken($token))->toBeNull();
});

it('authenticated user can refresh token', function (): void {
    $oldToken = $this->admin->createToken('api-login')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$oldToken)
        ->postJson('/api/token/refresh')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['token']]);

    $newToken = $response->json('data.token');

    expect(\Laravel\Sanctum\PersonalAccessToken::findToken($oldToken))->toBeNull();
    expect(\Laravel\Sanctum\PersonalAccessToken::findToken($newToken))->not->toBeNull();

    $this->withHeader('Authorization', 'Bearer '.$newToken)
        ->getJson('/api/user')
        ->assertSuccessful()
        ->assertJsonPath('data.email', $this->admin->email);
});
