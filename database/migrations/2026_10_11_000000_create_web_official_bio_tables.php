<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_official_bio_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 60);
            $table->string('description', 100)->nullable();
            $table->string('url', 500);
            $table->string('icon', 32)->default('link');
            $table->string('image_url', 500)->nullable();
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('web_official_bio_socials', function (Blueprint $table) {
            $table->string('platform', 16)->primary();
            $table->string('url', 500)->default('');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Data awal = data statis frontend, supaya tampilan /bio tidak berubah saat endpoint aktif.
        $now = now();
        $links = [
            ['Chat WhatsApp', 'Tanya layanan dan informasi RS', 'https://wa.me/6281234501005', 'whatsapp'],
            ['Jadwal Dokter', 'Cek jadwal praktik dokter spesialis', '/dokter', 'stethoscope'],
            ['Klinik Spesialis', 'Daftar klinik dan layanan spesialis', '/klinik-spesialis', 'hospital'],
            ['Info Kamar Inap', 'Kelas kamar dan ketersediaan', '/kamar-inap', 'bed'],
            ['Tulis Ulasan Google', 'Bagikan pengalaman Anda di Google', 'https://g.page/r/CTC8p1RViQVqEBM/review', 'star'],
            ['Kritik & Saran', 'Sampaikan masukan untuk pelayanan kami', '/kritik-saran', 'message-square'],
        ];

        DB::table('web_official_bio_links')->insert(array_map(fn (array $link, int $i): array => [
            'id' => (string) Str::uuid(),
            'title' => $link[0],
            'description' => $link[1],
            'url' => $link[2],
            'icon' => $link[3],
            'sort_order' => $i + 1,
            'is_active' => true,
            'featured' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], $links, array_keys($links)));

        DB::table('web_official_bio_socials')->insert([
            ['platform' => 'instagram', 'url' => 'https://instagram.com/rsasitifatimah', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform' => 'facebook', 'url' => '', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
            ['platform' => 'youtube', 'url' => '', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
            ['platform' => 'tiktok', 'url' => '', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('web_official_bio_socials');
        Schema::dropIfExists('web_official_bio_links');
    }
};
