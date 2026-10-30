<?php

use App\Models\BerkasPegawai;
use App\Models\MasterBerkasPegawai;
use App\Models\Pegawai;
use App\Services\BerkasKepegawaian\BerkasKepegawaianService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function berkasKepegawaianWriteAllowed(): bool
{
    // INSERT privilege probe: 1142 = denied; FK/other errors mean INSERT is allowed.
    try {
        $probe = 'ZW'.substr(uniqid(), -6);
        DB::connection('dbsimrs')->table('master_berkas_pegawai')->insert([
            'kode' => $probe,
            'nama_berkas' => 'Probe',
            'kategori' => 'Tenaga Non Klinis',
            'no_urut' => 999,
        ]);
        DB::connection('dbsimrs')->table('master_berkas_pegawai')->where('kode', $probe)->delete();
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), '1142') || str_contains(strtolower($e->getMessage()), 'command denied')) {
            return false;
        }
    }

    try {
        $probe = 'ZW'.substr(uniqid(), -6);
        DB::connection('dbsimrs')->table('berkas_pegawai')->insert([
            'nik' => '__no_nik__',
            'tgl_uploud' => now()->toDateString(),
            'kode_berkas' => $probe,
            'berkas' => 'pages/berkaspegawai/berkas/probe.pdf',
        ]);
        DB::connection('dbsimrs')->table('berkas_pegawai')->where('kode_berkas', $probe)->delete();

        return true;
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), '1142') || str_contains(strtolower($e->getMessage()), 'command denied')) {
            return false;
        }

        // e.g. FK 1452 — INSERT privilege exists
        return true;
    }
}

beforeEach(function (): void {
    config()->set('services.berkas_pegawai.receiver_url', 'http://webapps.test/receiveberkaspegawai.php');
    config()->set('services.berkas_pegawai.receiver_token', 'secret');
    config()->set('services.berkas_pegawai.public_base_url', 'http://webapps.test/webapps2/penggajian');

    Http::fake([
        'http://webapps.test/receiveberkaspegawai.php' => Http::response([
            'success' => true,
            'filename' => 'ok.pdf',
            'target' => 'berkaspegawai',
        ], 200),
    ]);
});

it('uploads then inserts berkas_pegawai row', function (): void {
    if (! berkasKepegawaianWriteAllowed()) {
        $this->markTestSkipped('dbsimrs user cannot write berkas_pegawai.');
    }

    $suffix = Str::lower(Str::random(6));
    $kode = 'T'.$suffix;
    $nik = Pegawai::query()->where('stts_aktif', 'AKTIF')->value('nik');
    if (! $nik) {
        $this->markTestSkipped('No active pegawai in dbsimrs');
    }

    MasterBerkasPegawai::query()->create([
        'kode' => $kode,
        'nama_berkas' => 'Test Berkas '.$suffix,
        'kategori' => 'Tenaga Non Klinis',
        'no_urut' => 999,
    ]);

    try {
        $file = UploadedFile::fake()->createWithContent('dok.pdf', '%PDF-1.4 test content');
        $service = app(BerkasKepegawaianService::class);
        $row = $service->upload((string) $nik, $kode, $file, now()->toDateString());

        expect($row->nik)->toBe((string) $nik)
            ->and($row->kode_berkas)->toBe($kode)
            ->and($row->berkas)->toStartWith('pages/berkaspegawai/berkas/');
    } finally {
        BerkasPegawai::query()->where('nik', $nik)->where('kode_berkas', $kode)->delete();
        MasterBerkasPegawai::query()->where('kode', $kode)->delete();
    }
});

it('rejects duplicate upload and requires replace', function (): void {
    if (! berkasKepegawaianWriteAllowed()) {
        $this->markTestSkipped('dbsimrs user cannot write berkas_pegawai.');
    }

    $suffix = Str::lower(Str::random(6));
    $kode = 'D'.$suffix;
    $nik = Pegawai::query()->where('stts_aktif', 'AKTIF')->value('nik');
    if (! $nik) {
        $this->markTestSkipped('No active pegawai in dbsimrs');
    }

    MasterBerkasPegawai::query()->create([
        'kode' => $kode,
        'nama_berkas' => 'Dup '.$suffix,
        'kategori' => 'Tenaga Non Klinis',
        'no_urut' => 998,
    ]);

    try {
        $service = app(BerkasKepegawaianService::class);
        $file = UploadedFile::fake()->createWithContent('dok.pdf', '%PDF-1.4 test content');
        $service->upload((string) $nik, $kode, $file, now()->toDateString());

        expect(fn () => $service->upload((string) $nik, $kode, $file, now()->toDateString()))
            ->toThrow(InvalidArgumentException::class);
    } finally {
        BerkasPegawai::query()->where('nik', $nik)->where('kode_berkas', $kode)->delete();
        MasterBerkasPegawai::query()->where('kode', $kode)->delete();
    }
});

