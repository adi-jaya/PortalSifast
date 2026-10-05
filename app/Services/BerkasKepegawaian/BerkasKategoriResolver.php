<?php

namespace App\Services\BerkasKepegawaian;

use App\Models\Pegawai;

class BerkasKategoriResolver
{
    public const KATEGORI_DOKTER_UMUM = 'Tenaga klinis Dokter Umum';

    public const KATEGORI_DOKTER_SPESIALIS = 'Tenaga klinis Dokter Spesialis';

    public const KATEGORI_PERAWAT_BIDAN = 'Tenaga klinis Perawat dan Bidan';

    public const KATEGORI_PROFESI_LAIN = 'Tenaga klinis Profesi Lain';

    public const KATEGORI_NON_KLINIS = 'Tenaga Non Klinis';

    /**
     * @var array<string, list<string>>
     */
    private const SUGGESTION_ALIASES = [
        'STR' => ['str', 'surat tanda registrasi'],
        'SIP' => ['sip', 'surat izin praktik', 'izin praktik'],
        'IJAZAH' => ['ijazah', 'ijazah legalisir'],
        'KONSIL' => ['konsil', 'konsil dokter'],
        'KK' => ['kk', 'kartu keluarga'],
        'SERTIFIKAT' => ['sertifikat'],
        'LAMARAN' => ['lamaran', 'lamaran kerja'],
        'VERIFIKASI' => ['verifikasi ijazah', 'verifikasi'],
        'KTP' => ['ktp', 'kartu tanda penduduk'],
        'NPWP' => ['npwp'],
        'BPJS' => ['bpjs'],
    ];

    public function resolve(Pegawai|string|null $pegawaiOrNama = null, ?string $jbtn = null, ?string $bidang = null): string
    {
        if ($pegawaiOrNama instanceof Pegawai) {
            $nama = (string) $pegawaiOrNama->nama;
            $jbtn = (string) $pegawaiOrNama->jbtn;
            $bidang = (string) $pegawaiOrNama->bidang;
        } else {
            $nama = (string) $pegawaiOrNama;
            $jbtn = (string) $jbtn;
            $bidang = (string) $bidang;
        }

        $namaNorm = $this->normalize($nama);
        $jbtnNorm = $this->normalize($jbtn);
        $bidangNorm = $this->normalize($bidang);

        if ($this->isDokterSpesialis($namaNorm, $jbtnNorm)) {
            return self::KATEGORI_DOKTER_SPESIALIS;
        }

        if ($this->isDokter($namaNorm, $jbtnNorm, $bidangNorm)) {
            return self::KATEGORI_DOKTER_UMUM;
        }

        if ($this->isPerawatAtauBidan($namaNorm, $jbtnNorm)) {
            return self::KATEGORI_PERAWAT_BIDAN;
        }

        if ($this->isKlinisLain($bidangNorm, $jbtnNorm)) {
            return self::KATEGORI_PROFESI_LAIN;
        }

        return self::KATEGORI_NON_KLINIS;
    }

    /**
     * @param  list<array{kode: string, nama_berkas: string, kategori: string|null}>  $masters
     * @return list<array{kode: string, nama_berkas: string, kategori: string|null}>
     */
    public function filterMastersByKategori(array $masters, string $kategori): array
    {
        return array_values(array_filter(
            $masters,
            fn (array $row): bool => (string) ($row['kategori'] ?? '') === $kategori,
        ));
    }

    /**
     * Map OCR/agent suggestion (STR/SIP/IJAZAH/…) to a master kode within one kategori.
     *
     * @param  list<array{kode: string, nama_berkas: string, kategori: string|null}>  $mastersInKategori
     */
    public function matchSuggestedKode(
        array $mastersInKategori,
        ?string $suggestedKode,
        ?string $suggestedLabel = null,
    ): ?string {
        if ($mastersInKategori === []) {
            return null;
        }

        $suggestedKode = strtoupper(trim((string) $suggestedKode));
        $haystack = $this->normalize(trim((string) $suggestedLabel).' '.$suggestedKode);

        foreach ($mastersInKategori as $master) {
            if (strtoupper((string) $master['kode']) === $suggestedKode) {
                return (string) $master['kode'];
            }
        }

        $aliases = self::SUGGESTION_ALIASES[$suggestedKode] ?? [];
        if ($aliases === [] && $haystack !== '') {
            $aliases = [$haystack];
        }

        $bestKode = null;
        $bestScore = 0;

        foreach ($mastersInKategori as $master) {
            $nama = $this->normalize((string) $master['nama_berkas']);
            $score = 0;

            foreach ($aliases as $alias) {
                $aliasNorm = $this->normalize($alias);
                if ($aliasNorm === '') {
                    continue;
                }
                if ($nama === $aliasNorm) {
                    $score = max($score, 100);
                } elseif (str_contains($nama, $aliasNorm)) {
                    $score = max($score, 50 + mb_strlen($aliasNorm));
                }
            }

            if ($haystack !== '' && str_contains($nama, $haystack)) {
                $score = max($score, 40);
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestKode = (string) $master['kode'];
            }
        }

        return $bestScore > 0 ? $bestKode : null;
    }

    private function isDokterSpesialis(string $nama, string $jbtn): bool
    {
        if (str_contains($jbtn, 'spesialis')) {
            return true;
        }

        if (preg_match('/\bsp\.?\s*[a-z]{1,12}\b/u', $jbtn) === 1) {
            return true;
        }

        if (preg_match('/\bsp\.[a-z]/u', $nama) === 1) {
            return true;
        }

        return str_contains($nama, 'spesialis');
    }

    private function isDokter(string $nama, string $jbtn, string $bidang): bool
    {
        if (str_contains($jbtn, 'dokter')) {
            return true;
        }

        if (str_contains($bidang, 'dokter')) {
            return true;
        }

        if ($bidang === 'staf medis') {
            return true;
        }

        return preg_match('/(^|[\s,])dr\.?(\s|$)/u', $nama) === 1
            || str_starts_with($nama, 'dr ')
            || str_starts_with($nama, 'dr.');
    }

    private function isPerawatAtauBidan(string $nama, string $jbtn): bool
    {
        if (str_contains($jbtn, 'perawat') || str_contains($jbtn, 'bidan') || str_contains($jbtn, 'keperawatan')) {
            return true;
        }

        return str_contains($nama, 's.kep')
            || str_contains($nama, 's kep')
            || str_contains($nama, 's. keb')
            || str_contains($nama, 's.keb')
            || str_contains($nama, 'amd.kep')
            || str_contains($nama, 'amd.keb')
            || str_contains($nama, 'a.md.kep')
            || str_contains($nama, 'a.md.keb');
    }

    private function isKlinisLain(string $bidang, string $jbtn): bool
    {
        if (str_contains($bidang, 'staf klinis') || str_contains($bidang, 'staf medis')) {
            return true;
        }

        foreach (['farmasi', 'gizi', 'radiografer', 'analis', 'terapis', 'fisioterapi', 'rekam medis'] as $keyword) {
            if (str_contains($jbtn, $keyword) || str_contains($bidang, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['_', '-'], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }
}
