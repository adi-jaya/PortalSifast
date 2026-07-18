<?php

use App\Enums\WebOfficialFeedbackStatus;
use App\Models\User;
use App\Models\WebOfficialFeedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->pemohon = User::factory()->pemohon()->create();
});

function validKritikSaranPayload(array $overrides = []): array
{
    return array_merge([
        'fullName' => 'Budi Santoso',
        'phone' => '081234567890',
        'serviceUnit' => 'IGD (Unit Gawat Darurat)',
        'rating' => 2,
        'message' => 'Antrian IGD terlalu lama dan informasi ke keluarga pasien kurang jelas.',
    ], $overrides);
}

it('accepts public kritik saran submission', function (): void {
    $this->postJson('/api/kritik-saran', validKritikSaranPayload())
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Kritik & saran berhasil diterima.')
        ->assertJsonStructure([
            'data' => ['id', 'submittedAt'],
        ]);

    $this->assertDatabaseHas('web_official_feedback', [
        'full_name' => 'Budi Santoso',
        'phone' => '081234567890',
        'service_unit' => 'IGD (Unit Gawat Darurat)',
        'rating' => 2,
        'status' => WebOfficialFeedbackStatus::New->value,
    ]);
});

it('returns validation errors in envelope format', function (): void {
    $this->postJson('/api/kritik-saran', [])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Validasi gagal.')
        ->assertJsonStructure(['errors' => ['fullName', 'phone', 'serviceUnit', 'rating', 'message']]);
});

it('rejects duplicate submission within five minutes', function (): void {
    $payload = validKritikSaranPayload();

    $this->postJson('/api/kritik-saran', $payload)->assertCreated();

    $this->postJson('/api/kritik-saran', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('errors.message.0', 'Pengaduan serupa baru saja dikirim. Mohon tunggu beberapa menit.');
});

it('lists service units publicly', function (): void {
    $this->getJson('/api/service-units')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonFragment(['IGD (Unit Gawat Darurat)']);
});

it('admin can list kritik saran submissions', function (): void {
    WebOfficialFeedback::factory()->create(['full_name' => 'Ani Wijaya']);

    Sanctum::actingAs($this->admin);

    $this->getJson('/api/admin/kritik-saran')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.fullName', 'Ani Wijaya');
});

it('admin can update kritik saran status', function (): void {
    $feedback = WebOfficialFeedback::factory()->create();

    Sanctum::actingAs($this->admin);

    $this->patchJson("/api/admin/kritik-saran/{$feedback->id}", [
        'status' => 'in_progress',
        'adminNotes' => 'Sudah diteruskan ke kepala IGD.',
    ])->assertSuccessful()
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonPath('data.adminNotes', 'Sudah diteruskan ke kepala IGD.');

    $feedback->refresh();
    expect($feedback->status)->toBe(WebOfficialFeedbackStatus::InProgress);
});

it('non admin cannot access admin kritik saran endpoints', function (): void {
    Sanctum::actingAs($this->pemohon);

    $this->getJson('/api/admin/kritik-saran')->assertForbidden();
});

test('admin can open kritik saran panel', function (): void {
    WebOfficialFeedback::factory()->create(['full_name' => 'Rina Kartika']);

    actingAs($this->admin)
        ->get('/web-official/kritik-saran')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('web-official/kritik-saran/index')
            ->has('feedbacks.data', 1)
            ->where('feedbacks.data.0.full_name', 'Rina Kartika'));
});

test('admin can update kritik saran from web panel', function (): void {
    $feedback = WebOfficialFeedback::factory()->create();

    actingAs($this->admin)
        ->put("/web-official/kritik-saran/{$feedback->id}", [
            'status' => 'resolved',
            'admin_notes' => 'Sudah ditindaklanjuti.',
        ])
        ->assertRedirect(route('web-official.kritik-saran.show', $feedback));

    $feedback->refresh();
    expect($feedback->status)->toBe(WebOfficialFeedbackStatus::Resolved)
        ->and($feedback->admin_notes)->toBe('Sudah ditindaklanjuti.')
        ->and($feedback->resolved_at)->not->toBeNull();
});
