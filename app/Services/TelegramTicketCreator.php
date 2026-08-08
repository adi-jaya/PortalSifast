<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Support\Str;
use RuntimeException;

final class TelegramTicketCreator
{
    /**
     * Buat tiket dari Telegram (pemohon sementara = user terhubung).
     *
     * @throws RuntimeException
     */
    public function create(User $user, string $title, string $description, string $requestedBy): Ticket
    {
        $title = trim($title);
        $requestedBy = trim($requestedBy);
        $description = trim($description);

        if ($title === '') {
            throw new RuntimeException('Judul tiket kosong.');
        }

        if ($requestedBy === '') {
            throw new RuntimeException('Pemohon (Diminta oleh) wajib diisi.');
        }

        $type = TicketType::query()->active()->orderByRaw("slug = 'incident' desc")->orderBy('id')->first();
        $priority = TicketPriority::query()->active()->ordered()->first();
        $statusNew = TicketStatus::query()->where('slug', TicketStatus::SLUG_NEW)->first();

        if (! $type || ! $priority || ! $statusNew) {
            throw new RuntimeException('Konfigurasi tiket belum lengkap (tipe/prioritas/status).');
        }

        $category = TicketCategory::query()
            ->active()
            ->where(function ($q) use ($type) {
                $q->whereNull('ticket_type_id')
                    ->orWhere('ticket_type_id', $type->id);
            })
            ->orderByRaw('dep_id = ? desc', [$user->dep_id ?? 'IT'])
            ->orderBy('id')
            ->first();

        $depId = $category?->dep_id ?? ($user->dep_id ?: 'IT');
        $ticketDescriptionPrefix = "Permintaan dibuat via Telegram oleh {$user->name} ({$user->email}).\nPemohon aktual (manual): {$requestedBy}\nMohon finalisasi pemohon aktual di web setelah tiket dibuat.";
        $ticketDescription = trim($ticketDescriptionPrefix."\n\n".($description !== '' ? $description : '(Tanpa deskripsi tambahan)'));

        $ticket = Ticket::query()->create([
            'ticket_type_id' => $type->id,
            'ticket_category_id' => $category?->id,
            'ticket_priority_id' => $priority->id,
            'ticket_status_id' => $statusNew->id,
            'dep_id' => $depId,
            'requester_id' => $user->id,
            'title' => Str::limit($title, 255, '...'),
            'description' => Str::limit($ticketDescription, 10000, '...'),
        ]);

        $ticket->logActivity(
            TicketActivity::ACTION_CREATED,
            null,
            null,
            'Tiket dibuat via Telegram (pemohon sementara, perlu finalisasi pemohon).',
            $user->id
        );

        try {
            User::query()
                ->where('role', 'staff')
                ->where('dep_id', $depId)
                ->get()
                ->each(fn (User $staff) => $staff->notify(new TicketCreatedNotification($ticket)));

            TicketTelegramGroupNotifier::notifyNewTicket($ticket);
        } catch (\Throwable) {
            // Tiket sudah tersimpan; notifikasi boleh gagal.
        }

        return $ticket;
    }
}
