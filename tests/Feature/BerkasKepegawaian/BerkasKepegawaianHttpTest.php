<?php

use App\Models\BerkasPegawai;
use App\Models\MasterBerkasPegawai;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

function berkasKepegawaianHttpWriteAllowed(): bool
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

it('forbids staff without berkas kepegawaian flag from show', function (): void {
    $user = User::factory()->staff()->create([
        'can_access_berkas_kepegawaian' => false,
    ]);

    $nik = Pegawai::query()->where('stts_aktif', 'AKTIF')->value('nik');
    if (! $nik) {
        $this->markTestSkipped('No active pegawai in dbsimrs');
    }

    actingAs($user)->get("/berkas-kepegawaian/{$nik}")->assertForbidden();
});

it('allows admin to open pegawai index', function (): void {
    $admin = User::factory()->admin()->create([
        'can_access_berkas_kepegawaian' => false,
    ]);

    actingAs($admin)
        ->get('/berkas-kepegawaian')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('berkas-kepegawaian/index')
            ->has('pegawai')
            ->has('filters'));
});

it('allows admin to open show for existing aktif nik', function (): void {
    $admin = User::factory()->admin()->create();
    $nik = Pegawai::query()->where('stts_aktif', 'AKTIF')->value('nik');
    if (! $nik) {
        $this->markTestSkipped('No active pegawai in dbsimrs');
    }

    actingAs($admin)
        ->get("/berkas-kepegawaian/{$nik}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('berkas-kepegawaian/show')
            ->has('pegawai')
            ->has('sections')
            ->has('masterOptions')
            ->where('pegawai.nik', (string) $nik));
});

it('rejects png on store via validation', function (): void {
    $admin = User::factory()->admin()->create();
    $nik = Pegawai::query()->where('stts_aktif', 'AKTIF')->value('nik');
    if (! $nik) {
        $this->markTestSkipped('No active pegawai in dbsimrs');
    }

    actingAs($admin)
        ->from("/berkas-kepegawaian/{$nik}")
        ->post("/berkas-kepegawaian/{$nik}", [
            'kode_berkas' => 'XX',
            'tgl_uploud' => now()->toDateString(),
            'dokumen' => UploadedFile::fake()->image('foto.png'),
        ])
        ->assertRedirect("/berkas-kepegawaian/{$nik}")
        ->assertSessionHasErrors('dokumen');
});

it('returns 404 when mutating berkas for missing or non-aktif pegawai', function (): void {
    $admin = User::factory()->admin()->create();
    $file = UploadedFile::fake()->createWithContent('dok.pdf', '%PDF-1.4 test content');

    actingAs($admin)
        ->post('/berkas-kepegawaian/__nik_tidak_ada__', [
            'kode_berkas' => 'XX',
            'tgl_uploud' => now()->toDateString(),
            'dokumen' => $file,
        ])
        ->assertNotFound();

    $nonAktifNik = Pegawai::query()->where('stts_aktif', '!=', 'AKTIF')->value('nik');
    if (! $nonAktifNik) {
        return;
    }

    actingAs($admin)
        ->post("/berkas-kepegawaian/{$nonAktifNik}", [
            'kode_berkas' => 'XX',
            'tgl_uploud' => now()->toDateString(),
            'dokumen' => $file,
        ])
        ->assertNotFound();

    actingAs($admin)
        ->post("/berkas-kepegawaian/{$nonAktifNik}/XX/replace", [
            'tgl_uploud' => now()->toDateString(),
            'dokumen' => $file,
        ])
        ->assertNotFound();

    actingAs($admin)
        ->delete("/berkas-kepegawaian/{$nonAktifNik}/XX")
        ->assertNotFound();
});

it('can store replace and destroy berkas with http fake', function (): void {
    if (! berkasKepegawaianHttpWriteAllowed()) {
        $this->markTestSkipped('dbsimrs user cannot write berkas_pegawai.');
    }

    $admin = User::factory()->admin()->create();
    $suffix = Str::lower(Str::random(6));
    $kode = 'H'.$suffix;
    $nik = Pegawai::query()->where('stts_aktif', 'AKTIF')->value('nik');
    if (! $nik) {
        $this->markTestSkipped('No active pegawai in dbsimrs');
    }

    MasterBerkasPegawai::query()->create([
        'kode' => $kode,
        'nama_berkas' => 'Http Test '.$suffix,
        'kategori' => 'Tenaga Non Klinis',
        'no_urut' => 990,
    ]);

    try {
        $file = UploadedFile::fake()->createWithContent('dok.pdf', '%PDF-1.4 test content');

        actingAs($admin)
            ->from("/berkas-kepegawaian/{$nik}")
            ->post("/berkas-kepegawaian/{$nik}", [
                'kode_berkas' => $kode,
                'tgl_uploud' => now()->toDateString(),
                'dokumen' => $file,
            ])
            ->assertRedirect("/berkas-kepegawaian/{$nik}")
            ->assertSessionHas('success');

        expect(BerkasPegawai::query()->where('nik', $nik)->where('kode_berkas', $kode)->exists())->toBeTrue();

        $replace = UploadedFile::fake()->createWithContent('baru.pdf', '%PDF-1.4 baru content');

        actingAs($admin)
            ->from("/berkas-kepegawaian/{$nik}")
            ->post("/berkas-kepegawaian/{$nik}/{$kode}/replace", [
                'tgl_uploud' => now()->toDateString(),
                'dokumen' => $replace,
            ])
            ->assertRedirect("/berkas-kepegawaian/{$nik}")
            ->assertSessionHas('success');

        actingAs($admin)
            ->from("/berkas-kepegawaian/{$nik}")
            ->delete("/berkas-kepegawaian/{$nik}/{$kode}")
            ->assertRedirect("/berkas-kepegawaian/{$nik}")
            ->assertSessionHas('success');

        expect(BerkasPegawai::query()->where('nik', $nik)->where('kode_berkas', $kode)->exists())->toBeFalse();
    } finally {
        BerkasPegawai::query()->where('nik', $nik)->where('kode_berkas', $kode)->delete();
        MasterBerkasPegawai::query()->where('kode', $kode)->delete();
    }
});