it('replaces file then updates db then deletes old remote', function (): void {
    if (! berkasKepegawaianWriteAllowed()) {
        $this->markTestSkipped('dbsimrs user cannot write berkas_pegawai.');
    }

    Http::fake([
        'http://webapps.test/receiveberkaspegawai.php' => Http::sequence()
            ->push(['success' => true, 'filename' => 'new.pdf'], 200)
            ->push(['success' => true, 'filename' => 'old.pdf'], 200),
    ]);

    $suffix = Str::lower(Str::random(6));
    $kode = 'R'.$suffix;
    $nik = Pegawai::query()->where('stts_aktif', 'AKTIF')->value('nik');
    if (! $nik) {
        $this->markTestSkipped('No active pegawai in dbsimrs');
    }

    MasterBerkasPegawai::query()->create([
        'kode' => $kode,
        'nama_berkas' => 'Replace '.$suffix,
        'kategori' => 'Tenaga Non Klinis',
        'no_urut' => 997,
    ]);

    BerkasPegawai::query()->create([
        'nik' => $nik,
        'tgl_uploud' => now()->toDateString(),
        'kode_berkas' => $kode,
        'berkas' => 'pages/berkaspegawai/berkas/old.pdf',
    ]);

    try {
        $file = UploadedFile::fake()->createWithContent('baru.pdf', '%PDF-1.4 baru content');
        $row = app(BerkasKepegawaianService::class)->replace((string) $nik, $kode, $file, now()->toDateString());

        expect($row->berkas)->not->toEndWith('old.pdf');
        Http::assertSentCount(2);
    } finally {
        BerkasPegawai::query()->where('nik', $nik)->where('kode_berkas', $kode)->delete();
        MasterBerkasPegawai::query()->where('kode', $kode)->delete();
    }
});

it('deletes remote then deletes db row', function (): void {
    if (! berkasKepegawaianWriteAllowed()) {
        $this->markTestSkipped('dbsimrs user cannot write berkas_pegawai.');
    }

    $suffix = Str::lower(Str::random(6));
    $kode = 'X'.$suffix;
    $nik = Pegawai::query()->where('stts_aktif', 'AKTIF')->value('nik');
    if (! $nik) {
        $this->markTestSkipped('No active pegawai in dbsimrs');
    }

    MasterBerkasPegawai::query()->create([
        'kode' => $kode,
        'nama_berkas' => 'Delete '.$suffix,
        'kategori' => 'Tenaga Non Klinis',
        'no_urut' => 996,
    ]);
    BerkasPegawai::query()->create([
        'nik' => $nik,
        'tgl_uploud' => now()->toDateString(),
        'kode_berkas' => $kode,
        'berkas' => 'pages/berkaspegawai/berkas/del_'.$suffix.'.pdf',
    ]);

    try {
        app(BerkasKepegawaianService::class)->delete((string) $nik, $kode);

        expect(BerkasPegawai::query()->where('nik', $nik)->where('kode_berkas', $kode)->exists())->toBeFalse();
    } finally {
        BerkasPegawai::query()->where('nik', $nik)->where('kode_berkas', $kode)->delete();
        MasterBerkasPegawai::query()->where('kode', $kode)->delete();
    }
});

it('compensates by deleting remote when db insert fails', function (): void {
    Http::fake([
        'http://webapps.test/receiveberkaspegawai.php' => Http::sequence()
            ->push(['success' => true, 'filename' => 'orphan.pdf'], 200)
            ->push(['success' => true, 'filename' => 'orphan.pdf'], 200),
    ]);

    $file = UploadedFile::fake()->createWithContent('dok.pdf', '%PDF-1.4 orphan content');
    $service = app(BerkasKepegawaianService::class);

    expect(fn () => $service->upload('__nik_tidak_ada__', '__kode_tidak_ada__', $file, now()->toDateString()))
        ->toThrow(RuntimeException::class, 'Gagal menyimpan data berkas ke database.');

    Http::assertSentCount(2);
});

it('compensates by deleting new remote when db update fails on replace', function (): void {
    if (! berkasKepegawaianWriteAllowed()) {
        $this->markTestSkipped('dbsimrs user cannot write berkas_pegawai.');
    }

    Http::fake([
        'http://webapps.test/receiveberkaspegawai.php' => Http::sequence()
            ->push(['success' => true, 'filename' => 'new.pdf'], 200)
            ->push(['success' => true, 'filename' => 'new.pdf'], 200),
    ]);

    $suffix = Str::lower(Str::random(6));
    $kode = 'C'.$suffix;
    $nik = Pegawai::query()->where('stts_aktif', 'AKTIF')->value('nik');
    if (! $nik) {
        $this->markTestSkipped('No active pegawai in dbsimrs');
    }

    MasterBerkasPegawai::query()->create([
        'kode' => $kode,
        'nama_berkas' => 'Compensate '.$suffix,
        'kategori' => 'Tenaga Non Klinis',
        'no_urut' => 995,
    ]);

    BerkasPegawai::query()->create([
        'nik' => $nik,
        'tgl_uploud' => now()->toDateString(),
        'kode_berkas' => $kode,
        'berkas' => 'pages/berkaspegawai/berkas/old_'.$suffix.'.pdf',
    ]);

    $connection = DB::connection('dbsimrs');
    $connection->beforeExecuting(function (string $query): void {
        if (str_contains(strtolower($query), 'update') && str_contains(strtolower($query), 'berkas_pegawai')) {
            throw new RuntimeException('Simulated DB update failure.');
        }
    });

    try {
        $file = UploadedFile::fake()->createWithContent('baru.pdf', '%PDF-1.4 baru content');

        expect(fn () => app(BerkasKepegawaianService::class)->replace((string) $nik, $kode, $file, now()->toDateString()))
            ->toThrow(RuntimeException::class, 'Gagal memperbarui data berkas di database.');

        Http::assertSentCount(2);

        $row = BerkasPegawai::query()->where('nik', $nik)->where('kode_berkas', $kode)->first();
        expect($row)->not->toBeNull()
            ->and($row->berkas)->toEndWith('old_'.$suffix.'.pdf');
    } finally {
        $callbacks = new ReflectionProperty($connection, 'beforeExecutingCallbacks');
        $callbacks->setValue($connection, []);

        BerkasPegawai::query()->where('nik', $nik)->where('kode_berkas', $kode)->delete();
        MasterBerkasPegawai::query()->where('kode', $kode)->delete();
    }
});
