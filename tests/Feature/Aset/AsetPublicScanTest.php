<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('allows guest to open public qr scan page without login', function () {
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IGD', 'nama_ruang' => 'IGD']);
    $barang = AsetBarang::query()->create([
        'kode_barang' => 'MON01',
        'nama_barang' => 'Patient Monitor',
        'kelas_aset' => 'medis',
        'wajib_kalibrasi' => true,
    ]);
    $aset = Aset::query()->create([
        'kode_aset' => 'INV-IGD-2026-0007',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IGD',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
        'status_fungsi' => 'berfungsi',
        'tingkat_kerusakan' => 'baik',
        'no_seri' => 'SN-PUBLIC-1',
    ]);

    get(route('aset.public.show', $aset))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('aset/public-scan')
            ->where('aset.kode_aset', 'INV-IGD-2026-0007')
            ->where('aset.nama_barang', 'Patient Monitor')
            ->where('aset.nama_ruang', 'IGD')
            ->where('aset.no_seri', 'SN-PUBLIC-1')
            ->where('aset.wajib_kalibrasi', true)
            ->where('canManage', false)
            ->where('authenticated', false)
            ->where('ticketCreateUrl', '/tickets/create?asset_id='.$aset->id)
            ->has('riwayat.peminjaman')
            ->has('riwayat.mutasi')
            ->has('riwayat.tiket')
            ->missing('aset.harga'));
});

it('shows manage link when authenticated user scans qr page', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'OK', 'nama_ruang' => 'OK']);
    $barang = AsetBarang::query()->create(['kode_barang' => 'B1', 'nama_barang' => 'Bed']);
    $aset = Aset::query()->create([
        'kode_aset' => 'INV-OK-2026-0001',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'OK',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
    ]);

    actingAs($user)
        ->get(route('aset.public.show', $aset))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canManage', true)
            ->where('authenticated', true)
            ->where('manageUrl', route('aset.show', $aset)));
});

it('hides peminjam name and ticket title from guests but shows to authenticated users', function () {
    $user = User::factory()->create(['name' => 'Petugas Scan']);
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IGD', 'nama_ruang' => 'IGD']);
    $tujuan = AsetRuang::query()->create(['kode_ruang' => 'OK', 'nama_ruang' => 'OK']);
    $barang = AsetBarang::query()->create(['kode_barang' => 'MONX', 'nama_barang' => 'Monitor']);
    $aset = Aset::query()->create([
        'kode_aset' => 'INV-IGD-2026-0088',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IGD',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
        'status_ketersediaan' => 'tersedia',
    ]);

    $pinjam = app(\App\Services\Inventaris\BuatPeminjamanAset::class)->handle(
        $aset,
        $user,
        $user->id,
        null,
        now(),
        null,
        null,
    );

    // Restore for mutasi test path — pinjam marks dipinjam; kembalikan first
    app(\App\Services\Inventaris\KembalikanPeminjamanAset::class)->handle($pinjam, $user, null);

    app(\App\Services\Inventaris\BuatMutasiLokasiAset::class)->handle(
        $aset->fresh(),
        $user,
        $tujuan->id,
        $user->id,
        null,
        now(),
        'Pindah OK',
    );

    $type = \App\Models\TicketType::factory()->create();
    $priority = \App\Models\TicketPriority::factory()->create();
    $status = \App\Models\TicketStatus::factory()->asNew()->create();
    $ticket = \App\Models\Ticket::factory()->create([
        'ticket_type_id' => $type->id,
        'ticket_priority_id' => $priority->id,
        'ticket_status_id' => $status->id,
        'requester_id' => $user->id,
        'asset_id' => $aset->id,
        'title' => 'Rahasia Judul Tiket',
        'dep_id' => 'IT',
    ]);

    get(route('aset.public.show', $aset))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('authenticated', false)
            ->has('riwayat.peminjaman', 1)
            ->where('riwayat.peminjaman.0.nomor', $pinjam->nomor)
            ->missing('riwayat.peminjaman.0.peminjam_label')
            ->has('riwayat.mutasi', 1)
            ->missing('riwayat.mutasi.0.penerima_label')
            ->has('riwayat.tiket', 1)
            ->where('riwayat.tiket.0.ticket_number', $ticket->ticket_number)
            ->missing('riwayat.tiket.0.title'));

    actingAs($user)
        ->get(route('aset.public.show', $aset))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('authenticated', true)
            ->where('riwayat.peminjaman.0.peminjam_label', 'Petugas Scan')
            ->where('riwayat.mutasi.0.penerima_label', 'Petugas Scan')
            ->where('riwayat.tiket.0.title', 'Rahasia Judul Tiket'));
});

it('embeds public scan url in aset qr label print', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'LAB', 'nama_ruang' => 'Lab']);
    $barang = AsetBarang::query()->create(['kode_barang' => 'B2', 'nama_barang' => 'Analyzer']);
    $aset = Aset::query()->create([
        'kode_aset' => 'INV-LAB-2026-0002',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'LAB',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
    ]);

    $this->mock(\App\Services\InventarisQrCodeGenerator::class, function ($mock) use ($aset) {
        $mock->shouldReceive('svg')
            ->once()
            ->withArgs(fn (string $url) => str_contains($url, '/q/'.$aset->kode_aset))
            ->andReturn('<svg data-test="qr"></svg>');
    });

    actingAs($user)
        ->get(route('aset.label-print', $aset))
        ->assertOk()
        ->assertSee($aset->kode_aset, false)
        ->assertSee('data-test="qr"', false);
});
