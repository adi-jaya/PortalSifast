<?php

use App\Models\MonitoredDevice;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    Config::set('services.tianji.base_url', 'http://tianji.test');
    Config::set('services.tianji.api_key', 'test-key');
    Config::set('services.tianji.workspace_id', 'ws_test');
    Config::set('services.tianji.timeout', 5);
});

function fakeTianjiDashboard(): void
{
    Http::fake([
        'http://tianji.test/open/workspace/ws_test/monitor/all' => Http::response([
            [
                'id' => 'mon_1',
                'name' => 'SIMRS',
                'type' => 'http',
                'active' => true,
                'payload' => ['url' => 'http://192.168.10.1'],
            ],
            [
                'id' => 'mon_2',
                'name' => 'IT1',
                'type' => 'ping',
                'active' => true,
                'payload' => ['hostname' => '192.168.10.57'],
            ],
        ], 200),
        'http://tianji.test/open/workspace/ws_test/monitor/mon_1/publicSummary' => Http::response([
            ['day' => '2026-07-01', 'totalCount' => 100, 'upCount' => 90, 'upRate' => 0.9],
            ['day' => '2026-07-02', 'totalCount' => 100, 'upCount' => 80, 'upRate' => 0.8],
        ], 200),
        'http://tianji.test/open/workspace/ws_test/monitor/mon_2/publicSummary' => Http::response([
            ['day' => '2026-07-01', 'totalCount' => 50, 'upCount' => 0, 'upRate' => 0],
        ], 200),
        'http://tianji.test/open/workspace/ws_test/monitor/mon_1/recentData*' => Http::response([
            ['value' => 15.2, 'createdAt' => '2026-07-29T01:00:00.000Z'],
        ], 200),
        'http://tianji.test/open/workspace/ws_test/monitor/mon_2/recentData*' => Http::response([
            ['value' => -1, 'createdAt' => '2026-07-29T01:00:00.000Z'],
        ], 200),
        'http://tianji.test/open/workspace/ws_test/monitor/events*' => Http::response([
            [
                'id' => 'evt_1',
                'message' => 'Monitor [IT1] has been down',
                'monitorId' => 'mon_2',
                'type' => 'DOWN',
                'createdAt' => '2026-07-23T00:00:00.635Z',
            ],
        ], 200),
        'http://tianji.test/open/workspace/ws_test/monitor/mon_1/data*' => Http::response([
            ['value' => 12.5, 'createdAt' => '2026-07-01T01:00:00.000Z'],
            ['value' => -1, 'createdAt' => '2026-07-01T02:00:00.000Z'],
            ['value' => 20, 'createdAt' => '2026-07-01T03:00:00.000Z'],
        ], 200),
        'http://tianji.test/open/workspace/ws_test/monitor/mon_2/data*' => Http::response([
            ['value' => -1, 'createdAt' => '2026-07-01T01:30:00.000Z'],
            ['value' => -1, 'createdAt' => '2026-07-01T02:30:00.000Z'],
        ], 200),
    ]);
}

it('redirects guests away from infrastruktur', function (): void {
    get('/infrastruktur')->assertRedirect();
});

it('redirects old laporan-tianji path to infrastruktur', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/laporan-tianji')
        ->assertRedirect('/infrastruktur');
});

it('shows combined infrastruktur dashboard', function (): void {
    fakeTianjiDashboard();

    $device = MonitoredDevice::factory()->online()->create([
        'computer_name' => 'PC-IT',
        'hostname' => 'pc-it',
        'last_cpu_percent' => 12.5,
    ]);

    $user = User::factory()->create();

    actingAs($user)
        ->get('/infrastruktur?start_date=2026-07-01&end_date=2026-07-07')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('infrastruktur/index')
            ->where('configured', true)
            ->where('summary.tianji.total', 2)
            ->where('summary.tianji.up_now', 1)
            ->where('summary.tianji.down_now', 1)
            ->where('tianji.monitors.0.name', 'SIMRS')
            ->where('tianji.monitors.0.current_status', 'up')
            ->where('tianji.monitors.0.period_uptime_percent', 85)
            ->where('tianji.monitors.1.current_status', 'down')
            ->where('tianji.events.0.type', 'DOWN')
            ->where('summary.agent.total', 1)
            ->where('agent.devices.0.id', $device->id)
            ->where('agent.devices.0.computer_name', 'PC-IT'));
});

