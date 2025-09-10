<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

it('can run setup command interactively', function () {
    // Mock the interactive input
    $this->artisan('idn-area:setup', ['--force' => true, '--skip-seeding' => true])
        ->expectsChoice('Which mode do you prefer?', 'Local Data Mode (Recommended)', [
            'Local Data Mode (Recommended)',
            'API Mode (Real-time)',
        ])
        ->assertSuccessful();
});

it('can run setup command with local mode parameter', function () {
    $this->artisan('idn-area:setup', [
        '--mode' => 'local',
        '--force' => true,
        '--skip-seeding' => true,
    ])->assertSuccessful();
});

it('can run setup command with api mode parameter', function () {
    $this->artisan('idn-area:setup', [
        '--mode' => 'api',
        '--force' => true,
        '--access-key' => 'test_access_key',
        '--secret-key' => 'test_secret_key',
        '--token-storage' => 'cache',
        '--skip-connectivity' => true,
    ])->assertSuccessful();
});

it('validates invalid mode parameter', function () {
    $this->artisan('idn-area:setup', [
        '--mode' => 'invalid',
        '--force' => true,
    ])->assertFailed();
});

it('skips setup when already configured without force', function () {
    // Create a fake migration file to simulate already setup
    File::put(database_path('migrations/create_idn_provinces_table.php'), '<?php // fake migration');

    $this->artisan('idn-area:setup')
        ->expectsOutput('✅ Package is already configured!')
        ->assertSuccessful();

    // Cleanup
    File::delete(database_path('migrations/create_idn_provinces_table.php'));
});

it('can force reconfigure when already setup', function () {
    // Create a fake migration file to simulate already setup
    File::put(database_path('migrations/create_idn_provinces_table.php'), '<?php // fake migration');

    $this->artisan('idn-area:setup', [
        '--mode' => 'local',
        '--force' => true,
        '--skip-seeding' => true,
    ])->assertSuccessful();

    // Cleanup
    File::delete(database_path('migrations/create_idn_provinces_table.php'));
});

it('handles non-interactive mode correctly', function () {
    // Test that non-interactive mode defaults to local
    $exitCode = Artisan::call('idn-area:setup', [
        '--force' => true,
        '--skip-seeding' => true,
    ]);

    expect($exitCode)->toBe(0);
});
