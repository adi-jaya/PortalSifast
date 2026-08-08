<?php

use App\Models\AsetRuang;
use App\Models\PatroliArea;
use App\Models\PatroliCheckinItem;
use App\Models\PatroliRuang;
use App\Models\PatroliTemplate;
use App\Models\User;

use function Pest\Laravel\actingAs;

function seedPatroliTitik(array $itemNames = ['Pintu', 'CCTV', 'AC']): array
{
    $template = PatroliTemplate::query()->create([
        'nama' => 'Area IGD '.uniqid(),
        'is_active' => true,
    ]);

    $items = [];
    foreach ($itemNames as $index => $nama) {
        $items[] = $template->items()->create([
            'nama' => $nama,
            'urutan' => $index,
            'is_active' => true,
        ]);
    }

    $area = PatroliArea::query()->create([
        'nama' => 'Area Test '.uniqid(),
        'is_active' => true,
    ]);

    $ruang = PatroliRuang::query()->create([
        'patroli_area_id' => $area->id,
        'kode' => 'IGD-'.uniqid(),
        'nama' => 'IGD Test',
        'patroli_template_id' => $template->id,
        'is_active' => true,
    ]);

    return compact('template', 'items', 'area', 'ruang');
}

test('user with flag can create checkin with item snapshots', function () {
    $user = User::factory()->create(['can_access_patroli' => true]);
    ['ruang' => $ruang, 'items' => $items] = seedPatroliTitik();

    $payload = [
        'patroli_ruang_id' => $ruang->id,
        'catatan' => 'Ronde malam',
        'items' => [
            ['patroli_template_item_id' => $items[0]->id, 'status' => 'berfungsi'],
            ['patroli_template_item_id' => $items[1]->id, 'status' => 'tidak_berfungsi'],
            ['patroli_template_item_id' => $items[2]->id, 'status' => 'tidak_dicek'],
        ],
    ];

    actingAs($user)
        ->post('/patroli/checkin', $payload)
        ->assertRedirect();

    $checkin = $ruang->patroliCheckins()->with('items')->first();
    expect($checkin)->not->toBeNull()
        ->and($checkin->user_id)->toBe($user->id)
        ->and($checkin->items)->toHaveCount(3)
        ->and($checkin->items->pluck('nama_item')->all())->toBe(['Pintu', 'CCTV', 'AC'])
        ->and($checkin->items->firstWhere('nama_item', 'CCTV')->status)->toBe(PatroliCheckinItem::STATUS_TIDAK_BERFUNGSI);
});

test('store checkin without template fails validation', function () {
    $user = User::factory()->create(['can_access_patroli' => true]);
    $area = PatroliArea::query()->create([
        'nama' => 'Area Tanpa Tpl '.uniqid(),
        'is_active' => true,
    ]);
    $ruang = PatroliRuang::query()->create([
        'patroli_area_id' => $area->id,
        'kode' => 'NO-TPL-'.uniqid(),
        'nama' => 'Tanpa Template',
        'is_active' => true,
    ]);

    actingAs($user)
        ->post('/patroli/checkin', [
            'patroli_ruang_id' => $ruang->id,
            'items' => [
                ['patroli_template_item_id' => 1, 'status' => 'berfungsi'],
            ],
        ])
        ->assertSessionHasErrors();
});

test('scan form only shows active template items', function () {
    $user = User::factory()->create(['can_access_patroli' => true]);
    ['ruang' => $ruang, 'template' => $template, 'items' => $items] = seedPatroliTitik(['Pintu', 'CCTV']);

    $items[1]->update(['is_active' => false]);
    $template->items()->create([
        'nama' => 'AC',
        'urutan' => 2,
        'is_active' => true,
    ]);

    actingAs($user)
        ->get(route('patroli.scan', $ruang))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patroli/checkin/scan')
            ->has('items', 2)
            ->where('items.0.nama', 'Pintu')
            ->where('items.1.nama', 'AC'));
});

test('assign template to patroli ruang works', function () {
    $user = User::factory()->create(['can_access_patroli' => true]);
    $template = PatroliTemplate::query()->create([
        'nama' => 'Koridor '.uniqid(),
        'is_active' => true,
    ]);
    $template->items()->create(['nama' => 'Lampu', 'urutan' => 0, 'is_active' => true]);
    $area = PatroliArea::query()->create([
        'nama' => 'Area Koridor '.uniqid(),
        'is_active' => true,
    ]);
    $ruang = PatroliRuang::query()->create([
        'patroli_area_id' => $area->id,
        'kode' => 'KRD-'.uniqid(),
        'nama' => 'Koridor',
        'is_active' => true,
    ]);

    actingAs($user)
        ->put(route('patroli.area.ruang.update', [$area, $ruang]), [
            'nama' => $ruang->nama,
            'kode' => $ruang->kode,
            'is_active' => true,
            'patroli_template_id' => $template->id,
        ])
        ->assertRedirect();

    expect($ruang->fresh()->patroli_template_id)->toBe($template->id);
});

test('creating ruang does not store aset_ruang foreign key', function () {
    $user = User::factory()->create(['can_access_patroli' => true]);
    $area = PatroliArea::query()->create([
        'nama' => 'Area Poli '.uniqid(),
        'is_active' => true,
    ]);
    AsetRuang::query()->create([
        'kode_ruang' => 'GIGI-'.uniqid(),
        'nama_ruang' => 'Poli Gigi Inventaris',
    ]);

    actingAs($user)
        ->post(route('patroli.area.ruang.store', $area), [
            'nama' => 'Poli Gigi',
            'kode' => 'GIGI01',
            'is_active' => true,
        ])
        ->assertRedirect();

    $ruang = PatroliRuang::query()->where('patroli_area_id', $area->id)->first();
    expect($ruang)->not->toBeNull()
        ->and($ruang->nama)->toBe('Poli Gigi')
        ->and($ruang->getAttributes())->not->toHaveKey('aset_ruang_id');
});
