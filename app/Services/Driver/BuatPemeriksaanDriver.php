<?php

namespace App\Services\Driver;

use App\Models\DriverChecklistItem;
use App\Models\DriverKendaraan;
use App\Models\DriverKendaraanItem;
use App\Models\DriverPemeriksaan;
use App\Models\DriverPemeriksaanDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuatPemeriksaanDriver
{
    /**
     * @param  array<int, array{driver_checklist_item_id: int, hasil: string, temuan?: ?string, rekomendasi?: ?string, keterangan?: ?string}>  $items
     */
    public function handle(
        DriverKendaraan $kendaraan,
        User $petugas,
        array $items,
        ?string $catatan = null,
    ): DriverPemeriksaan {
        if (! $kendaraan->isAktif()) {
            throw ValidationException::withMessages([
                'driver_kendaraan_id' => 'Kendaraan ini tidak aktif.',
            ]);
        }

        $tanggal = now()->toDateString();
        $maxPerDay = (int) config('driver.max_inspection_per_day', 2);

        $existingCount = DriverPemeriksaan::query()
            ->where('driver_kendaraan_id', $kendaraan->id)
            ->whereDate('tanggal', $tanggal)
            ->where('status', DriverPemeriksaan::STATUS_SELESAI)
            ->count();

        if ($existingCount >= $maxPerDay) {
            throw ValidationException::withMessages([
                'driver_kendaraan_id' => "Pemeriksaan hari ini sudah mencapai batas maksimal ({$maxPerDay}×).",
            ]);
        }

        $pemeriksaanKe = $existingCount + 1;

        $applicableItemIds = DriverKendaraanItem::query()
            ->where('driver_kendaraan_id', $kendaraan->id)
            ->where('berlaku', true)
            ->pluck('driver_checklist_item_id')
            ->all();

        $naItemIds = DriverKendaraanItem::query()
            ->where('driver_kendaraan_id', $kendaraan->id)
            ->where('berlaku', false)
            ->pluck('driver_checklist_item_id')
            ->all();

        $hasPivotConfig = $applicableItemIds !== [] || $naItemIds !== [];

        $activeItems = DriverChecklistItem::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();

        if ($activeItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Belum ada item checklist aktif.',
            ]);
        }

        $payloadById = collect($items)->keyBy('driver_checklist_item_id');

        foreach ($activeItems as $item) {
            $isApplicable = $hasPivotConfig
                ? in_array($item->id, $applicableItemIds, true)
                : true;

            if (! $isApplicable) {
                continue;
            }

            $row = $payloadById->get($item->id);
            if ($row === null) {
                throw ValidationException::withMessages([
                    'items' => "Item \"{$item->nama}\" wajib diisi.",
                ]);
            }

            $hasil = $row['hasil'] ?? null;
            if (! in_array($hasil, [DriverPemeriksaanDetail::HASIL_LAYAK, DriverPemeriksaanDetail::HASIL_TIDAK_LAYAK], true)) {
                throw ValidationException::withMessages([
                    'items' => "Hasil tidak valid untuk item \"{$item->nama}\".",
                ]);
            }

            if ($hasil === DriverPemeriksaanDetail::HASIL_TIDAK_LAYAK) {
                if (blank($row['temuan'] ?? null) || blank($row['rekomendasi'] ?? null)) {
                    throw ValidationException::withMessages([
                        'items' => "Temuan dan rekomendasi wajib diisi untuk item \"{$item->nama}\".",
                    ]);
                }
            }
        }

        return DB::transaction(function () use ($kendaraan, $petugas, $tanggal, $pemeriksaanKe, $activeItems, $payloadById, $applicableItemIds, $hasPivotConfig, $catatan) {
            $pemeriksaan = DriverPemeriksaan::query()->create([
                'driver_kendaraan_id' => $kendaraan->id,
                'petugas_id' => $petugas->id,
                'tanggal' => $tanggal,
                'pemeriksaan_ke' => $pemeriksaanKe,
                'waktu_pemeriksaan' => now(),
                'status' => DriverPemeriksaan::STATUS_SELESAI,
                'catatan' => $catatan,
            ]);

            foreach ($activeItems as $item) {
                $isApplicable = $hasPivotConfig
                    ? in_array($item->id, $applicableItemIds, true)
                    : true;

                if (! $isApplicable) {
                    DriverPemeriksaanDetail::query()->create([
                        'driver_pemeriksaan_id' => $pemeriksaan->id,
                        'driver_checklist_item_id' => $item->id,
                        'hasil' => DriverPemeriksaanDetail::HASIL_NA,
                        'temuan' => null,
                        'rekomendasi' => null,
                        'keterangan' => 'Tidak berlaku untuk kendaraan ini',
                    ]);

                    continue;
                }

                $row = $payloadById->get($item->id);

                DriverPemeriksaanDetail::query()->create([
                    'driver_pemeriksaan_id' => $pemeriksaan->id,
                    'driver_checklist_item_id' => $item->id,
                    'hasil' => $row['hasil'],
                    'temuan' => $row['hasil'] === DriverPemeriksaanDetail::HASIL_TIDAK_LAYAK ? ($row['temuan'] ?? null) : null,
                    'rekomendasi' => $row['hasil'] === DriverPemeriksaanDetail::HASIL_TIDAK_LAYAK ? ($row['rekomendasi'] ?? null) : null,
                    'keterangan' => $row['keterangan'] ?? null,
                ]);
            }

            return $pemeriksaan->load(['details.checklistItem', 'kendaraan', 'petugas']);
        });
    }
}
