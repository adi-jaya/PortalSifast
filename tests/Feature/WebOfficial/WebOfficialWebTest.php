<?php

use App\Enums\WebOfficialArticleCategory;
use App\Enums\WebOfficialArticleStatus;
use App\Models\User;
use App\Models\WebOfficialArticle;
use App\Models\WebOfficialPolyclinic;
use App\Models\WebOfficialRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('staff without admin role cannot open web official dashboard', function (): void {
    $user = User::factory()->staff()->create();

    actingAs($user)->get('/web-official')->assertForbidden();
});

test('admin can open web official dashboard', function (): void {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->get('/web-official')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('web-official/index'));
});

test('admin can list articles in web panel', function (): void {
    $admin = User::factory()->admin()->create();
    WebOfficialArticle::factory()->published()->create(['title' => 'Berita RS']);

    actingAs($admin)
        ->get('/web-official/articles')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('web-official/articles/index')
            ->has('articles.data', 1)
            ->where('articles.data.0.title', 'Berita RS'));
});

test('admin can create article via web panel', function (): void {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->post('/web-official/articles', [
            'title' => 'Paket Medical Check Up Hemat',
            'category' => WebOfficialArticleCategory::Berita->value,
            'excerpt' => 'Cek kesehatan menyeluruh dengan harga spesial.',
            'body' => 'Pemeriksaan meliputi konsultasi dokter dan laboratorium.',
            'cover' => 'https://cdn.rsasitifatimah.id/informasi/promo-mcu.jpg',
            'status' => WebOfficialArticleStatus::Draft->value,
        ])
        ->assertRedirect(route('web-official.articles.index'));

    expect(WebOfficialArticle::query()->where('title', 'Paket Medical Check Up Hemat')->exists())->toBeTrue();
});

test('admin can list rooms in web panel', function (): void {
    $admin = User::factory()->admin()->create();
    WebOfficialRoom::factory()->create(['name' => 'President Suite']);

    actingAs($admin)
        ->get('/web-official/rooms')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('web-official/rooms/index')
            ->has('rooms.data', 1)
            ->where('rooms.data.0.name', 'President Suite'));
});

test('admin can update room via web panel', function (): void {
    $admin = User::factory()->admin()->create();
    $room = WebOfficialRoom::factory()->create([
        'name' => 'Kelas 1',
        'price' => 500000,
    ]);

    actingAs($admin)
        ->put("/web-official/rooms/{$room->id}", [
            'name' => 'Kelas 1 VIP',
            'description' => 'Kamar nyaman dengan fasilitas lengkap untuk pasien.',
            'price' => 750000,
            'facilities_text' => "AC\nTV\nKamar mandi dalam",
            'is_active' => true,
        ])
        ->assertRedirect(route('web-official.rooms.index'));

    $room->refresh();
    expect($room->name)->toBe('Kelas 1 VIP')
        ->and($room->price)->toBe(750000);
});

test('admin can list promos in web panel', function (): void {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->get('/web-official/promosi')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('web-official/promosi/index'));
});

test('admin can create promo via web panel', function (): void {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->post('/web-official/promosi', [
            'title' => 'Promo Paket MCU',
            'label' => 'PROMO SPESIAL',
            'excerpt' => 'Promo medical check up harga spesial.',
            'body' => '<p>Detail promo MCU</p>',
            'cover' => 'https://cdn.rsasitifatimah.id/promosi/mcu.jpg',
            'start_date' => now()->format('Y-m-d\TH:i'),
            'end_date' => now()->addMonth()->format('Y-m-d\TH:i'),
            'is_featured' => true,
            'sort_order' => 1,
            'is_active' => true,
        ])
        ->assertRedirect(route('web-official.promosi.index'));

    $this->assertDatabaseHas('web_official_promos', [
        'title' => 'Promo Paket MCU',
        'is_featured' => true,
        'is_active' => true,
    ]);
});

test('admin can open polyclinic create form with simrs options', function (): void {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->get('/web-official/poliklinik/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('web-official/poliklinik/create')
            ->has('availablePolyclinics'));
});

test('admin can list polyclinics in web panel', function (): void {
    $admin = User::factory()->admin()->create();
    WebOfficialPolyclinic::factory()->create([
        'kd_poli' => 'INT1',
        'simrs_name' => 'Klinik Penyakit Dalam',
    ]);

    actingAs($admin)
        ->get('/web-official/poliklinik')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('web-official/poliklinik/index')
            ->has('polyclinics.data', 1)
            ->where('polyclinics.data.0.kd_poli', 'INT1'));
});

test('admin can create polyclinic content via web panel', function (): void {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->post('/web-official/poliklinik', [
            'kd_poli' => 'INT1',
            'label' => 'KLINIK SPESIALIS',
            'name_override' => 'Klinik Penyakit Dalam',
            'short_description' => 'Layanan diagnosis penyakit dalam secara komprehensif.',
            'long_description' => 'Detail layanan penyakit dalam.',
            'photo' => 'https://cdn.rsasitifatimah.id/poliklinik/penyakit-dalam.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ])
        ->assertRedirect(route('web-official.poliklinik.index'));

    $this->assertDatabaseHas('web_official_polyclinics', [
        'kd_poli' => 'INT1',
        'is_active' => true,
    ]);
});

test('admin can open instagram feed panel', function (): void {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->get('/web-official/instagram')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('web-official/instagram/index')
            ->has('status')
            ->has('posts')
            ->has('apiEndpoint'));
});

test('admin can sync instagram feed from web panel', function (): void {
    $admin = User::factory()->admin()->create();

    Illuminate\Support\Facades\Config::set('services.instagram.enabled', true);
    Illuminate\Support\Facades\Config::set('services.instagram.access_token', 'test-token');
    Illuminate\Support\Facades\Config::set('services.instagram.user_id', '17841400000000000');

    Illuminate\Support\Facades\Http::fake([
        'graph.facebook.com/*' => Illuminate\Support\Facades\Http::response([
            'data' => [
                [
                    'id' => '1789655001',
                    'caption' => 'Info layanan RS',
                    'media_type' => 'IMAGE',
                    'media_url' => 'https://cdn.example.com/info.jpg',
                    'permalink' => 'https://instagram.com/p/web-sync',
                    'timestamp' => '2026-07-03T10:00:00+0000',
                ],
            ],
        ], 200),
    ]);

    actingAs($admin)
        ->post('/web-official/instagram/sync')
        ->assertRedirect(route('web-official.instagram.index'))
        ->assertSessionHas('success');
});

test('admin can open external rss panel', function (): void {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->get('/web-official/berita-eksternal')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('web-official/berita-eksternal/index')
            ->has('status')
            ->has('items')
            ->has('apiEndpoint'));
});

test('admin can sync external rss from web panel', function (): void {
    $admin = User::factory()->admin()->create();

    Http::fake([
        '*' => Http::response(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel><item>
<title>Berita Web Panel</title>
<link>https://muhammadiyah.or.id/web-panel</link>
<description>Ringkasan</description>
<pubDate>Thu, 02 Jul 2026 15:49:42 +0000</pubDate>
</item></channel></rss>
XML, 200),
    ]);

    actingAs($admin)
        ->post('/web-official/berita-eksternal/sync')
        ->assertRedirect(route('web-official.berita-eksternal.index'))
        ->assertSessionHas('success');
});