it('keeps agent block when tianji fails', function (): void {
    Http::fake([
        'http://tianji.test/open/workspace/ws_test/monitor/all' => Http::response('down', 503),
    ]);

    MonitoredDevice::factory()->create();
    $user = User::factory()->create();

    actingAs($user)
        ->get('/infrastruktur')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('infrastruktur/index')
            ->where('tianji.monitors', [])
            ->where('tianji_error', 'Tianji mengembalikan error HTTP 503.')
            ->where('summary.agent.total', 1));
});

it('exports ringkasan csv with uptime columns', function (): void {
    fakeTianjiDashboard();

    $user = User::factory()->create();

    $response = actingAs($user)->get('/laporan-tianji/export/ringkasan?start_date=2026-07-01&end_date=2026-07-07');

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $csv = $response->streamedContent();
    expect($csv)
        ->toContain('Nama,Tipe,Target,Aktif,"Periode mulai","Periode selesai","Total check",Up,Down,"Uptime %","Latency rata-rata (ms)"')
        ->toContain('SIMRS,http,http://192.168.10.1,ya,2026-07-01,2026-07-07,3,2,1,66.7,16.3')
        ->toContain('IT1,ping,192.168.10.57,ya,2026-07-01,2026-07-07,2,0,2,0.0,');
});

it('exports detail csv with check rows', function (): void {
    fakeTianjiDashboard();

    $user = User::factory()->create();

    $response = actingAs($user)->get('/laporan-tianji/export/detail?start_date=2026-07-01&end_date=2026-07-07');

    $response->assertSuccessful();
    $csv = $response->streamedContent();

    expect($csv)
        ->toContain('"Nama monitor",Tipe,Target,Waktu,Status,"Latency (ms)",Catatan')
        ->toContain('SIMRS,http,http://192.168.10.1,')
        ->toContain(',UP,12.5,')
        ->toContain(',DOWN,,')
        ->toContain('IT1,ping,192.168.10.57,');
});

it('rejects date ranges longer than 31 days', function (): void {
    fakeTianjiDashboard();

    $user = User::factory()->create();

    actingAs($user)
        ->from('/infrastruktur')
        ->get('/laporan-tianji/export/ringkasan?start_date=2026-07-01&end_date=2026-08-05')
        ->assertRedirect('/infrastruktur')
        ->assertSessionHasErrors('end_date');
});

it('exports uptime harian csv from publicSummary', function (): void {
    fakeTianjiDashboard();

    $user = User::factory()->create();

    $response = actingAs($user)->get('/laporan-tianji/export/harian?start_date=2026-07-01&end_date=2026-07-07');

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $csv = $response->streamedContent();
    expect($csv)
        ->toContain('Tanggal,Nama,Tipe,Target,"Total check",Up,Down,"Uptime %"')
        ->toContain('2026-07-01,SIMRS,http,http://192.168.10.1,100,90,10,90.0')
        ->toContain('2026-07-02,SIMRS,http,http://192.168.10.1,100,80,20,80.0')
        ->toContain('2026-07-01,IT1,ping,192.168.10.57,50,0,50,0.0');
});

it('exports gangguan csv from monitor events', function (): void {
    fakeTianjiDashboard();

    $user = User::factory()->create();

    $response = actingAs($user)->get('/laporan-tianji/export/gangguan?start_date=2026-07-01&end_date=2026-07-31');

    $response->assertSuccessful();
    $csv = $response->streamedContent();

    expect($csv)
        ->toContain('Waktu,Monitor,"Tipe event",Pesan')
        ->toContain('Monitor [IT1] has been down')
        ->toContain('DOWN');
});

it('exports agent csv with device data', function (): void {
    MonitoredDevice::factory()->online()->create([
        'hostname' => 'srv-01',
        'computer_name' => 'Server Utama',
        'ip_address' => '192.168.10.10',
        'last_cpu_percent' => 45.2,
        'last_ram_percent' => 60.1,
        'last_disk_percent' => 30.0,
    ]);

    $user = User::factory()->create();

    $response = actingAs($user)->get('/laporan-tianji/export/agent');

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $csv = $response->streamedContent();
    expect($csv)
        ->toContain('Hostname,"Nama komputer",IP,OS,Status')
        ->toContain('srv-01,"Server Utama",192.168.10.10')
        ->toContain('45.2')
        ->toContain('60.1');
});

it('redirects export when tianji is unreachable', function (): void {
    Http::fake([
        'http://tianji.test/open/workspace/ws_test/monitor/all' => function () {
            throw new Illuminate\Http\Client\ConnectionException('Connection refused');
        },
    ]);

    $user = User::factory()->create();

    actingAs($user)
        ->from('/infrastruktur')
        ->get('/laporan-tianji/export/detail?start_date=2026-07-01&end_date=2026-07-07')
        ->assertRedirect()
        ->assertSessionHas('error');
});
