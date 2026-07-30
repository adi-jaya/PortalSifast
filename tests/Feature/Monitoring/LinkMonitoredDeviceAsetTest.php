<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetRuang;
use App\Models\MonitoredDevice;
use App\Models\User;

function seedMonitorableAset(string $kodeAset = 'INV-IT-2026-0001', string $idKategori = 'KI005'): Aset
{
    $ruang = AsetRuang::query()->firstOrCreate(
        ['kode_ruang' => 'IT'],
        ['nama_ruang' => 'Ruang IT']
    );

    $barang = AsetBarang::query()->create([
        'kode_barang' => 'PC-'.$kodeAset,
        'nama_barang' => 'Komputer Desktop',
        'id_kategori' => $idKategori,
        'kelas_aset' => 'non_medis',
    ]);

    return Aset::query()->create([
        'kode_aset' => $kodeAset,
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IT',
        'tahun_registrasi' => 2026,
        'no_seri' => 'SN-'.$kodeAset,
        'harga' => 5_000_000,
        'asal_barang' => 'APBD',
        'tanggal_pengadaan' => '2026-01-15',
        'siklus_hidup' => 'aktif',
        'status_fungsi' => 'berfungsi',
        'tingkat_kerusakan' => 'baik',
        'status_ketersediaan' => 'tersedia',
    ]);
}

function seedNonMonitorableAset(string $kodeAset = 'INV-FURN-2026-0001'): Aset
{
    $ruang = AsetRuang::query()->firstOrCreate(
        ['kode_ruang' => 'IT'],
        ['nama_ruang' => 'Ruang IT']
    );

    $barang = AsetBarang::query()->create([
        'kode_barang' => 'FURN-'.$kodeAset,
        'nama_barang' => 'Meja Kerja',
        'id_kategori' => 'KI004',
        'kelas_aset' => 'non_medis',
    ]);

    return Aset::query()->create([
        'kode_aset' => $kodeAset,
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IT',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
        'status_fungsi' => 'berfungsi',
        'tingkat_kerusakan' => 'baik',
        'status_ketersediaan' => 'tersedia',
    ]);
}

it('includes linkable monitorable assets on device show', function () {
    $user = User::factory()->create();
    $device = MonitoredDevice::factory()->online()->create();
    $pc = seedMonitorableAset();
    seedNonMonitorableAset();

    $this->actingAs($user)
        ->get(route('monitoring.show', $device))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('monitoring/show')
            ->has('linkableAssets', 1)
            ->where('linkableAssets.0.id', $pc->id));
});

it('links a monitorable aset to a monitored device', function () {
    $user = User::factory()->create();
    $device = MonitoredDevice::factory()->online()->create(['aset_id' => null]);
    $aset = seedMonitorableAset('INV-IT-2026-0010');

    $this->actingAs($user)
        ->patch(route('monitoring.aset.update', $device), [
            'aset_id' => $aset->id,
        ])
        ->assertRedirect(route('monitoring.show', $device));

    expect($device->fresh()->aset_id)->toBe($aset->id);
});

it('unlinks aset when aset_id is null', function () {
    $user = User::factory()->create();
    $aset = seedMonitorableAset('INV-IT-2026-0011');
    $device = MonitoredDevice::factory()->online()->create(['aset_id' => $aset->id]);

    $this->actingAs($user)
        ->patch(route('monitoring.aset.update', $device), [
            'aset_id' => null,
        ])
        ->assertRedirect(route('monitoring.show', $device));

    expect($device->fresh()->aset_id)->toBeNull();
});

it('rejects linking a non-monitorable aset', function () {
    $user = User::factory()->create();
    $device = MonitoredDevice::factory()->online()->create();
    $furniture = seedNonMonitorableAset('INV-FURN-2026-0099');

    $this->actingAs($user)
        ->patch(route('monitoring.aset.update', $device), [
            'aset_id' => $furniture->id,
        ])
        ->assertSessionHasErrors('aset_id');

    expect($device->fresh()->aset_id)->toBeNull();
});

it('rejects linking an aset already used by another device', function () {
    $user = User::factory()->create();
    $aset = seedMonitorableAset('INV-IT-2026-0020');
    MonitoredDevice::factory()->online()->create(['aset_id' => $aset->id]);
    $other = MonitoredDevice::factory()->online()->create(['aset_id' => null]);

    $this->actingAs($user)
        ->patch(route('monitoring.aset.update', $other), [
            'aset_id' => $aset->id,
        ])
        ->assertSessionHasErrors('aset_id');

    expect($other->fresh()->aset_id)->toBeNull();
});

it('does not auto-link aset on agent register by serial number', function () {
    config(['agent.enrollment_key' => 'test-enrollment-key']);

    $aset = seedMonitorableAset('INV-IT-2026-0030');
    $aset->update(['no_seri' => 'SERIAL-AUTO-NO']);

    $uuid = (string) Illuminate\Support\Str::uuid();

    $this->postJson('/api/agent/register', [
        'enrollment_key' => 'test-enrollment-key',
        'uuid' => $uuid,
        'hostname' => 'pc-no-auto',
        'hardware' => [
            'serial_number' => 'SERIAL-AUTO-NO',
        ],
    ])->assertCreated();

    $device = MonitoredDevice::query()->where('uuid', $uuid)->first();

    expect($device)->not->toBeNull()
        ->and($device->aset_id)->toBeNull();
});
