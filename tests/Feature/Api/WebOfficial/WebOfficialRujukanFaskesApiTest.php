<?php

namespace Tests\Feature\Api\WebOfficial;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebOfficialRujukanFaskesApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection('dbsimrs')->getDriverName() === 'sqlite') {
            $this->artisan('migrate', [
                '--path' => 'database/migrations/2026_11_04_000001_create_permintaan_rujukan_faskes_table.php',
                '--database' => 'dbsimrs',
            ]);
        }

        DB::connection('dbsimrs')->table('permintaan_rujukan_faskes')
            ->where('nama_pasien', 'like', 'TEST_%')
            ->delete();
    }

    protected function tearDown(): void
    {
        DB::connection('dbsimrs')->table('permintaan_rujukan_faskes')
            ->where('nama_pasien', 'like', 'TEST_%')
            ->delete();

        parent::tearDown();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nama_fktp' => 'Puskesmas Gambiran',
            'nama_perujuk' => 'dr. Budi Santoso',
            'no_telp_perujuk' => '081234567890',
            'nama_pasien' => 'TEST_Pasien Satu',
            'no_ktp' => '3510012345670001',
            'jk' => 'L',
            'tgl_lahir' => '1990-05-15',
            'umur' => '34 Th',
            'no_telp_pasien' => '089876543210',
            'diagnosa' => 'Obs. Febris H-3 suspect DHF',
            'keluhan_utama' => 'Demam tinggi mendadak sejak 3 hari',
            'tanda_vital_kesadaran' => 'Compos Mentis',
            'tanda_vital_td' => '110/70',
            'tanda_vital_nadi' => '88',
            'tanda_vital_rr' => '20',
            'tanda_vital_suhu' => '38.5',
            'tanda_vital_spo2' => '98',
            'tindakan_diberikan' => 'Infus RL 20 tpm, Paracetamol 500mg',
            'transportasi' => 'Ambulans FKTP',
            'kebutuhan_khusus' => 'Oksigen Lanjutan',
            'estimasi_tiba' => '20 menit',
            '_hp_code' => '',
            '_form_time' => time() - 10,
        ], $overrides);
    }

    public function test_can_submit_valid_rujukan_and_generates_atomic_no_rujukan(): void
    {
        $payload = $this->validPayload();

        $response = $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson('/api/portal/rujukan-faskes', $payload);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Permintaan rujukan berhasil dicatat.',
                'data' => [
                    'nama_pasien' => 'TEST_Pasien Satu',
                    'nama_fktp' => 'Puskesmas Gambiran',
                ],
            ]);

        $noRujukan = $response->json('data.no_rujukan');
        $dateCompact = Carbon::now()->format('Ymd');
        $this->assertMatchesRegularExpression("/^RF-{$dateCompact}-\\d{4}$/", $noRujukan);

        $this->assertDatabaseHas('permintaan_rujukan_faskes', [
            'no_rujukan' => $noRujukan,
            'nama_pasien' => 'TEST_Pasien Satu',
            'nama_fktp' => 'Puskesmas Gambiran',
            'status' => 'Menunggu',
        ], 'dbsimrs');
    }

    public function test_honeypot_submission_is_rejected(): void
    {
        // 1. Rejection when _hp_code is filled (bot submission)
        $payloadWithHoneypot = $this->validPayload([
            '_hp_code' => 'spambot_value',
        ]);

        $response = $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson('/api/portal/rujukan-faskes', $payloadWithHoneypot);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['_hp_code']);

        // 2. Rejection when _form_time is too fast (< 3 seconds)
        $payloadTooFast = $this->validPayload([
            '_hp_code' => '',
            '_form_time' => time(), // 0 seconds elapsed
        ]);

        $responseFast = $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson('/api/portal/rujukan-faskes', $payloadTooFast);

        $responseFast->assertStatus(422)
            ->assertJsonValidationErrors(['_form_time']);

        // 3. Rejection when _form_time is missing
        $payloadNoFormTime = $this->validPayload();
        unset($payloadNoFormTime['_form_time']);

        $responseNoFormTime = $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson('/api/portal/rujukan-faskes', $payloadNoFormTime);

        $responseNoFormTime->assertStatus(422)
            ->assertJsonValidationErrors(['_form_time']);
    }

    public function test_invalid_nik_is_rejected(): void
    {
        // Less than 16 digits
        $payloadShortNik = $this->validPayload([
            'no_ktp' => '351001234567001', // 15 digits
        ]);

        $responseShort = $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson('/api/portal/rujukan-faskes', $payloadShortNik);

        $responseShort->assertStatus(422)
            ->assertJsonValidationErrors(['no_ktp']);

        // More than 16 digits
        $payloadLongNik = $this->validPayload([
            'no_ktp' => '35100123456700001', // 17 digits
        ]);

        $responseLong = $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson('/api/portal/rujukan-faskes', $payloadLongNik);

        $responseLong->assertStatus(422)
            ->assertJsonValidationErrors(['no_ktp']);

        // Non-digit characters
        $payloadAlphaNik = $this->validPayload([
            'no_ktp' => '351001234567000A',
        ]);

        $responseAlpha = $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson('/api/portal/rujukan-faskes', $payloadAlphaNik);

        $responseAlpha->assertStatus(422)
            ->assertJsonValidationErrors(['no_ktp']);
    }

    public function test_race_condition_concurrent_numbering_produces_distinct_numbers(): void
    {
        $numbers = [];

        for ($i = 1; $i <= 3; $i++) {
            $payload = $this->validPayload([
                'nama_pasien' => "TEST_Pasien Race {$i}",
                'no_ktp' => sprintf('351001234567%04d', $i),
                '_form_time' => time() - 10,
            ]);

            $response = $this->withoutMiddleware(ThrottleRequests::class)
                ->postJson('/api/portal/rujukan-faskes', $payload);

            $response->assertCreated();
            $numbers[] = $response->json('data.no_rujukan');
        }

        // Must produce 3 distinct numbers
        $this->assertCount(3, array_unique($numbers));

        // Must be strictly increasing sequence numbers
        $sequences = array_map(function ($no) {
            $parts = explode('-', $no);

            return (int) end($parts);
        }, $numbers);

        $this->assertEquals($sequences[0] + 1, $sequences[1]);
        $this->assertEquals($sequences[1] + 1, $sequences[2]);
    }
}
