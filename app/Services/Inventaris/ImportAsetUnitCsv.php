<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AsetAspakAlat;
use App\Models\AsetJenis;
use App\Models\AsetMerk;
use App\Models\AsetNonAlkes;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportAsetUnitCsv
{
    public const MAX_ROWS = 500;

    public const SESSION_KEY = 'aset_unit_csv_import';

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return [
            'kelas_aset',
            'kode_non_alkes',
            'kode_aspak',
            'kode_ruang',
            'tahun_registrasi',
            'no_seri',
            'nama_barang',
            'nama_merk',
            'nama_tipe',
            'harga',
            'asal_barang',
            'tanggal_pengadaan',
            'status_fungsi',
            'tingkat_kerusakan',
        ];
    }

    /**
     * @return list<list<string>>
     */
    public function exampleRows(): array
    {
        return [
            [
                'non_medis',
                '10.02.002',
                '',
                'IGD01',
                '2026',
                'SN-IGD-01',
                'Lenovo Ideapad 3',
                'Lenovo',
                'Ideapad 3',
                '6000000',
                'Beli',
                '2026-01-15',
                'berfungsi',
                'baik',
            ],
            [
                'non_medis',
                '10.02.002',
                '',
                'POL01',
                '2026',
                'SN-POL-01',
                'Lenovo Ideapad 3',
                'Lenovo',
                'Ideapad 3',
                '6000000',
                'Beli',
                '2026-01-15',
                'berfungsi',
                'baik',
            ],
        ];
    }

    /**
     * @return array{
     *     token: string,
     *     error_count: int,
     *     ok_count: int,
     *     rows: list<array{line: int, ok: bool, errors: list<string>, summary: array<string, string|null>}>,
     *     payloads: list<array<string, mixed>>
     * }
     */
    public function preview(UploadedFile|string $file): array
    {
        $rows = $this->readCsv($file);
        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'CSV kosong atau tidak valid.',
            ]);
        }

        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages([
                'file' => 'Maksimal '.self::MAX_ROWS.' baris data per file.',
            ]);
        }

        $previewRows = [];
        $payloads = [];
        $serialsInFile = [];
        $errorCount = 0;

        foreach ($rows as $index => $row) {
            $line = $index + 2; // header = line 1
            $errors = $this->validateRow($row, $serialsInFile);
            $ok = $errors === [];
            if (! $ok) {
                $errorCount++;
            } else {
                $serial = trim((string) ($row['no_seri'] ?? ''));
                if ($serial !== '') {
                    $serialsInFile[mb_strtolower($serial)] = $line;
                }
                // Raw CSV row — resolve merk/tipe/IDs only on commit
                $payloads[] = $row;
            }

            $previewRows[] = [
                'line' => $line,
                'ok' => $ok,
                'errors' => $errors,
                'summary' => [
                    'kelas_aset' => $row['kelas_aset'] ?? null,
                    'kode_katalog' => ($row['kelas_aset'] ?? '') === 'medis'
                        ? ($row['kode_aspak'] ?? null)
                        : ($row['kode_non_alkes'] ?? null),
                    'kode_ruang' => $row['kode_ruang'] ?? null,
                    'no_seri' => $row['no_seri'] ?? null,
                    'nama_barang' => $row['nama_barang'] ?? null,
                ],
            ];
        }

        return [
            'token' => (string) Str::uuid(),
            'error_count' => $errorCount,
            'ok_count' => count($rows) - $errorCount,
            'rows' => $previewRows,
            'payloads' => $payloads,
        ];
    }

    /**
     * @param  array{token: string, error_count: int, payloads: list<array<string, mixed>>}  $preview
     * @return list<Aset>
     */
    public function commit(array $preview, ?User $user = null): array
    {
        if (($preview['error_count'] ?? 1) > 0) {
            throw ValidationException::withMessages([
                'file' => 'Masih ada baris error. Perbaiki CSV lalu preview ulang.',
            ]);
        }

        $payloads = $preview['payloads'] ?? [];
        if ($payloads === []) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ada baris valid untuk diimpor.',
            ]);
        }

        return DB::transaction(function () use ($payloads, $user) {
            $created = [];
            $batch = app(BuatAsetBatch::class);

            foreach ($payloads as $row) {
                $units = $batch->buat($this->buildPayload($row), $user);
                array_push($created, ...$units);
            }

            return $created;
        });
    }

    /**
     * @return list<array<string, string>>
     */
    private function readCsv(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        if ($path === false || ! is_file($path)) {
            return [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return [];
        }

        // Strip UTF-8 BOM from first header cell
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0];
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $required = ['kelas_aset', 'kode_ruang', 'tahun_registrasi'];
        foreach ($required as $col) {
            if (! in_array($col, $header, true)) {
                fclose($handle);
                throw ValidationException::withMessages([
                    'file' => "Header CSV wajib mengandung kolom: {$col}.",
                ]);
            }
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === 1 && ($data[0] === null || trim((string) $data[0]) === '')) {
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = trim((string) ($data[$i] ?? ''));
            }
            if ($this->rowIsEmpty($row)) {
                continue;
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, int>  $serialsInFile
     * @return list<string>
     */
    private function validateRow(array $row, array $serialsInFile): array
    {
        $errors = [];
        $kelas = $row['kelas_aset'] ?? '';

        if (! in_array($kelas, ['medis', 'non_medis'], true)) {
            $errors[] = 'kelas_aset harus medis atau non_medis.';
        }

        if ($kelas === 'medis') {
            $code = $row['kode_aspak'] ?? '';
            if ($code === '') {
                $errors[] = 'kode_aspak wajib untuk kelas medis.';
            } else {
                $aspak = $this->findAspakLeaf($code);
                if ($aspak === null) {
                    $errors[] = "kode_aspak \"{$code}\" tidak ditemukan atau bukan leaf.";
                }
            }
        }

        if ($kelas === 'non_medis') {
            $code = $row['kode_non_alkes'] ?? '';
            if ($code === '') {
                $errors[] = 'kode_non_alkes wajib untuk kelas non_medis.';
            } else {
                $nonAlkes = $this->findNonAlkesLeaf($code);
                if ($nonAlkes === null) {
                    $errors[] = "kode_non_alkes \"{$code}\" tidak ditemukan atau bukan leaf.";
                }
            }
        }

        $kodeRuang = $row['kode_ruang'] ?? '';
        if ($kodeRuang === '') {
            $errors[] = 'kode_ruang wajib.';
        } elseif (! AsetRuang::query()->where('kode_ruang', $kodeRuang)->exists()) {
            $errors[] = "kode_ruang \"{$kodeRuang}\" tidak ditemukan.";
        }

        $tahun = $row['tahun_registrasi'] ?? '';
        if ($tahun === '' || ! ctype_digit($tahun) || (int) $tahun < 1900 || (int) $tahun > 2100) {
            $errors[] = 'tahun_registrasi harus tahun 1900–2100.';
        }

        $serial = trim((string) ($row['no_seri'] ?? ''));
        if ($serial !== '') {
            $key = mb_strtolower($serial);
            if (isset($serialsInFile[$key])) {
                $errors[] = "no_seri \"{$serial}\" dobel dalam file (baris {$serialsInFile[$key]}).";
            } elseif (Aset::query()->where('no_seri', $serial)->exists()) {
                $errors[] = "no_seri \"{$serial}\" sudah dipakai aset lain.";
            }
        }

        $asal = $row['asal_barang'] ?? '';
        if ($asal !== '' && ! in_array($asal, ['Beli', 'Bantuan', 'Hibah'], true)) {
            $errors[] = 'asal_barang harus Beli, Bantuan, atau Hibah.';
        }

        $status = $row['status_fungsi'] ?? '';
        if ($status !== '' && ! in_array($status, ['berfungsi', 'tidak_berfungsi'], true)) {
            $errors[] = 'status_fungsi tidak valid.';
        }

        $tingkat = $row['tingkat_kerusakan'] ?? '';
        if ($tingkat !== '' && ! in_array($tingkat, ['baik', 'rusak_ringan', 'rusak_berat'], true)) {
            $errors[] = 'tingkat_kerusakan tidak valid.';
        }

        $harga = $row['harga'] ?? '';
        if ($harga !== '' && (! is_numeric($harga) || (float) $harga < 0)) {
            $errors[] = 'harga harus angka ≥ 0.';
        }

        $tanggal = $row['tanggal_pengadaan'] ?? '';
        if ($tanggal !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $errors[] = 'tanggal_pengadaan harus format YYYY-MM-DD.';
        }

        return $errors;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function buildPayload(array $row): array
    {
        $kelas = $row['kelas_aset'];
        $ruang = AsetRuang::query()->where('kode_ruang', $row['kode_ruang'])->firstOrFail();

        $merkId = null;
        if (filled($row['nama_merk'] ?? null)) {
            $merkId = $this->resolveMerkId((string) $row['nama_merk']);
        }

        $jenisId = null;
        if (filled($row['nama_tipe'] ?? null)) {
            $jenisId = $this->resolveJenisId((string) $row['nama_tipe'], $merkId);
        }

        $payload = [
            'kelas_aset' => $kelas,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => (int) $row['tahun_registrasi'],
            'jumlah_unit' => 1,
            'no_seri_list' => [trim((string) ($row['no_seri'] ?? ''))],
            'nama_barang' => filled($row['nama_barang'] ?? null) ? $row['nama_barang'] : null,
            'aset_merk_id' => $merkId,
            'aset_jenis_id' => $jenisId,
            'harga' => filled($row['harga'] ?? null) ? (float) $row['harga'] : null,
            'asal_barang' => filled($row['asal_barang'] ?? null) ? $row['asal_barang'] : null,
            'tanggal_pengadaan' => filled($row['tanggal_pengadaan'] ?? null) ? $row['tanggal_pengadaan'] : null,
            'status_fungsi' => filled($row['status_fungsi'] ?? null) ? $row['status_fungsi'] : 'berfungsi',
            'tingkat_kerusakan' => filled($row['tingkat_kerusakan'] ?? null) ? $row['tingkat_kerusakan'] : 'baik',
        ];

        if ($kelas === 'medis') {
            $aspak = $this->findAspakLeaf((string) $row['kode_aspak']);
            $payload['aset_aspak_alat_id'] = $aspak?->id;
            if (! filled($payload['nama_barang'])) {
                $payload['nama_barang'] = $aspak?->nama_alat;
            }
        } else {
            $nonAlkes = $this->findNonAlkesLeaf((string) $row['kode_non_alkes']);
            $payload['aset_non_alkes_id'] = $nonAlkes?->id;
            if (! filled($payload['nama_barang'])) {
                $payload['nama_barang'] = $nonAlkes?->nama_alat;
            }
        }

        return $payload;
    }

    private function findAspakLeaf(string $code): ?AsetAspakAlat
    {
        $item = AsetAspakAlat::query()
            ->where(function ($q) use ($code) {
                $q->where('kode', $code)->orWhere('alat_code', $code);
            })
            ->first();

        if ($item === null || ! $item->isLeaf()) {
            return null;
        }

        return $item;
    }

    private function findNonAlkesLeaf(string $code): ?AsetNonAlkes
    {
        $item = AsetNonAlkes::query()
            ->where(function ($q) use ($code) {
                $q->where('kode', $code)->orWhere('alat_code', $code);
            })
            ->first();

        if ($item === null || ! $item->isLeaf()) {
            return null;
        }

        return $item;
    }

    private function resolveMerkId(string $nama): int
    {
        $existing = AsetMerk::query()
            ->whereRaw('LOWER(nama_merk) = ?', [mb_strtolower($nama)])
            ->first();

        if ($existing !== null) {
            return $existing->id;
        }

        $generator = app(GeneratorKodeMasterAset::class);
        $merk = AsetMerk::query()->create([
            'kode_merk' => $generator->generate($nama, new AsetMerk, 'kode_merk'),
            'nama_merk' => $nama,
        ]);

        return $merk->id;
    }

    private function resolveJenisId(string $nama, ?int $merkId): int
    {
        $query = AsetJenis::query()->whereRaw('LOWER(nama_jenis) = ?', [mb_strtolower($nama)]);
        if ($merkId !== null) {
            $query->where(function ($q) use ($merkId) {
                $q->where('aset_merk_id', $merkId)->orWhereNull('aset_merk_id');
            });
        }
        $existing = $query->first();

        if ($existing !== null) {
            if ($merkId !== null && $existing->aset_merk_id === null) {
                $existing->update(['aset_merk_id' => $merkId]);
            }

            return $existing->id;
        }

        $generator = app(GeneratorKodeMasterAset::class);
        $jenis = AsetJenis::query()->create([
            'kode_jenis' => $generator->generate($nama, new AsetJenis, 'kode_jenis'),
            'nama_jenis' => $nama,
            'aset_merk_id' => $merkId,
        ]);

        return $jenis->id;
    }
}
