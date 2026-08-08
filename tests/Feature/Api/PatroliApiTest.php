<?php

use App\Models\PatroliArea;
use App\Models\PatroliRuang;
use App\Models\PatroliTemplate;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function seedApiPatroliTitik(): array
{
    $template = PatroliTemplate::query()->create([
        'nama' => 'API Template '.uniqid(),
        'is_active' => true,
    ]);
    $items = collect(['Pintu', 'CCTV'])->map(function (string $nama, int $index) use ($template) {
        return $template->items()->create([
            'nama' => $nama,
            'urutan' => $index,
            'is_active' => true,
        ]);
    })->all();

    $area = PatroliArea::query()->create([
        'nama' => 'API Area '.uniqid(),
        'is_active' => true,
    ]);

    $ruang = PatroliRuang::query()->create([
        'patroli_area_id' => $area->id,
        'kode' => 'API-'.uniqid(),
        'nama' => 'Ruang API',
        'patroli_template_id' => $template->id,
        'is_active' => true,
    ]);

    return compact('template', 'items', 'area', 'ruang');
}

function makeServiceTokenUser(): User
{
    return User::factory()->create([
        'email' => 'api-service-patroli-'.uniqid().'@portal.local',
        'can_access_patroli' => false,
        'simrs_nik' => null,
    ]);
}

test('patroli api requires nik when using service-style token', function () {
    $service = makeServiceTokenUser();
    Sanctum::actingAs($service);

    getJson('/api/sifast/patroli/me')->assertStatus(422);
});

test('patroli api rejects nik without patroli access', function () {
    $service = makeServiceTokenUser();
    $petugas = User::factory()->staff()->create([
        'simrs_nik' => '99.01.01.'.uniqid(),
        'can_access_patroli' => false,
    ]);
    Sanctum::actingAs($service);

    getJson('/api/sifast/patroli/me?nik='.$petugas->simrs_nik)->assertForbidden();
});

test('patroli api service token plus nik can resolve qr and store checkin', function () {
    $service = makeServiceTokenUser();
    $petugas = User::factory()->staff()->create([
        'simrs_nik' => '88.02.02.'.uniqid(),
        'can_access_patroli' => true,
    ]);
    Sanctum::actingAs($service);
    ['ruang' => $ruang, 'area' => $area, 'items' => $items] = seedApiPatroliTitik();

    getJson('/api/sifast/patroli/me?nik='.$petugas->simrs_nik)
        ->assertOk()
        ->assertJsonPath('data.can_access_patroli', true)
        ->assertJsonPath('data.nik', $petugas->simrs_nik);

    postJson('/api/sifast/patroli/resolve-qr?nik='.$petugas->simrs_nik, [
        'qr_payload' => route('patroli.scan', $ruang, absolute: true),
    ])
        ->assertOk()
        ->assertJsonPath('data.ruang.id', $ruang->id)
        ->assertJsonPath('data.ruang.area.id', $area->id)
        ->assertJsonCount(2, 'data.items');

    postJson('/api/sifast/patroli/checkin?nik='.$petugas->simrs_nik, [
        'patroli_ruang_id' => $ruang->id,
        'catatan' => 'OK',
        'items' => [
            ['patroli_template_item_id' => $items[0]->id, 'status' => 'berfungsi'],
            ['patroli_template_item_id' => $items[1]->id, 'status' => 'tidak_berfungsi'],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.petugas.id', $petugas->id)
        ->assertJsonPath('data.ruang.area.nama', $area->nama)
        ->assertJsonCount(2, 'data.items');

    getJson('/api/sifast/patroli/checkin?nik='.$petugas->simrs_nik.'&mine=1')
        ->assertOk()
        ->assertJsonPath('meta.total', 1);

    getJson('/api/sifast/patroli/laporan?nik='.$petugas->simrs_nik.'&periode=bulanan')
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('patroli api scan by kode works with nik', function () {
    $service = makeServiceTokenUser();
    $petugas = User::factory()->staff()->create([
        'simrs_nik' => '77.03.03.'.uniqid(),
        'can_access_patroli' => true,
    ]);
    Sanctum::actingAs($service);
    ['ruang' => $ruang] = seedApiPatroliTitik();

    getJson('/api/sifast/patroli/scan-by-kode/'.$ruang->kode.'?nik='.$petugas->simrs_nik)
        ->assertOk()
        ->assertJsonPath('data.ruang.kode', $ruang->kode);
});
