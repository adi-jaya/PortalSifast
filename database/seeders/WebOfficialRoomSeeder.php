<?php

namespace Database\Seeders;

use App\Models\WebOfficialRoom;
use Illuminate\Database\Seeder;

class WebOfficialRoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            [
                'slug' => 'president-suite',
                'name' => 'President Suite',
                'tagline' => 'Pilihan Terbaik',
                'badge' => 'Disarankan',
                'description' => 'Kamar perawatan paling eksklusif dengan desain modern dan homey.',
                'price' => 2_500_000,
                'photo_url' => 'https://cdn.rsasitifatimah.id/kamar/president-suite.jpg',
                'facilities' => ['AC', 'Smart TV 65"', 'WiFi', 'Kamar Mandi Dalam', 'Air Panas', 'Kulkas', 'Sofa Tamu', 'Kitchen Set', 'Nurse Call'],
                'sort_order' => 1,
            ],
            [
                'slug' => 'vip-a',
                'name' => 'VIP A',
                'description' => 'Kamar klasik dan elegan dengan fasilitas lengkap.',
                'price' => 1_100_000,
                'photo_url' => 'https://cdn.rsasitifatimah.id/kamar/vip-a.jpg',
                'facilities' => ['AC', 'TV LED 47"', 'WiFi', 'Kamar Mandi Dalam', 'Air Panas'],
                'sort_order' => 2,
            ],
            [
                'slug' => 'suite-room',
                'name' => 'Suite Room',
                'description' => 'Kamar suite nyaman untuk perawatan inap.',
                'price' => 1_500_000,
                'photo_url' => 'https://cdn.rsasitifatimah.id/kamar/suite-room.jpg',
                'facilities' => ['AC', 'TV', 'WiFi', 'Kamar Mandi Dalam', 'Kulkas'],
                'sort_order' => 3,
            ],
            [
                'slug' => 'kelas-1',
                'name' => 'Kelas I',
                'description' => 'Kamar kelas satu dengan fasilitas standar rumah sakit.',
                'price' => 550_000,
                'photo_url' => 'https://cdn.rsasitifatimah.id/kamar/kelas-1.jpg',
                'facilities' => ['AC', 'TV', 'Kamar Mandi Dalam'],
                'sort_order' => 4,
            ],
            [
                'slug' => 'kelas-2',
                'name' => 'Kelas II',
                'description' => 'Kamar kelas dua yang nyaman dan terjangkau.',
                'price' => 350_000,
                'photo_url' => 'https://cdn.rsasitifatimah.id/kamar/kelas-2.jpg',
                'facilities' => ['AC', 'Kamar Mandi Dalam'],
                'sort_order' => 5,
            ],
            [
                'slug' => 'kelas-3',
                'name' => 'Kelas III',
                'description' => 'Kamar kelas tiga untuk perawatan inap ekonomis.',
                'price' => 200_000,
                'photo_url' => 'https://cdn.rsasitifatimah.id/kamar/kelas-3.jpg',
                'facilities' => ['Kipas Angin', 'Kamar Mandi Bersama'],
                'sort_order' => 6,
            ],
        ];

        foreach ($rooms as $room) {
            WebOfficialRoom::query()->updateOrCreate(
                ['slug' => $room['slug']],
                array_merge($room, ['is_active' => true]),
            );
        }
    }
}
