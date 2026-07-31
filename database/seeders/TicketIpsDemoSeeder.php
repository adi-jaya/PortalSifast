<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dummy tiket penanganan IPS untuk uji filter IT/IPS.
 *
 * Jalankan: php artisan db:seed --class=TicketIpsDemoSeeder --force
 */
class TicketIpsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $requesters = User::query()
            ->whereIn('role', ['pemohon', 'staff', 'admin'])
            ->orderBy('id')
            ->limit(30)
            ->get();

        if ($requesters->isEmpty()) {
            $this->command?->error('Tidak ada user untuk dijadikan pemohon. Buat user dulu.');

            return;
        }

        $ipsStaff = User::query()
            ->where('role', 'staff')
            ->where('dep_id', 'IPS')
            ->orderBy('id')
            ->get();

        $types = TicketType::query()->where('is_active', true)->get()->keyBy('slug');
        $priorities = TicketPriority::query()->where('is_active', true)->ordered()->get();
        $statuses = TicketStatus::query()->where('is_active', true)->get()->keyBy('slug');

        $categories = TicketCategory::query()
            ->where('dep_id', 'IPS')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($categories->isEmpty()) {
            $this->command?->error('Kategori IPS belum ada. Jalankan TicketingSeeder dulu.');

            return;
        }

        $samples = [
            [
                'title' => 'AC ruang IGD tidak dingin',
                'description' => 'AC di ruang IGD hanya mengeluarkan angin hangat sejak pagi. Suhu ruangan naik.',
                'category' => 'AC',
                'type' => 'incident',
                'status' => 'new',
                'days_ago' => 0,
            ],
            [
                'title' => 'Pendingin farmasi bocor air',
                'description' => 'Ada genangan di bawah unit pendingin gudang farmasi. Mohon dicek pipa drain.',
                'category' => 'AC',
                'type' => 'incident',
                'status' => 'assigned',
                'days_ago' => 1,
            ],
            [
                'title' => 'Permintaan servis berkala AC poli anak',
                'description' => 'Mohon jadwalkan pembersihan filter AC poli anak sebelum akhir bulan.',
                'category' => 'AC',
                'type' => 'service_request',
                'status' => 'in_progress',
                'days_ago' => 3,
            ],
            [
                'title' => 'Defibrillator ruang resusitasi error',
                'description' => 'Alat menampilkan error battery. Sudah dicoba charge ulang, tetap sama.',
                'category' => 'Alat Medis',
                'type' => 'incident',
                'status' => 'new',
                'days_ago' => 0,
            ],
            [
                'title' => 'Infus pump sering alarm occlude',
                'description' => 'Infus pump di ruang rawat sering alarm tanpa sumbatan jelas. Perlu kalibrasi/cek.',
                'category' => 'Alat Medis',
                'type' => 'problem',
                'status' => 'pending',
                'days_ago' => 5,
            ],
            [
                'title' => 'Kursi roda rusak di lobby',
                'description' => 'Roda depan macet dan rem tidak mengunci. Mohon diperbaiki atau diganti.',
                'category' => 'Peralatan Kantor',
                'type' => 'incident',
                'status' => 'assigned',
                'days_ago' => 2,
            ],
            [
                'title' => 'Meja poli goyang / kaki patah',
                'description' => 'Kaki meja di poli umum goyang. Perlu pengelasan/penggantian.',
                'category' => 'Peralatan Kantor',
                'type' => 'service_request',
                'status' => 'resolved',
                'days_ago' => 7,
            ],
            [
                'title' => 'Lampu ruang operasi berkedip',
                'description' => 'Lampu di koridor dekat OK berkedip. Dikhawatirkan masalah kelistrikan.',
                'category' => 'Listrik',
                'type' => 'incident',
                'status' => 'in_progress',
                'days_ago' => 1,
            ],
            [
                'title' => 'Stop kontak pantri longgar',
                'description' => 'Stop kontak di pantri lantai 2 longgar dan panas saat dipakai.',
                'category' => 'Listrik',
                'type' => 'incident',
                'status' => 'new',
                'days_ago' => 0,
            ],
            [
                'title' => 'Plafon bocor dekat lift',
                'description' => 'Ada tetesan air dari plafon dekat lift lantai 1 setelah hujan.',
                'category' => 'Sarana Bangunan',
                'type' => 'incident',
                'status' => 'assigned',
                'days_ago' => 2,
            ],
            [
                'title' => 'Permintaan perbaikan pintu toilet',
                'description' => 'Engsel pintu toilet pengunjung rusak, pintu tidak menutup sempurna.',
                'category' => 'Sarana Bangunan',
                'type' => 'service_request',
                'status' => 'waiting_confirmation',
                'days_ago' => 4,
            ],
            [
                'title' => 'Saluran wastafel tersumbat',
                'description' => 'Wastafel di ruang perawat tersumbat, air meluber.',
                'category' => 'Pipa',
                'type' => 'incident',
                'status' => 'in_progress',
                'days_ago' => 1,
            ],
            [
                'title' => 'Kran air bocor di dapur gizi',
                'description' => 'Kran cuci piring bocor terus. Perlu diganti seal/kran.',
                'category' => 'Pipa',
                'type' => 'service_request',
                'status' => 'closed',
                'days_ago' => 10,
            ],
            [
                'title' => 'Mesin cuci laundry tidak spin',
                'description' => 'Mesin cuci di laundry berhenti di siklus spin. Sudah di-reset, tetap gagal.',
                'category' => 'Mesin Cuci',
                'type' => 'incident',
                'status' => 'new',
                'days_ago' => 0,
            ],
            [
                'title' => 'Mesin pengering berisik',
                'description' => 'Ada bunyi gesekan keras saat mesin pengering beroperasi.',
                'category' => 'Mesin Cuci',
                'type' => 'problem',
                'status' => 'pending',
                'days_ago' => 6,
            ],
            [
                'title' => 'Permintaan pemasangan gorden baru',
                'description' => 'Mohon pasang gorden di ruang tunggu poli gigi (sudah tersedia bahannya).',
                'category' => 'Lainnya',
                'type' => 'service_request',
                'status' => 'assigned',
                'days_ago' => 3,
            ],
            [
                'title' => 'Bau tidak sedap di ruang sampah medis',
                'description' => 'Area TPS medis berbau menyengat. Perlu pengecekan drainase/ventilasi.',
                'category' => 'Lainnya',
                'type' => 'incident',
                'status' => 'in_progress',
                'days_ago' => 2,
            ],
            [
                'title' => 'Usulan penggantian panel listrik lantai 2',
                'description' => 'Panel lama sering trip. Diusulkan penggantian sesuai rekomendasi teknisi.',
                'category' => 'Listrik',
                'type' => 'change_request',
                'status' => 'pending',
                'days_ago' => 8,
            ],
            [
                'title' => 'Kalibrasi tensimeter digital',
                'description' => 'Beberapa tensimeter digital hasilnya tidak konsisten. Mohon kalibrasi.',
                'category' => 'Alat Medis',
                'type' => 'service_request',
                'status' => 'resolved',
                'days_ago' => 9,
            ],
            [
                'title' => 'AC ruang rapat mati total',
                'description' => 'Remote dan unit indoor tidak nyala. Suspect kompresor / listrik.',
                'category' => 'AC',
                'type' => 'incident',
                'status' => 'closed',
                'days_ago' => 12,
            ],
        ];

        $created = 0;

        foreach ($samples as $index => $sample) {
            $category = $categories->first(
                fn (TicketCategory $c) => str_contains(mb_strtolower($c->name), mb_strtolower($sample['category']))
            ) ?? $categories->first();

            $type = $types->get($sample['type']) ?? $types->first();
            $status = $statuses->get($sample['status']) ?? $statuses->get('new');
            $priority = $priorities[$index % max(1, $priorities->count())];
            $requester = $requesters[$index % $requesters->count()];

            $assigneeId = null;
            if (in_array($sample['status'], ['assigned', 'in_progress', 'pending', 'resolved', 'waiting_confirmation', 'closed'], true)
                && $ipsStaff->isNotEmpty()) {
                $assigneeId = $ipsStaff[$index % $ipsStaff->count()]->id;
            }

            $createdAt = now()->subDays($sample['days_ago'])->subHours($index % 8);
            $closedAt = null;
            $resolvedAt = null;

            if (in_array($sample['status'], ['resolved', 'waiting_confirmation', 'closed'], true)) {
                $resolvedAt = (clone $createdAt)->addHours(6 + ($index % 20));
            }
            if ($sample['status'] === 'closed') {
                $closedAt = (clone ($resolvedAt ?? $createdAt))->addHours(2);
            }

            Ticket::query()->create([
                'ticket_type_id' => $type->id,
                'ticket_category_id' => $category->id,
                'ticket_priority_id' => $priority->id,
                'ticket_status_id' => $status->id,
                'dep_id' => 'IPS',
                'requester_id' => $requester->id,
                'assignee_id' => $assigneeId,
                'title' => '[DEMO IPS] '.$sample['title'],
                'description' => $sample['description'],
                'is_draft' => false,
                'published_at' => $createdAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
                'resolved_at' => $resolvedAt,
                'closed_at' => $closedAt,
            ]);

            $created++;
        }

        $this->command?->info("Berhasil membuat {$created} tiket dummy IPS.");
        $this->command?->info('Filter di /tickets → tab IPS untuk melihatnya.');
    }
}
