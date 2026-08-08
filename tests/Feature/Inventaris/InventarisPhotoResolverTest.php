<?php

use App\Services\InventarisPhotoResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('converts simrs relative photo path to base64 data uri', function () {
    config(['inventaris.asset_base_url' => 'http://192.168.10.3/webapps2/inventaris']);

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    Http::fake([
        'http://192.168.10.3/webapps2/inventaris/pages/upload/cmm.jpg' => Http::response($png, 200, [
            'Content-Type' => 'image/png',
        ]),
    ]);

    $uri = app(InventarisPhotoResolver::class)->toDataUri('pages/upload/cmm.jpg');

    expect($uri)->toStartWith('data:image/')
        ->and($uri)->toContain(';base64,')
        ->and($uri)->not->toContain('192.168.10.3');
});

it('converts local portal upload to base64 data uri', function () {
    Storage::fake('public');

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    Storage::disk('public')->put('inventaris/test.png', $png);

    $uri = app(InventarisPhotoResolver::class)->toDataUri('inventaris/test.png');

    expect($uri)->toStartWith('data:image/')
        ->and($uri)->toContain(';base64,');
});

it('returns null when remote photo is missing', function () {
    config(['inventaris.asset_base_url' => 'http://192.168.10.3/webapps2/inventaris']);

    Http::fake([
        'http://192.168.10.3/webapps2/inventaris/pages/upload/missing.jpg' => Http::response('not found', 404),
    ]);

    expect(app(InventarisPhotoResolver::class)->toDataUri('pages/upload/missing.jpg'))->toBeNull();
});
