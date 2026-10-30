<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetRuang;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->type = TicketType::firstOrCreate(
        ['slug' => 'incident'],
        ['name' => 'Incident', 'description' => 'Test', 'is_active' => true]
    );
    $this->category = TicketCategory::firstOrCreate(
        ['slug' => 'it-hardware'],
        ['name' => 'Hardware', 'ticket_type_id' => $this->type->id, 'dep_id' => 'IT', 'is_active' => true]
    );
    $this->priority = TicketPriority::firstOrCreate(
        ['slug' => 'low'],
        ['name' => 'Low', 'level' => 1, 'is_active' => true]
    );
    $this->statusNew = TicketStatus::firstOrCreate(
        ['slug' => TicketStatus::SLUG_NEW],
        ['name' => 'New', 'is_closed' => false, 'is_active' => true]
    );
});

function makePortalAset(string $kode = 'INV-IGD-2026-0099', ?string $noSimrs = null): Aset
{
    $ruang = AsetRuang::query()->firstOrCreate(
        ['kode_ruang' => 'IGD'],
        ['nama_ruang' => 'IGD']
    );
    $barang = AsetBarang::query()->firstOrCreate(
        ['kode_barang' => 'MON99'],
        ['nama_barang' => 'Patient Monitor Link', 'kelas_aset' => 'medis']
    );

    return Aset::query()->create([
        'kode_aset' => $kode,
        'no_simrs' => $noSimrs,
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IGD',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
        'status_fungsi' => 'berfungsi',
        'no_seri' => 'SN-LINK-1',
    ]);
}

it('prefill create form from asset_id query', function () {
    $user = User::factory()->pemohon()->create();
    $aset = makePortalAset();

    actingAs($user)
        ->get('/tickets/create?asset_id='.$aset->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tickets/create')
            ->where('initialAssetId', $aset->id)
            ->where('initialAssetNoInventaris', $aset->kode_aset)
            ->where('initialAssetLabel', fn ($label) => str_contains((string) $label, $aset->kode_aset)));
});

it('searches portal aset by kode and no seri', function () {
    $user = User::factory()->pemohon()->create();
    $aset = makePortalAset('INV-IGD-2026-0888');

    actingAs($user);

    $byKode = getJson('/tickets/search-for-inventaris?q=INV-IGD-2026-0888')->assertOk()->json();
    expect($byKode)->not->toBeEmpty()
        ->and($byKode[0]['asset_id'])->toBe($aset->id)
        ->and($byKode[0]['sumber'])->toBe('portal')
        ->and($byKode[0]['kode_aset'])->toBe('INV-IGD-2026-0888');

    $bySeri = getJson('/tickets/search-for-inventaris?q=SN-LINK-1')->assertOk()->json();
    expect(collect($bySeri)->pluck('asset_id')->all())->toContain($aset->id);
});

it('stores ticket with asset_id and resolves portal aset', function () {
    $user = User::factory()->pemohon()->create();
    $aset = makePortalAset('INV-IGD-2026-0777', null);

    actingAs($user)
        ->post('/tickets', [
            'ticket_type_id' => $this->type->id,
            'dep_id' => 'IT',
            'ticket_category_id' => $this->category->id,
            'ticket_priority_id' => $this->priority->id,
            'title' => 'Rusak monitor IGD',
            'description' => 'Tidak nyala',
            'asset_id' => $aset->id,
        ])
        ->assertRedirect();

    $ticket = Ticket::query()->where('title', 'Rusak monitor IGD')->firstOrFail();
    expect($ticket->asset_id)->toBe($aset->id);
});

it('stores ticket with asset_no_inventaris and links portal aset by kode', function () {
    $user = User::factory()->pemohon()->create();
    $aset = makePortalAset('INV-IGD-2026-0666');

    actingAs($user)
        ->post('/tickets', [
            'ticket_type_id' => $this->type->id,
            'dep_id' => 'IT',
            'ticket_category_id' => $this->category->id,
            'ticket_priority_id' => $this->priority->id,
            'title' => 'Tiket via kode aset',
            'asset_no_inventaris' => $aset->kode_aset,
        ])
        ->assertRedirect();

    $ticket = Ticket::query()->where('title', 'Tiket via kode aset')->firstOrFail();
    expect($ticket->asset_id)->toBe($aset->id)
        ->and($ticket->asset_no_inventaris)->toBe($aset->kode_aset);
});

it('shows portal aset on ticket detail', function () {
    $user = User::factory()->pemohon()->create();
    $aset = makePortalAset('INV-IGD-2026-0555');
    $ticket = Ticket::factory()->create([
        'ticket_type_id' => $this->type->id,
        'ticket_category_id' => $this->category->id,
        'ticket_priority_id' => $this->priority->id,
        'ticket_status_id' => $this->statusNew->id,
        'requester_id' => $user->id,
        'dep_id' => 'IT',
        'asset_id' => $aset->id,
        'asset_no_inventaris' => $aset->kode_aset,
    ]);

    actingAs($user)
        ->get('/tickets/'.$ticket->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tickets/show')
            ->where('ticket.aset.kode_aset', 'INV-IGD-2026-0555')
            ->where('ticket.asset_id', $aset->id));
});

it('exposes ticket create url on public scan page', function () {
    $aset = makePortalAset('INV-IGD-2026-0444');

    $this->get(route('aset.public.show', $aset))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('ticketCreateUrl', '/tickets/create?asset_id='.$aset->id)
            ->where('aset.id', $aset->id));
});
