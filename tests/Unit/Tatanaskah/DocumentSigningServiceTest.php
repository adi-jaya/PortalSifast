<?php

use App\Services\Tatanaskah\DocumentSigningService;
use App\Services\Tatanaskah\StirlingPdfService;

uses(Tests\TestCase::class);

it('reports signing capabilities from config and stirling service', function () {
    config([
        'services.stirling_pdf.url' => 'http://stirling.test',
        'services.stirling_pdf.api_key' => 'test-key',
        'services.stirling_pdf.signature.enable_cert_sign' => false,
        'services.stirling_pdf.signature.image_path' => '',
    ]);

    $service = app(DocumentSigningService::class);

    expect($service->capabilities())->toBe([
        'cert' => false,
        'image' => false,
        'timestamp' => true,
    ]);
});

it('detects cert signing when p12 is configured', function () {
    $p12 = tempnam(sys_get_temp_dir(), 'cert').'.p12';
    file_put_contents($p12, 'fake-cert');

    config([
        'services.stirling_pdf.url' => 'http://stirling.test',
        'services.stirling_pdf.api_key' => 'test-key',
        'services.stirling_pdf.signature.enable_cert_sign' => true,
        'services.stirling_pdf.cert.type' => 'custom',
        'services.stirling_pdf.cert.p12_path' => $p12,
        'services.stirling_pdf.cert.password' => 'secret',
    ]);

    expect(app(StirlingPdfService::class)->isCertSigningConfigured())->toBeTrue();

    @unlink($p12);
});
