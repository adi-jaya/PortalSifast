<?php

use App\Support\SimrsDayName;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

test('simrs day name maps iso weekday to uppercase indonesian', function (): void {
    expect(SimrsDayName::fromDate(Carbon::parse('2026-07-06')))->toBe('SENIN')
        ->and(SimrsDayName::fromDate(Carbon::parse('2026-07-12')))->toBe('MINGGU');
});

test('simrs day name normalizes aliases', function (): void {
    expect(SimrsDayName::normalize('senin'))->toBe('SENIN')
        ->and(SimrsDayName::normalize('Selasa'))->toBe('SELASA');
});

test('invalid hari returns validation error', function (): void {
    $this->getJson('/api/jadwal-dokter?hari=invalid')
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'INVALID_DAY');
});

test('jadwal dokter endpoint returns schedules for date when simrs available', function (): void {
    try {
        DB::connection('dbsimrs')->table('jadwal')->limit(1)->get();
    } catch (Throwable) {
        test()->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }

    $response = $this->getJson('/api/jadwal-dokter?tanggal=2026-07-06');

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.hariKey', 'SENIN')
        ->assertJsonPath('meta.tanggal', '2026-07-06')
        ->assertJsonStructure([
            'data' => [
                '*' => ['kdDokter', 'namaDokter', 'kdPoli', 'namaPoli', 'hari', 'hariKey', 'jamMulai', 'jamSelesai', 'kuota', 'foto', 'fotoUrl'],
            ],
        ]);

    expect($response->json('data'))->toBeArray();

    $first = $response->json('data.0');
    expect($first['foto'])->toBeNull();

    if (filled($first['fotoUrl'] ?? null)) {
        expect($first['fotoUrl'])->toStartWith('/api/dokter/');
    }
});

test('jadwal dokter can include inline foto when requested', function (): void {
    try {
        DB::connection('dbsimrs')->table('jadwal')->limit(1)->get();
    } catch (Throwable) {
        test()->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }

    $response = $this->getJson('/api/jadwal-dokter?tanggal=2026-07-06&with_foto=1');

    $response->assertSuccessful();

    $withPhoto = collect($response->json('data'))->first(fn (array $row): bool => filled($row['foto'] ?? null));
    if ($withPhoto !== null) {
        expect($withPhoto['foto'])->toStartWith('data:image/');
    }
});

test('jadwal dokter can filter by kd_poli when simrs available', function (): void {
    try {
        $sample = DB::connection('dbsimrs')->table('jadwal')->value('kd_poli');
    } catch (Throwable) {
        test()->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }

    if ($sample === null) {
        test()->markTestSkipped('No jadwal data in SIMRS.');
    }

    $response = $this->getJson('/api/jadwal-dokter?tanggal=2026-07-06&kd_poli='.$sample);

    $response->assertSuccessful();

    foreach ($response->json('data') as $row) {
        expect($row['kdPoli'])->toBe($sample);
    }
});

test('jadwal dokter can search by doctor name when simrs available', function (): void {
    try {
        $sampleName = DB::connection('dbsimrs')
            ->table('dokter')
            ->where('nm_dokter', 'like', '%hud%')
            ->value('nm_dokter');
    } catch (Throwable) {
        test()->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }

    if ($sampleName === null) {
        test()->markTestSkipped('No matching doctor in SIMRS.');
    }

    $response = $this->getJson('/api/jadwal-dokter?q=hud&with_foto=0');

    $response->assertSuccessful()
        ->assertJsonPath('meta.semuaHari', true)
        ->assertJsonPath('meta.q', 'hud');

    foreach ($response->json('data') as $row) {
        expect(strtolower($row['namaDokter']))->toContain('hud');
    }
});

test('jadwal dokter can search by poli name when simrs available', function (): void {
    try {
        DB::connection('dbsimrs')->table('jadwal')->limit(1)->get();
    } catch (Throwable) {
        test()->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }

    $response = $this->getJson('/api/jadwal-dokter?hari=Senin&poli=Kandungan&with_foto=0');

    $response->assertSuccessful()
        ->assertJsonPath('meta.hariKey', 'SENIN')
        ->assertJsonPath('meta.poli', 'Kandungan');

    foreach ($response->json('data') as $row) {
        expect(strtolower($row['namaPoli']))->toContain('kandungan');
    }
});

test('jadwal dokter poliklinik list returns options when simrs available', function (): void {
    try {
        DB::connection('dbsimrs')->table('jadwal')->limit(1)->get();
    } catch (Throwable) {
        test()->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }

    $this->getJson('/api/jadwal-dokter/poliklinik')
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['*' => ['kdPoli', 'namaPoli']]]);
});
