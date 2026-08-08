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
     * Buat tiket dari Telegram.
     *
     * @param  User  $actor  Staff yang membuat via Telegram
     * @param  User|null  $requester  Pemohon dari DB; null = pakai $actor (teks manual / sementara)
     *
     * @throws RuntimeException
     */
    public function create(
        User $actor,
        string $title,
        string $description,
        string $requestedByLabel,
        ?User $requester = null,
    ): Ticket {
        $title = trim($title);
        $requestedByLabel = trim($requestedByLabel);
        $description = trim($description);

        if ($title === '') {
            throw new RuntimeException('Judul tiket kosong.');
        }

        if ($requestedByLabel === '' && $requester === null) {
            throw new RuntimeException('Pemohon wajib dipilih atau diisi.');
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
            ->orderByRaw('dep_id = ? desc', [$actor->dep_id ?? 'IT'])
            ->orderBy('id')
            ->first();

        $depId = $category?->dep_id ?? ($actor->dep_id ?: 'IT');
        $requesterModel = $requester ?? $actor;
        $resolvedFromDb = $requester !== null;

        if ($resolvedFromDb) {
            $ticketDescriptionPrefix = "Permintaan dibuat via Telegram oleh {$actor->name} ({$actor->email}).\nPemohon: {$requesterModel->name}"
                .($requesterModel->simrs_nik ? " (NIK: {$requesterModel->simrs_nik})" : '')
                .($requesterModel->dep_id ? " · {$requesterModel->dep_id}" : '');
            $activityNote = 'Tiket dibuat via Telegram Jarvis (pemohon dipilih dari database).';
        } else {
            $label = $requestedByLabel !== '' ? $requestedByLabel : $actor->name;
            $ticketDescriptionPrefix = "Permintaan dibuat via Telegram oleh {$actor->name} ({$actor->email}).\nPemohon aktual (manual): {$label}\nMohon finalisasi pemohon aktual di web setelah tiket dibuat.";
            $activityNote = 'Tiket dibuat via Telegram (pemohon sementara, perlu finalisasi pemohon).';
        }

        $ticketDescription = trim($ticketDescriptionPrefix."\n\n".($description !== '' ? $description : '(Tanpa deskripsi tambahan)'));

        $ticket = Ticket::query()->create([
            'ticket_type_id' => $type->id,
            'ticket_category_id' => $category?->id,
            'ticket_priority_id' => $priority->id,
            'ticket_status_id' => $statusNew->id,
            'dep_id' => $depId,
            'requester_id' => $requesterModel->id,
            'title' => Str::limit($title, 255, '...'),
            'description' => Str::limit($ticketDescription, 10000, '...'),
        ]);

        $ticket->logActivity(
            TicketActivity::ACTION_CREATED,
            null,
            null,
            $activityNote,
            $actor->id
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
