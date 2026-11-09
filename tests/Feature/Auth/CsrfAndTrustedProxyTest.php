<?php

use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

test('laravel recognizes https when x-forwarded-proto is sent through reverse proxy', function () {
    // Send a request via HTTP with X-Forwarded-Proto: https from an untrusted IP by default
    $response = $this->withServerVariables([
        'REMOTE_ADDR' => '172.18.0.2',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.195',
    ])->get('/');

    expect(request()->isSecure())->toBeTrue();
});

test('http 419 page expired exception redirects back gracefully with flash message', function () {
    Route::post('/test-csrf-expiration', function () {
        throw new TokenMismatchException('CSRF token mismatch');
    })->middleware('web');

    $response = $this->from('/login')->post('/test-csrf-expiration', [
        '_token' => 'invalid-token',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHas('message', 'Sesi Anda telah berakhir. Silakan coba kembali.');
});
