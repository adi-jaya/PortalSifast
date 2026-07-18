<?php

use App\Models\Inventaris;
use App\Models\InventarisGambar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function inventarisGambarWriteAllowed(): bool
{
    try {
        $no = Inventaris::query()->value('no_inventaris');
        if (! $no) {
            return false;
        }

        DB::connection('dbsimrs')->table('inventaris_gambar')->updateOrInsert(
            ['no_inventaris' => $no],
            ['photo' => 'probe-test.jpg']
        );
        DB::connection('dbsimrs')->table('inventaris_gambar')->where('no_inventaris', $no)->where('photo', 'probe-test.jpg')->delete();

        return true;
    } catch (\Throwable) {
        return false;
    }
}

beforeEach(function () {
    try {
        Inventaris::query()->limit(1)->get();
    } catch (\Throwable) {
        $this->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }
});

it('can upload and delete inventaris photo', function () {
    if (! inventarisGambarWriteAllowed()) {
        $this->markTestSkipped('dbsimrs user cannot write inventaris_gambar.');
    }

    Storage::fake('public');

    $user = User::factory()->create();
    $inventaris = Inventaris::query()->first();

    if (! $inventaris) {
        $this->markTestSkipped('No inventaris sample data available.');
    }

    $file = UploadedFile::fake()->image('aset.jpg', 200, 200);

    actingAs($user)
        ->post('/inventaris/'.$inventaris->no_inventaris.'/gambar', [
            'photo' => $file,
        ])
        ->assertRedirect(route('inventaris.show', $inventaris->no_inventaris));

    $gambar = InventarisGambar::query()->find($inventaris->no_inventaris);
    expect($gambar)->not->toBeNull();
    Storage::disk('public')->assertExists($gambar->photo);

    actingAs($user)
        ->delete('/inventaris/'.$inventaris->no_inventaris.'/gambar')
        ->assertRedirect(route('inventaris.show', $inventaris->no_inventaris));

    expect(InventarisGambar::query()->find($inventaris->no_inventaris))->toBeNull();
});

it('serves inventaris photo proxy without 404 when simrs file is missing', function () {
    $user = User::factory()->create();

    $missing = Inventaris::query()
        ->whereHas('gambar', fn ($q) => $q->where('photo', 'pages/upload/1736914521_1.jpeg'))
        ->first();

    if (! $missing) {
        $this->markTestSkipped('No inventaris with known-missing SIMRS photo available.');
    }

    actingAs($user)
        ->get(route('inventaris.photo', $missing->no_inventaris))
        ->assertOk()
        ->assertHeader('content-type', 'image/png')
        ->assertHeader('X-Inventaris-Photo', 'missing');
});
