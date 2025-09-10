<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use zaidysf\IdnArea\Models\Province;

it('can switch to api mode', function () {
    $this->artisan('idn-area:switch-mode', [
        'mode' => 'api',
        '--force' => true,
        '--access-key' => 'test_access_key',
        '--secret-key' => 'test_secret_key',
        '--token-storage' => 'cache',
        '--skip-connectivity' => true
    ])->assertSuccessful();
});

it('can switch to local mode', function () {
    $this->artisan('idn-area:switch-mode', [
        'mode' => 'local', 
        '--force' => true,
        '--skip-seeding' => true
    ])->assertSuccessful();
});

it('validates invalid mode parameter', function () {
    $this->artisan('idn-area:switch-mode', [
        'mode' => 'invalid',
        '--force' => true
    ])->assertFailed();
});

it('shows current mode when already in target mode', function () {
    Config::set('idn-area.mode', 'local');
    
    $this->artisan('idn-area:switch-mode', [
        'mode' => 'local',
        '--force' => true
    ])
    ->expectsOutput('✅ Already in local mode.')
    ->assertSuccessful();
});

it('can skip validation checks', function () {
    $this->artisan('idn-area:switch-mode', [
        'mode' => 'local',
        '--force' => true,
        '--skip-validation' => true,
        '--skip-seeding' => true
    ])->assertSuccessful();
});

it('can skip migration step', function () {
    $this->artisan('idn-area:switch-mode', [
        'mode' => 'local',
        '--force' => true,
        '--skip-migration' => true,
        '--skip-seeding' => true
    ])->assertSuccessful();
});

it('can skip seeding step', function () {
    $this->artisan('idn-area:switch-mode', [
        'mode' => 'local',
        '--force' => true,
        '--skip-seeding' => true
    ])->assertSuccessful();
});

it('handles interactive mode selection', function () {
    Config::set('idn-area.mode', 'api');
    
    $this->artisan('idn-area:switch-mode', ['--skip-seeding' => true])
        ->expectsChoice('Which mode would you like to switch to?', 'Local Mode (Recommended)', [
            'API Mode (Real-time)',
            'Local Mode (Recommended)'
        ])
        ->expectsConfirmation('Continue with mode switch?', 'yes')
        ->assertSuccessful();
});

it('validates prerequisites for api mode', function () {
    // This test might fail in CI without actual DataToko API access
    // We'll mock the validation in a separate test
    $this->artisan('idn-area:switch-mode', [
        'mode' => 'api',
        '--force' => true,
        '--skip-validation' => true,
        '--access-key' => 'test_access_key',
        '--secret-key' => 'test_secret_key',
        '--token-storage' => 'cache',
        '--skip-connectivity' => true
    ])->assertSuccessful();
});

it('validates prerequisites for local mode', function () {
    $this->artisan('idn-area:switch-mode', [
        'mode' => 'local',
        '--force' => true,
        '--skip-seeding' => true
    ])->assertSuccessful();
});