<?php

use Illuminate\Support\Facades\Schema;

it('creates portals table with expected columns and indices', function (): void {
    expect(Schema::hasTable('portals'))->toBeTrue();

    $expectedColumns = [
        'id',
        'name',
        'slug',
        'category',
        'url',
        'url_pattern',
        'icon_path',
        'description',
        'auth_type',
        'shared_username',
        'shared_password',
        'shared_extra_fields',
        'form_config',
        'is_active',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    foreach ($expectedColumns as $column) {
        expect(Schema::hasColumn('portals', $column))
            ->toBeTrue("Column {$column} is missing on portals table.");
    }
});

it('creates user_portal_credentials table with expected columns and foreign keys', function (): void {
    expect(Schema::hasTable('user_portal_credentials'))->toBeTrue();

    $expectedColumns = [
        'id',
        'user_id',
        'portal_id',
        'credential_type',
        'personal_username',
        'personal_password',
        'personal_extra_fields',
        'is_active',
        'notes',
        'created_at',
        'updated_at',
    ];

    foreach ($expectedColumns as $column) {
        expect(Schema::hasColumn('user_portal_credentials', $column))
            ->toBeTrue("Column {$column} is missing on user_portal_credentials table.");
    }
});
