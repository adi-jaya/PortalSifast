<?php

use App\Services\Simrs\PegawaiPhotoDataUriService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    config()->set('services.simrs.pegawai_photo_base_url', 'http://192.168.10.3/webapps2/penggajian');
    Cache::flush();
});

test('buildPhotoUrl encodes spaces in filename', function (): void {
    $service = app(PegawaiPhotoDataUriService::class);

    expect($service->buildPhotoUrl('pages/pegawai/photo/dr hud.jpg'))
        ->toBe('http://192.168.10.3/webapps2/penggajian/pages/pegawai/photo/dr%20hud.jpg');
});

test('toDataUri returns base64 data uri from remote photo', function (): void {
    Http::fake([
        'http://192.168.10.3/webapps2/penggajian/pages/pegawai/photo/dr%20hud.jpg' => Http::response(
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='),
            200,
            ['Content-Type' => 'image/png'],
        ),
    ]);

    $service = app(PegawaiPhotoDataUriService::class);
    $dataUri = $service->toDataUri('pages/pegawai/photo/dr hud.jpg');

    expect($dataUri)
        ->toStartWith('data:image/png;base64,')
        ->and(base64_decode(substr((string) $dataUri, strlen('data:image/png;base64,'))))
        ->not->toBeEmpty();
});

test('toDataUri returns null when photo path empty', function (): void {
    $service = app(PegawaiPhotoDataUriService::class);

    expect($service->toDataUri(null))->toBeNull()
        ->and($service->toDataUri(''))->toBeNull();
});

test('toDataUri returns null when remote photo missing', function (): void {
    Http::fake([
        '*' => Http::response('', 404),
    ]);

    $service = app(PegawaiPhotoDataUriService::class);

    expect($service->toDataUri('pages/pegawai/photo/missing.jpg'))->toBeNull();
});
