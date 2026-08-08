<?php

use Illuminate\Support\Facades\DB;

test('dokter list returns active doctor profiles when simrs available', function (): void {
    try {
        DB::connection('dbsimrs')->table('jadwal')->limit(1)->get();
    } catch (Throwable) {
        test()->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }

    $response = $this->getJson('/api/dokter?with_foto=0');

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                '*' => ['kdDokter', 'namaDokter', 'sttsAktif', 'isAktif', 'poli', 'jumlahJadwal', 'foto', 'fotoUrl'],
            ],
        ]);

    foreach ($response->json('data') as $row) {
        expect($row['sttsAktif'])->toBe('AKTIF')
            ->and($row['isAktif'])->toBeTrue()
            ->and($row['foto'])->toBeNull();
    }
});

test('dokter list can filter by name when simrs available', function (): void {
    try {
        DB::connection('dbsimrs')->table('dokter')->where('nm_dokter', 'like', '%hud%')->limit(1)->get();
    } catch (Throwable) {
        test()->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }

    $response = $this->getJson('/api/dokter?q=hud&with_foto=0');

    $response->assertSuccessful();

    foreach ($response->json('data') as $row) {
        expect(strtolower($row['namaDokter']))->toContain('hud');
    }
});

test('dokter profile show returns schedule and status when simrs available', function (): void {
    try {
        $kdDokter = DB::connection('dbsimrs')->table('jadwal')->value('kd_dokter');
    } catch (Throwable) {
        test()->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }

    if ($kdDokter === null) {
        test()->markTestSkipped('No jadwal data in SIMRS.');
    }

    $response = $this->getJson('/api/dokter/'.urlencode($kdDokter).'?with_foto=0');

    $response->assertSuccessful()
        ->assertJsonPath('data.kdDokter', $kdDokter)
        ->assertJsonPath('data.sttsAktif', 'AKTIF')
        ->assertJsonPath('data.isAktif', true)
        ->assertJsonStructure([
            'data' => [
                'kdDokter',
                'namaDokter',
                'sttsAktif',
                'isAktif',
                'poli',
                'jadwal',
                'foto',
                'fotoUrl',
            ],
        ]);

    expect($response->json('data.jadwal'))->not->toBeEmpty()
        ->and($response->json('data.foto'))->toBeNull();
});

test('dokter photo endpoint returns image when simrs available', function (): void {
    try {
        $kdDokter = DB::connection('dbsimrs')
            ->table('pegawai')
            ->where('stts_aktif', 'AKTIF')
            ->whereNotNull('photo')
            ->where('photo', '!=', '')
            ->value('nik');
    } catch (Throwable) {
        test()->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }

    if ($kdDokter === null) {
        test()->markTestSkipped('No pegawai photo in SIMRS.');
    }

    $this->get('/api/dokter/'.urlencode($kdDokter).'/foto')
        ->assertSuccessful()
        ->assertHeader('content-type');
});

test('dokter profile show returns not found for unknown doctor', function (): void {
    $this->getJson('/api/dokter/tidak-ada-123')
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});
