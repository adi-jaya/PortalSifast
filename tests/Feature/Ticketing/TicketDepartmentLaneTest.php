<?php

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketCollaborator;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);

    $this->type = TicketType::firstOrCreate(
        ['slug' => 'pest-ticketing-incident-lane'],
        ['name' => 'Insiden Lane (Pest)', 'description' => 'Master data lane', 'is_active' => true]
    );
    $this->categoryIt = TicketCategory::firstOrCreate(
        ['ticket_type_id' => $this->type->id, 'name' => 'Jaringan Lane (Pest)'],
        ['dep_id' => 'IT', 'is_development' => false, 'is_active' => true]
    );
    $this->categoryIps = TicketCategory::firstOrCreate(
        ['ticket_type_id' => $this->type->id, 'name' => 'AC Lane (Pest)'],
        ['dep_id' => 'IPS', 'is_development' => false, 'is_active' => true]
    );
    $this->priority = TicketPriority::firstOrCreate(
        ['name' => 'Rendah Lane (Pest)'],
        ['level' => 4, 'color' => 'green', 'response_hours' => 24, 'resolution_hours' => 72, 'is_active' => true]
    );
    $this->statusNew = TicketStatus::firstOrCreate(
        ['slug' => TicketStatus::SLUG_NEW],
        ['name' => 'Baru', 'color' => 'blue', 'order' => 1, 'is_closed' => false, 'is_active' => true]
    );
});

it('filters tickets index by penanganan department for admin', function () {
    $admin = User::factory()->admin()->create();

    Ticket::factory()->create([
        'ticket_type_id' => $this->type->id,
        'ticket_category_id' => $this->categoryIt->id,
        'ticket_priority_id' => $this->priority->id,
        'ticket_status_id' => $this->statusNew->id,
        'dep_id' => 'IT',
        'title' => 'Tiket IT lane',
    ]);
    Ticket::factory()->create([
        'ticket_type_id' => $this->type->id,
        'ticket_category_id' => $this->categoryIps->id,
        'ticket_priority_id' => $this->priority->id,
        'ticket_status_id' => $this->statusNew->id,
        'dep_id' => 'IPS',
        'title' => 'Tiket IPS lane',
    ]);

    $this->actingAs($admin)
        ->get('/tickets?department=IPS&include_closed=1')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tickets/index')
            ->where('filters.department', 'IPS')
            ->has('tickets.data', 1)
            ->where('tickets.data.0.dep_id', 'IPS'));
});

it('exports csv filename with department when filtered', function () {
    $admin = User::factory()->admin()->create();

    Ticket::factory()->create([
        'ticket_type_id' => $this->type->id,
        'ticket_category_id' => $this->categoryIt->id,
        'ticket_priority_id' => $this->priority->id,
        'ticket_status_id' => $this->statusNew->id,
        'dep_id' => 'IT',
    ]);

    $response = $this->actingAs($admin)->get('/tickets/export?department=IT&include_closed=1');

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('tickets-IT-');
});

it('passes both IT and IPS categories to create form for staff', function () {
    $staffIt = User::factory()->staff('IT')->create();

    $this->actingAs($staffIt)
        ->get('/tickets/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tickets/create')
            ->where('categories', function ($categories) {
                $deps = collect($categories)->pluck('dep_id')->unique();

                return $deps->contains('IT') && $deps->contains('IPS');
            }));
});

it('requires dep_id when creating a ticket and rejects mismatched category', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/tickets', [
            'ticket_type_id' => $this->type->id,
            'dep_id' => 'IT',
            'ticket_category_id' => $this->categoryIps->id,
            'ticket_priority_id' => $this->priority->id,
            'title' => 'Salah kategori',
            'description' => 'Test',
        ])
        ->assertSessionHasErrors('ticket_category_id');
});

it('creates ticket with selected penanganan', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/tickets', [
            'ticket_type_id' => $this->type->id,
            'dep_id' => 'IPS',
            'ticket_category_id' => $this->categoryIps->id,
            'ticket_priority_id' => $this->priority->id,
            'title' => 'Tiket IPS baru',
            'description' => 'AC rusak',
        ])
        ->assertRedirect();

    expect(Ticket::query()->where('title', 'Tiket IPS baru')->value('dep_id'))->toBe('IPS');
});

it('allows assignee to transfer ticket department with reason', function () {
    $assignee = User::factory()->staff('IT')->create();
    $ticket = Ticket::factory()->create([
        'ticket_type_id' => $this->type->id,
        'ticket_category_id' => $this->categoryIt->id,
        'ticket_priority_id' => $this->priority->id,
        'ticket_status_id' => $this->statusNew->id,
        'dep_id' => 'IT',
        'assignee_id' => $assignee->id,
        'title' => 'Transfer me',
    ]);

    $this->actingAs($assignee)
        ->post("/tickets/{$ticket->id}/transfer-department", [
            'dep_id' => 'IPS',
            'ticket_category_id' => $this->categoryIps->id,
            'reason' => 'Salah jalur, ini masalah fasilitas',
        ])
        ->assertRedirect(route('tickets.show', $ticket));

    $ticket->refresh();
    expect($ticket->dep_id)->toBe('IPS')
        ->and($ticket->ticket_category_id)->toBe($this->categoryIps->id)
        ->and($ticket->assignee_id)->toBeNull()
        ->and(
            TicketCollaborator::query()
                ->where('ticket_id', $ticket->id)
                ->where('user_id', $assignee->id)
                ->exists()
        )->toBeTrue()
        ->and($ticket->activities()->where('action', 'department_transferred')->exists())->toBeTrue();
});

it('rejects transfer by unrelated staff', function () {
    $outsider = User::factory()->staff('IT')->create();
    $ticket = Ticket::factory()->create([
        'ticket_type_id' => $this->type->id,
        'ticket_category_id' => $this->categoryIt->id,
        'ticket_priority_id' => $this->priority->id,
        'ticket_status_id' => $this->statusNew->id,
        'dep_id' => 'IT',
        'assignee_id' => null,
        'title' => 'No transfer',
    ]);

    $this->actingAs($outsider)
        ->post("/tickets/{$ticket->id}/transfer-department", [
            'dep_id' => 'IPS',
            'ticket_category_id' => $this->categoryIps->id,
            'reason' => 'Tidak boleh tanpa jadi assignee',
        ])
        ->assertForbidden();
});
