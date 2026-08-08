<?php

use App\Models\Inventaris;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    try {
        Inventaris::query()->limit(1)->get();
    } catch (\Throwable) {
        $this->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }
});

it('can export inventaris csv', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/inventaris/export')
        ->assertOk()
        ->assertHeader('content-disposition');
});
