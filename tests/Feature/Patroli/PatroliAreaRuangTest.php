<?php

use App\Models\PatroliArea;
use App\Models\PatroliRuang;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('user with patroli access can create area and nested ruang', function () {
    $user = User::factory()->create(['can_access_patroli' => true]);

    actingAs($user)
        ->post(route('patroli.area.store'), [
            'nama' => 'Poli Klinik Lantai 1 Depan',
            'deskripsi' => 'Depan',
            'is_active' => true,
        ])
        ->assertRedirect();

    $area = PatroliArea::query()->where('nama', 'Poli Klinik Lantai 1 Depan')->first();
    expect($area)->not->toBeNull();

    actingAs($user)
        ->post(route('patroli.area.ruang.store', $area), [
            'nama' => 'Poli Gigi',
            'kode' => 'GIGI01',
            'is_active' => true,
        ])
        ->assertRedirect(route('patroli.area.show', $area));

    expect(PatroliRuang::query()->where('patroli_area_id', $area->id)->where('nama', 'Poli Gigi')->exists())->toBeTrue();
});

test('duplicate ruang name in same area is rejected', function () {
    $user = User::factory()->create(['can_access_patroli' => true]);
    $area = PatroliArea::query()->create([
        'nama' => 'Area Dup '.uniqid(),
        'is_active' => true,
    ]);
    PatroliRuang::query()->create([
        'patroli_area_id' => $area->id,
        'nama' => 'Poli Gigi',
        'is_active' => true,
    ]);

    actingAs($user)
        ->post(route('patroli.area.ruang.store', $area), [
            'nama' => 'Poli Gigi',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('nama');
});

test('titik path redirects to area index', function () {
    $user = User::factory()->create(['can_access_patroli' => true]);

    actingAs($user)
        ->get('/patroli/titik')
        ->assertRedirect('/patroli/area');
});
