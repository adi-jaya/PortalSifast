<?php

use App\Models\PatroliArea;
use App\Models\PatroliCheckin;
use App\Models\PatroliCheckinItem;
use App\Models\PatroliRuang;
use App\Models\PatroliTemplate;
use App\Models\User;
use App\Services\Patroli\PatroliLaporanAggregator;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;

test('laporan periode calculates berfungsi percent correctly', function () {
    $user = User::factory()->create(['can_access_patroli' => true]);
    $template = PatroliTemplate::query()->create([
        'nama' => 'Laporan '.uniqid(),
        'is_active' => true,
    ]);
    $item = $template->items()->create([
        'nama' => 'CCTV',
        'urutan' => 0,
        'is_active' => true,
    ]);
    $area = PatroliArea::query()->create([
        'nama' => 'Area Laporan '.uniqid(),
        'is_active' => true,
    ]);
    $ruang = PatroliRuang::query()->create([
        'patroli_area_id' => $area->id,
        'kode' => 'RPT-'.uniqid(),
        'nama' => 'Ruang Laporan',
        'patroli_template_id' => $template->id,
        'is_active' => true,
    ]);

    $make = function (string $status, Carbon $at) use ($user, $ruang, $template, $item): void {
        $checkin = PatroliCheckin::query()->create([
            'patroli_ruang_id' => $ruang->id,
            'user_id' => $user->id,
            'patroli_template_id' => $template->id,
            'checked_at' => $at,
        ]);
        PatroliCheckinItem::query()->create([
            'patroli_checkin_id' => $checkin->id,
            'patroli_template_item_id' => $item->id,
            'nama_item' => 'CCTV',
            'status' => $status,
        ]);
    };

    $make(PatroliCheckinItem::STATUS_BERFUNGSI, Carbon::parse('2026-07-10 08:00:00'));
    $make(PatroliCheckinItem::STATUS_BERFUNGSI, Carbon::parse('2026-07-11 08:00:00'));
    $make(PatroliCheckinItem::STATUS_TIDAK_BERFUNGSI, Carbon::parse('2026-07-12 08:00:00'));
    $make(PatroliCheckinItem::STATUS_TIDAK_DICEK, Carbon::parse('2026-07-13 08:00:00'));

    $report = app(PatroliLaporanAggregator::class)->aggregate(
        Carbon::parse('2026-07-01'),
        Carbon::parse('2026-07-31'),
    );

    expect($report['total_checkin'])->toBe(4)
        ->and($report['per_item'][0]['nama_item'])->toBe('CCTV')
        ->and($report['per_item'][0]['berfungsi'])->toBe(2)
        ->and($report['per_item'][0]['tidak_berfungsi'])->toBe(1)
        ->and($report['per_item'][0]['tidak_dicek'])->toBe(1)
        ->and($report['per_item'][0]['persen_berfungsi'])->toBe(66.7)
        ->and($report['temuan'])->toHaveCount(1)
        ->and($report['per_area'][0]['nama_area'])->toBe($area->nama)
        ->and($report['per_area'][0]['total_checkin'])->toBe(4);

    actingAs($user)
        ->get('/patroli/laporan?periode=custom&from=2026-07-01&to=2026-07-31')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patroli/laporan')
            ->where('report.total_checkin', 4)
            ->where('report.per_item.0.persen_berfungsi', 66.7)
            ->where('report.per_area.0.total_checkin', 4));
});
