<?php

use App\Services\BerkasKepegawaian\WebappsBerkasPegawaiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    config()->set('services.berkas_pegawai.receiver_url', 'http://webapps.test/receiveberkaspegawai.php');
    config()->set('services.berkas_pegawai.receiver_token', 'secret-token');
    config()->set('services.berkas_pegawai.timeout', 10);
});

it('uploads dokumen with token and target', function (): void {
    Http::fake([
        'http://webapps.test/receiveberkaspegawai.php' => Http::response([
            'success' => true,
            'filename' => 'nik_kode_20260101.pdf',
            'target' => 'berkaspegawai',
            'path' => '/var/www/html/webapps2/penggajian/pages/berkaspegawai/berkas/nik_kode_20260101.pdf',
        ], 200),
    ]);

    $file = UploadedFile::fake()->createWithContent('ijazah.pdf', '%PDF-1.4 test content');
    $client = new WebappsBerkasPegawaiClient;
    $result = $client->upload($file, 'nik_kode_20260101.pdf');

    expect($result['success'])->toBeTrue()
        ->and($result['filename'])->toBe('nik_kode_20260101.pdf');

    Http::assertSent(function (Request $request): bool {
        $parts = collect($request->data());
        $target = $parts->firstWhere('name', 'target')['contents'] ?? null;
        $hasDokumen = $parts->contains(
            fn (array $part): bool => ($part['name'] ?? null) === 'dokumen'
        );

        return $request->url() === 'http://webapps.test/receiveberkaspegawai.php'
            && $request->hasHeader('X-Webapps-Token', 'secret-token')
            && $target === 'berkaspegawai'
            && $hasDokumen;
    });
});

it('deletes remote file by basename', function (): void {
    Http::fake([
        'http://webapps.test/receiveberkaspegawai.php' => Http::response([
            'success' => true,
            'filename' => 'old.pdf',
            'target' => 'berkaspegawai',
        ], 200),
    ]);

    $client = new WebappsBerkasPegawaiClient;
    $result = $client->delete('pages/berkaspegawai/berkas/old.pdf');

    expect($result['success'])->toBeTrue();

    Http::assertSent(function (Request $request): bool {
        return $request['action'] === 'delete'
            && $request['filename'] === 'old.pdf'
            && $request['target'] === 'berkaspegawai';
    });
});

it('throws when receiver returns failure', function (): void {
    Http::fake([
        'http://webapps.test/receiveberkaspegawai.php' => Http::response([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401),
    ]);

    $file = UploadedFile::fake()->createWithContent('x.pdf', '%PDF-1.4 x');
    $client = new WebappsBerkasPegawaiClient;

    $client->upload($file, 'x.pdf');
})->throws(RuntimeException::class);
