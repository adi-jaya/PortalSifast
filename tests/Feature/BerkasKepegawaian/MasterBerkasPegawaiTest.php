<?php

use App\Models\BerkasPegawai;
use App\Models\MasterBerkasPegawai;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

function masterBerkasPegawaiWriteAllowed(): bool
{
    try {
        $probe = 'ZW'.substr(uniqid(), -6);
        DB::connection('dbsimrs')->table('master_berkas_pegawai')->insert([
            'kode' => $probe,
            'nama_berkas' => 'Probe',
            'kategori' => 'Tenaga Non Klinis',
            'no_urut' => 999,
        ]);
        DB::connection('dbsimrs')->table('master_berkas_pegawai')->where('kode', $probe)->delete();

        return true;
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), '1142') || str_contains(strtolower($e->getMessage()), 'command denied')) {
            return false;
        }

        return true;
    }
}

it('forbids staff without berkas kepegawaian flag from master index', function (): void {
    $user = User::factory()->staff()->create([
        'can_access_berkas_kepegawaian' => false,
    ]);

    actingAs($user)->get('/berkas-kepegawaian/master')->assertForbidden();
});

it('allows admin to open master jenis berkas index', function (): void {
    $admin = User::factory()->admin()->create([
        'can_access_berkas_kepegawaian' => false,
    ]);

    actingAs($admin)
        ->get('/berkas-kepegawaian/master')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('berkas-kepegawaian/master/index')
            ->has('items')
            ->has('kategoriOptions')
            ->has('filters'));
});

it('can create update and delete unused master jenis berkas', function (): void {
    if (! masterBerkasPegawaiWriteAllowed()) {
        $this->markTestSkipped('dbsimrs user cannot write master_berkas_pegawai.');
    }

    $admin = User::factory()->admin()->create();
    $suffix = Str::lower(Str::random(6));
    $kode = 'T'.$suffix;

    try {
        actingAs($admin)
            ->post('/berkas-kepegawaian/master', [
                'kode' => $kode,
                'nama_berkas' => 'Test Berkas '.$suffix,
                'kategori' => 'Tenaga Non Klinis',
                'no_urut' => 900,
            ])
            ->assertRedirect(route('berkas-kepegawaian.master.index'))
            ->assertSessionHas('success');

        $master = MasterBerkasPegawai::query()->find($kode);
        expect($master)->not->toBeNull()
            ->and($master->nama_berkas)->toBe('Test Berkas '.$suffix)
            ->and((int) $master->no_urut)->toBe(900);

        actingAs($admin)
            ->put("/berkas-kepegawaian/master/{$kode}", [
                'nama_berkas' => 'Updated Berkas '.$suffix,
                'kategori' => 'Tenaga Non Klinis',
                'no_urut' => 901,
            ])
            ->assertRedirect(route('berkas-kepegawaian.master.index'))
            ->assertSessionHas('success');

        $master->refresh();
        expect($master->nama_berkas)->toBe('Updated Berkas '.$suffix)
            ->and((int) $master->no_urut)->toBe(901);

        actingAs($admin)
            ->delete("/berkas-kepegawaian/master/{$kode}")
            ->assertRedirect(route('berkas-kepegawaian.master.index'))
            ->assertSessionHas('success');

        expect(MasterBerkasPegawai::query()->find($kode))->toBeNull();
    } finally {
        MasterBerkasPegawai::query()->where('kode', $kode)->delete();
    }
});

it('cannot delete master still used by berkas pegawai', function (): void {
    if (! masterBerkasPegawaiWriteAllowed()) {
        $this->markTestSkipped('dbsimrs user cannot write master_berkas_pegawai.');
    }

    $admin = User::factory()->admin()->create();
    $suffix = Str::lower(Str::random(6));
    $kode = 'U'.$suffix;
    $nik = Pegawai::query()->where('stts_aktif', 'AKTIF')->value('nik');

    if (! $nik) {
        $this->markTestSkipped('No active pegawai in dbsimrs');
    }

    MasterBerkasPegawai::query()->create([
        'kode' => $kode,
        'nama_berkas' => 'Used Berkas '.$suffix,
        'kategori' => 'Tenaga Non Klinis',
        'no_urut' => 998,
    ]);

    try {
        try {
            BerkasPegawai::query()->create([
                'nik' => (string) $nik,
                'tgl_uploud' => now()->toDateString(),
                'kode_berkas' => $kode,
                'berkas' => 'pages/berkaspegawai/berkas/probe-used.pdf',
            ]);
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), '1142') || str_contains(strtolower($e->getMessage()), 'command denied')) {
                $this->markTestSkipped('dbsimrs user cannot write berkas_pegawai.');
            }

            throw $e;
        }

        actingAs($admin)
            ->from('/berkas-kepegawaian/master')
            ->delete("/berkas-kepegawaian/master/{$kode}")
            ->assertRedirect('/berkas-kepegawaian/master')
            ->assertSessionHas('error');

        expect(MasterBerkasPegawai::query()->find($kode))->not->toBeNull();
    } finally {
        BerkasPegawai::query()->where('nik', $nik)->where('kode_berkas', $kode)->delete();
        MasterBerkasPegawai::query()->where('kode', $kode)->delete();
    }
});
