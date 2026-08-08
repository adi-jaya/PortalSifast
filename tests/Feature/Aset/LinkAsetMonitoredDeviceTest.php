<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetRuang;
use App\Models\MonitoredDevice;
use App\Models\User;

function seedAsetWithKategori(string $kodeAset, string $idKategori): Aset
{
    $ruang = AsetRuang::query()->firstOrCreate(
        ['kode_ruang' => 'ITMON'],
        ['nama_ruang' => 'IT Monitoring']
    );

    $barang = AsetBarang::query()->create([
        'kode_barang' => 'BRG-'.$kodeAset,
        'nama_barang' => 'Unit '.$kodeAset,
        'id_kategori' => $idKategori,
        'kelas_aset' => 'non_medis',
    ]);

    return Aset::query()->create([
        'kode_aset' => $kodeAset,
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'ITMON',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
        'status_fungsi' => 'berfungsi',
        'tingkat_kerusakan' => 'baik',
        'status_ketersediaan' => 'tersedia',
    ]);
}

it('exposes linkable devices on monitorable aset show', function () {
    $user = User::factory()->create();
    $aset = seedAsetWithKategori('INV-ITMON-2026-0001', 'KI005');
    MonitoredDevice::factory()->online()->create([
        'hostname' => 'pc-free-01',
        'aset_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('aset.show', $aset))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('aset/show')
            ->where('canLinkMonitoring', true)
            ->has('linkableDevices', 1)
            ->where('linkableDevices.0.label', fn ($label) => str_contains((string) $label, 'pc-free-01')));
});

it('links a free monitored device from aset detail', function () {
    $user = User::factory()->create();
    $aset = seedAsetWithKategori('INV-ITMON-2026-0002', 'KI012');
    $device = MonitoredDevice::factory()->online()->create([
        'hostname' => 'laptop-ward',
        'aset_id' => null,
    ]);

    $this->actingAs($user)
        ->patch(route('aset.monitoring.update', $aset), [
            'monitored_device_id' => $device->id,
        ])
        ->assertRedirect(route('aset.show', $aset));

    expect($device->fresh()->aset_id)->toBe($aset->id);
});

it('unlinks monitored device from aset detail', function () {
    $user = User::factory()->create();
    $aset = seedAsetWithKategori('INV-ITMON-2026-0003', 'KI005');
    $device = MonitoredDevice::factory()->online()->create([
        'hostname' => 'pc-bound',
        'aset_id' => $aset->id,
    ]);

    $this->actingAs($user)
        ->patch(route('aset.monitoring.update', $aset), [
            'monitored_device_id' => null,
        ])
        ->assertRedirect(route('aset.show', $aset));

    expect($device->fresh()->aset_id)->toBeNull();
});

it('rejects linking from non-monitorable aset', function () {
    $user = User::factory()->create();
    $aset = seedAsetWithKategori('INV-FURN-2026-0001', 'KI004');
    $device = MonitoredDevice::factory()->online()->create(['aset_id' => null]);

    $this->actingAs($user)
        ->patch(route('aset.monitoring.update', $aset), [
            'monitored_device_id' => $device->id,
        ])
        ->assertSessionHasErrors('monitored_device_id');

    expect($device->fresh()->aset_id)->toBeNull();
});

it('rejects linking a device already bound to another aset', function () {
    $user = User::factory()->create();
    $asetA = seedAsetWithKategori('INV-ITMON-2026-0010', 'KI005');
    $asetB = seedAsetWithKategori('INV-ITMON-2026-0011', 'KI005');
    $device = MonitoredDevice::factory()->online()->create(['aset_id' => $asetA->id]);

    $this->actingAs($user)
        ->patch(route('aset.monitoring.update', $asetB), [
            'monitored_device_id' => $device->id,
        ])
        ->assertSessionHasErrors('monitored_device_id');

    expect($device->fresh()->aset_id)->toBe($asetA->id);
});

it('replaces previous device when linking another from aset', function () {
    $user = User::factory()->create();
    $aset = seedAsetWithKategori('INV-ITMON-2026-0020', 'MINIPC');
    $old = MonitoredDevice::factory()->online()->create([
        'hostname' => 'old-pc',
        'aset_id' => $aset->id,
    ]);
    $fresh = MonitoredDevice::factory()->online()->create([
        'hostname' => 'new-pc',
        'aset_id' => null,
    ]);

    $this->actingAs($user)
        ->patch(route('aset.monitoring.update', $aset), [
            'monitored_device_id' => $fresh->id,
        ])
        ->assertRedirect(route('aset.show', $aset));

    expect($old->fresh()->aset_id)->toBeNull()
        ->and($fresh->fresh()->aset_id)->toBe($aset->id);
});
