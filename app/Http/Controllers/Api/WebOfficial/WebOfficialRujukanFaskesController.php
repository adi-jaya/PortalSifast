<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebOfficial\StoreWebOfficialRujukanFaskesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebOfficialRujukanFaskesController extends Controller
{
    public function store(StoreWebOfficialRujukanFaskesRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $rujukan = retry(3, function () use ($request, $validated) {
                $now = Carbon::now();
                $date = $now->format('Y-m-d');
                $time = $now->format('H:i:s');
                $lockName = 'lock_rujukan_faskes_'.$date;

                $isMysql = DB::connection('dbsimrs')->getDriverName() === 'mysql';
                $lockAcquired = false;

                if ($isMysql) {
                    $lockResult = DB::connection('dbsimrs')->select('SELECT GET_LOCK(?, 5) as acquired', [$lockName]);
                    $lockAcquired = ! empty($lockResult) && (int) ($lockResult[0]->acquired ?? 0) === 1;

                    if (! $lockAcquired) {
                        throw new \RuntimeException("Gagal memperoleh mutex lock rujukan faskes: {$lockName}");
                    }
                }

                try {
                    $dateCompact = str_replace('-', '', $date);
                    $prefix = "RF-{$dateCompact}-";

                    $latest = DB::connection('dbsimrs')
                        ->table('permintaan_rujukan_faskes')
                        ->where('no_rujukan', 'like', $prefix.'%')
                        ->orderByDesc('no_rujukan')
                        ->value('no_rujukan');

                    $nextSeq = 1;
                    if ($latest) {
                        $parts = explode('-', $latest);
                        $lastSeq = (int) end($parts);
                        $nextSeq = $lastSeq + 1;
                    }

                    $noRujukan = $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);

                    DB::connection('dbsimrs')->table('permintaan_rujukan_faskes')->insert([
                        'no_rujukan' => $noRujukan,
                        'tanggal_rujukan' => $date,
                        'jam_rujukan' => $time,
                        'nama_fktp' => $validated['nama_fktp'],
                        'nama_perujuk' => $validated['nama_perujuk'],
                        'no_telp_perujuk' => $validated['no_telp_perujuk'],
                        'nama_pasien' => $validated['nama_pasien'],
                        'no_ktp' => $validated['no_ktp'],
                        'jk' => $validated['jk'],
                        'tgl_lahir' => $validated['tgl_lahir'] ?? null,
                        'umur' => $validated['umur'] ?? null,
                        'no_telp_pasien' => $validated['no_telp_pasien'] ?? null,
                        'diagnosa' => $validated['diagnosa'],
                        'keluhan_utama' => $validated['keluhan_utama'] ?? null,
                        'tanda_vital_kesadaran' => $validated['tanda_vital_kesadaran'] ?? null,
                        'tanda_vital_td' => $validated['tanda_vital_td'] ?? null,
                        'tanda_vital_nadi' => $validated['tanda_vital_nadi'] ?? null,
                        'tanda_vital_rr' => $validated['tanda_vital_rr'] ?? null,
                        'tanda_vital_suhu' => $validated['tanda_vital_suhu'] ?? null,
                        'tanda_vital_spo2' => $validated['tanda_vital_spo2'] ?? null,
                        'tindakan_diberikan' => $validated['tindakan_diberikan'] ?? null,
                        'transportasi' => $validated['transportasi'] ?? null,
                        'kebutuhan_khusus' => $validated['kebutuhan_khusus'] ?? null,
                        'estimasi_tiba' => $validated['estimasi_tiba'] ?? null,
                        'status' => 'Menunggu',
                        'catatan_igd' => null,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    return [
                        'no_rujukan' => $noRujukan,
                        'tanggal_rujukan' => $date,
                        'jam_rujukan' => $time,
                        'nama_pasien' => $validated['nama_pasien'],
                        'nama_fktp' => $validated['nama_fktp'],
                    ];
                } finally {
                    if ($isMysql && $lockAcquired) {
                        DB::connection('dbsimrs')->select('SELECT RELEASE_LOCK(?)', [$lockName]);
                    }
                }
            }, 100);

            return response()->json([
                'success' => true,
                'message' => 'Permintaan rujukan berhasil dicatat.',
                'data' => $rujukan,
            ], 201);
        } catch (Throwable $e) {
            Log::error('Gagal mencatat permintaan rujukan faskes: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada sistem saat mencatat permintaan rujukan.',
            ], 500);
        }
    }
}
