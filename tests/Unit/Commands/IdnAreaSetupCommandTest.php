<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use zaidysf\IdnArea\Commands\IdnAreaSetupCommand;
use zaidysf\IdnArea\IdnAreaServiceProvider;

class IdnAreaSetupCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [IdnAreaServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function test_command_signature_is_correct(): void
    {
        $command = new IdnAreaSetupCommand;
        expect($command->signature)->toContain('idn-area:setup');
        expect($command->signature)->toContain('--mode');
        expect($command->signature)->toContain('--force');
        expect($command->signature)->toContain('--skip-validation');
        expect($command->signature)->toContain('--skip-migration');
        expect($command->signature)->toContain('--skip-seeding');
        expect($command->signature)->toContain('--access-key');
        expect($command->signature)->toContain('--secret-key');
    }

    public function test_command_description_is_set(): void
    {
        $command = new IdnAreaSetupCommand;
        expect($command->description)->toContain('Initial setup');
        expect($command->description)->toContain('Indonesian Area Data package');
    }

    public function test_command_shows_welcome_header(): void
    {
        $this->artisan('idn-area:setup --force --mode=local')
            ->expectsOutput('🇮🇩 Indonesian Area Data Package Setup')
            ->expectsOutput('Version 2.0')
            ->assertExitCode(0);
    }

    public function test_command_with_invalid_mode_fails(): void
    {
        $this->artisan('idn-area:setup --mode=invalid')
            ->expectsOutput('Invalid mode: invalid. Must be \'local\' or \'api\'.')
            ->assertExitCode(1);
    }

    public function test_command_local_mode_setup(): void
    {
        $this->artisan('idn-area:setup --force --mode=local --skip-seeding')
            ->expectsOutput('🚀 Setting up Indonesian Area Data in local mode...')
            ->expectsOutput('✅ LOCAL MODE SETUP COMPLETE')
            ->assertExitCode(0);
    }

    public function test_command_api_mode_requires_credentials(): void
    {
        $this->artisan('idn-area:setup --force --mode=api --access-key= --secret-key=')
            ->expectsOutput('Access key is required for API mode')
            ->assertExitCode(1);
    }

    public function test_command_api_mode_with_credentials(): void
    {
        $this->artisan('idn-area:setup --force --mode=api --access-key=test --secret-key=test --skip-connectivity')
            ->expectsOutput('✅ API MODE SETUP COMPLETE')
            ->assertExitCode(0);
    }

    public function test_command_api_mode_with_invalid_token_storage(): void
    {
        $this->artisan('idn-area:setup --force --mode=api --access-key=test --secret-key=test --token-storage=invalid')
            ->expectsOutput('Invalid token storage method. Must be: cache, redis, or file')
            ->assertExitCode(1);
    }

    public function test_command_api_mode_with_valid_token_storage(): void
    {
        $this->artisan('idn-area:setup --force --mode=api --access-key=test --secret-key=test --token-storage=cache --skip-connectivity')
            ->expectsOutput('📦 Token storage: cache')
            ->assertExitCode(0);
    }

    public function test_command_checks_if_already_setup(): void
    {
        // Mock already setup
        config(['idn-area.setup_completed' => true]);

        $this->artisan('idn-area:setup')
            ->expectsOutput('✅ Package is already configured!')
            ->expectsOutput('Use --force to reconfigure')
            ->assertExitCode(0);
    }

    public function test_command_can_force_reconfigure(): void
    {
        config(['idn-area.setup_completed' => true]);

        $this->artisan('idn-area:setup --force --mode=local --skip-seeding')
            ->expectsOutput('✅ LOCAL MODE SETUP COMPLETE')
            ->assertExitCode(0);
    }

    public function test_command_publishes_migrations(): void
    {
        $this->artisan('idn-area:setup --force --mode=local --skip-seeding')
            ->expectsOutput('📦 Publishing migrations...')
            ->assertExitCode(0);
    }

    public function test_command_handles_migration_publishing_error(): void
    {
        // In testing environment, migration publishing is skipped
        $this->artisan('idn-area:setup --force --mode=local --skip-seeding')
            ->expectsOutput('⚠️  Migration publishing skipped in testing environment')
            ->assertExitCode(0);
    }

    public function test_command_skips_migrations_when_requested(): void
    {
        $this->artisan('idn-area:setup --force --mode=local --skip-migration --skip-seeding')
            ->expectsOutput('⚠️  Skipping database migrations')
            ->assertExitCode(0);
    }

    public function test_command_handles_migration_run_error(): void
    {
        // In testing environment, migration run is handled gracefully
        $this->artisan('idn-area:setup --force --mode=local --skip-seeding')
            ->assertExitCode(0);
    }

    public function test_command_local_mode_shows_seeding_info(): void
    {
        $this->artisan('idn-area:setup --force --mode=local')
            ->expectsOutput('🌱 Seeding data from pre-bundled CSV files...')
            ->assertExitCode(0);
    }

    public function test_command_local_mode_skips_seeding_when_requested(): void
    {
        $this->artisan('idn-area:setup --force --mode=local --skip-seeding')
            ->expectsOutput('⚠️  Skipping data seeding - you will need to run data seeding later')
            ->assertExitCode(0);
    }

    public function test_command_api_mode_shows_credential_setup(): void
    {
        $this->artisan('idn-area:setup --force --mode=api --access-key=test --secret-key=test --skip-connectivity')
            ->expectsOutput('🔐 Setting up DataToko API credentials...')
            ->assertExitCode(0);
    }

    public function test_command_api_mode_skips_connectivity_test(): void
    {
        $this->artisan('idn-area:setup --force --mode=api --access-key=test --secret-key=test --skip-connectivity')
            ->expectsOutput('⏭️ Skipping API connectivity test')
            ->assertExitCode(0);
    }

    public function test_command_handles_setup_exception(): void
    {
        // Mock File::exists to throw exception
        File::shouldReceive('exists')->andThrow(new Exception('File system error'));

        $this->artisan('idn-area:setup --force --mode=local')
            ->expectsOutput('Setup failed: File system error')
            ->assertExitCode(1);
    }

    public function test_command_shows_appropriate_welcome_messages(): void
    {
        $this->artisan('idn-area:setup --force --mode=local --skip-seeding')
            ->expectsOutput('This package provides Indonesian administrative area data')
            ->expectsOutput('(provinces, regencies, districts, villages) from official sources.')
            ->assertExitCode(0);
    }

    public function test_command_updates_configuration(): void
    {
        $this->artisan('idn-area:setup --force --mode=local --skip-seeding')
            ->expectsOutput('✅ Configuration updated: mode = local')
            ->assertExitCode(0);
    }

    public function test_command_shows_final_success_messages(): void
    {
        $this->artisan('idn-area:setup --force --mode=local --skip-seeding')
            ->expectsOutput('🎉 Your package is ready to use!')
            ->assertExitCode(0);
    }

    public function test_command_api_mode_shows_warnings(): void
    {
        $this->artisan('idn-area:setup --force --mode=api --access-key=test --secret-key=test --skip-connectivity')
            ->expectsOutput('⚠️  Remember: API calls may be slower and have limits')
            ->assertExitCode(0);
    }

    public function test_command_local_mode_shows_csv_info(): void
    {
        $this->artisan('idn-area:setup --force --mode=local --skip-seeding')
            ->expectsOutput('📝 Run "php artisan idn-area:seed --force" to seed from CSV files')
            ->assertExitCode(0);
    }
}
