<?php

// Basic package functionality test
it('package is properly configured', function () {
    expect(true)->toBeTrue();

    // Test that package service provider is loaded
    $providers = app()->getLoadedProviders();
    expect($providers)->toHaveKey('zaidysf\\IdnArea\\IdnAreaServiceProvider');
});

it('config is published correctly', function () {
    $config = config('idn-area');

    expect($config)->toBeArray();
    expect($config)->toHaveKey('database.table_prefix');
    expect($config)->toHaveKey('database.enable_foreign_keys');
    expect($config)->toHaveKey('models');
});

it('database tables exist', function () {
    $tables = [
        'idn_provinces',
        'idn_regencies',
        'idn_districts',
        'idn_villages',
        'idn_islands',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }
});
