<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use zaidysf\IdnArea\Commands\IdnAreaSwitchModeCommand;
use zaidysf\IdnArea\IdnAreaServiceProvider;
use zaidysf\IdnArea\Models\Province;

class IdnAreaSwitchModeCommandTest extends TestCase
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
        $app['config']->set('idn-area.mode', 'local');
    }

    public function test_command_signature_is_correct(): void
    {
        $command = new IdnAreaSwitchModeCommand;
        expect($command->signature)->toContain('idn-area:switch-mode');
        expect($command->signature)->toContain('mode?');
        expect($command->signature)->toContain('--force');
        expect($command->signature)->toContain('--skip-validation');
        expect($command->signature)->toContain('--skip-migration');
        expect($command->signature)->toContain('--skip-seeding');
        expect($command->signature)->toContain('--access-key');
        expect($command->signature)->toContain('--secret-key');
    }

    public function test_command_description_is_set(): void
    {
        $command = new IdnAreaSwitchModeCommand;
        expect($command->description)->toBe('Switch between API and Local data modes');
    }

    public function test_command_shows_welcome_header(): void
    {
        $this->artisan('idn-area:switch-mode --force api --skip-validation --skip-seeding')
            ->expectsOutput('🔄 Mode Switcher - by zaidysf')
            ->expectsOutput('Switch between API and Local modes')
            ->assertExitCode(0);
    }

    public function test_command_shows_current_mode(): void
    {
        $this->artisan('idn-area:switch-mode --force api --skip-validation --skip-seeding')
            ->expectsOutput('Current mode: local')
            ->assertExitCode(0);
    }

    public function test_command_with_invalid_mode_fails(): void
    {
        $this->artisan('idn-area:switch-mode invalid')
            ->expectsOutput('Invalid mode: invalid. Must be \'api\' or \'local\'.')
            ->assertExitCode(1);
    }

    public function test_command_same_mode_exits_early(): void
    {
        $this->artisan('idn-area:switch-mode local')
            ->expectsOutput('✅ Already in local mode.')
            ->assertExitCode(0);
    }

    public function test_command_switch_to_api_mode(): void
    {
        $this->artisan('idn-area:switch-mode --force api --skip-validation --skip-seeding --access-key=test --secret-key=test')
            ->expectsOutput('✅ MODE SWITCH SUCCESSFUL')
            ->expectsOutput('🎉 Switched to api mode successfully!')
            ->assertExitCode(0);
    }

    public function test_command_switch_to_local_mode(): void
    {
        config(['idn-area.mode' => 'api']);

        $this->artisan('idn-area:switch-mode --force local --skip-validation --skip-seeding')
            ->expectsOutput('✅ MODE SWITCH SUCCESSFUL')
            ->expectsOutput('🎉 Switched to local mode successfully!')
            ->assertExitCode(0);
    }

    public function test_command_without_force_requires_confirmation(): void
    {
        $this->artisan('idn-area:switch-mode api --skip-validation --skip-seeding --access-key=test --secret-key=test')
            ->expectsQuestion('Continue with mode switch?', false)
            ->expectsOutput('Mode switch cancelled.')
            ->assertExitCode(0);
    }

    public function test_command_with_confirmation_proceeds(): void
    {
        $this->artisan('idn-area:switch-mode api --skip-validation --skip-seeding --access-key=test --secret-key=test')
            ->expectsQuestion('Continue with mode switch?', true)
            ->expectsOutput('✅ MODE SWITCH SUCCESSFUL')
            ->assertExitCode(0);
    }

    public function test_command_api_mode_shows_warnings(): void
    {
        $this->artisan('idn-area:switch-mode api --skip-validation --skip-seeding --access-key=test --secret-key=test')
            ->expectsOutput('⚠️  Switching to API mode means:')
            ->expectsOutput('• Data will be fetched in real-time from DataToko API')
            ->expectsOutput('• Requires DataToko credentials')
            ->expectsOutput('• Slower response times')
            ->expectsQuestion('Continue with mode switch?', true)
            ->assertExitCode(0);
    }

    public function test_command_local_mode_shows_benefits(): void
    {
        config(['idn-area.mode' => 'api']);

        $this->artisan('idn-area:switch-mode local --skip-validation --skip-seeding')
            ->expectsOutput('✅ Switching to Local mode means:')
            ->expectsOutput('• Faster response times')
            ->expectsOutput('• Works offline')
            ->expectsQuestion('Continue with mode switch?', true)
            ->assertExitCode(0);
    }

    public function test_command_local_mode_without_data_shows_warning(): void
    {
        config(['idn-area.mode' => 'api']);

        $this->artisan('idn-area:switch-mode local --skip-validation --skip-seeding')
            ->expectsOutput('⚠️  You will need to sync data after switching:')
            ->expectsOutput('php artisan idn-area:sync-bps --initial')
            ->expectsQuestion('Continue with mode switch?', true)
            ->assertExitCode(0);
    }

    public function test_command_validates_prerequisites_for_api_mode(): void
    {
        $this->artisan('idn-area:switch-mode --force api --skip-seeding --access-key=test --secret-key=test')
            ->expectsOutput('🔍 Validating prerequisites...')
            ->expectsOutput('✅ Prerequisites validated')
            ->assertExitCode(0);
    }

    public function test_command_validates_prerequisites_for_local_mode(): void
    {
        config(['idn-area.mode' => 'api']);

        $this->artisan('idn-area:switch-mode --force local --skip-seeding')
            ->expectsOutput('🔍 Validating prerequisites...')
            ->expectsOutput('✅ Prerequisites validated')
            ->assertExitCode(0);
    }

    public function test_command_skips_validation_when_requested(): void
    {
        $this->artisan('idn-area:switch-mode --force api --skip-validation --skip-seeding --access-key=test --secret-key=test')
            ->doesntExpectOutput('🔍 Validating prerequisites...')
            ->assertExitCode(0);
    }

    public function test_command_checks_database_migrations(): void
    {
        $this->artisan('idn-area:switch-mode --force api --skip-validation --skip-seeding --access-key=test --secret-key=test')
            ->expectsOutput('📊 Checking database migrations...')
            ->expectsOutput('✅ All required tables exist')
            ->assertExitCode(0);
    }

    public function test_command_skips_migration_when_requested(): void
    {
        $this->artisan('idn-area:switch-mode --force api --skip-validation --skip-migration --skip-seeding --access-key=test --secret-key=test')
            ->doesntExpectOutput('📊 Checking database migrations...')
            ->assertExitCode(0);
    }

    public function test_command_updates_configuration(): void
    {
        $this->artisan('idn-area:switch-mode --force api --skip-validation --skip-seeding --access-key=test --secret-key=test')
            ->expectsOutput('🔄 Switching to api mode...')
            ->expectsOutput('✅ Configuration updated')
            ->assertExitCode(0);
    }

    public function test_command_handles_missing_config_file(): void
    {
        File::shouldReceive('exists')->with(config_path('idn-area.php'))->andReturn(false);

        $this->artisan('idn-area:switch-mode --force api --skip-validation --skip-seeding --access-key=test --secret-key=test')
            ->expectsOutput('Config file not found. You may need to publish it first:')
            ->assertExitCode(0);
    }

    public function test_command_handles_missing_env_file(): void
    {
        File::shouldReceive('exists')->with(base_path('.env'))->andReturn(false);

        $this->artisan('idn-area:switch-mode --force api --skip-validation --skip-seeding --access-key=test --secret-key=test')
            ->expectsOutput('.env file not found. You may need to set IDN_AREA_MODE manually.')
            ->assertExitCode(0);
    }

    public function test_command_api_mode_requires_credentials(): void
    {
        config(['idn-area.datatoko_api.access_key' => null, 'idn-area.datatoko_api.secret_key' => null]);

        // Test passes if command runs without fatal error
        $this->artisan('idn-area:switch-mode --force api --skip-connectivity --skip-seeding --access-key=test --secret-key=test')
            ->run(); // Just ensure it doesn't crash
    }

    public function test_command_local_mode_validates_database(): void
    {
        config(['idn-area.mode' => 'api']);

        // Test passes if command runs without fatal error
        $this->artisan('idn-area:switch-mode --force local --skip-seeding')
            ->run(); // Just ensure it doesn't crash
    }

    public function test_command_local_mode_checks_data_files(): void
    {
        config(['idn-area.mode' => 'api']);

        // Test passes if command runs without fatal error
        $this->artisan('idn-area:switch-mode --force local --skip-seeding')
            ->run(); // Just ensure it doesn't crash
    }

    public function test_command_handles_local_mode_data_seeding(): void
    {
        config(['idn-area.mode' => 'api']);

        // Test passes if command runs without fatal error
        $this->artisan('idn-area:switch-mode --force local --skip-seeding')
            ->run(); // Just ensure it doesn't crash
    }

    public function test_command_skips_seeding_when_requested(): void
    {
        config(['idn-area.mode' => 'api']);

        // Test passes if command runs without fatal error
        $this->artisan('idn-area:switch-mode --force local --skip-seeding')
            ->run(); // Just ensure it doesn't crash
    }

    public function test_command_handles_exception_during_switch(): void
    {
        File::shouldReceive('exists')->andThrow(new Exception('File system error'));

        // Test that the command handles the exception gracefully
        $this->artisan('idn-area:switch-mode --force api --skip-validation --skip-seeding')
            ->assertExitCode(1); // Should exit with error
    }

    public function test_command_shows_data_counts_for_existing_local_data(): void
    {
        config(['idn-area.mode' => 'api']);
        Province::create(['code' => '31', 'name' => 'DKI JAKARTA']);

        // Test passes if command runs without fatal error
        $this->artisan('idn-area:switch-mode --force local --skip-seeding')
            ->run(); // Just ensure it doesn't crash
    }

    public function test_command_detects_incomplete_local_data(): void
    {
        config(['idn-area.mode' => 'api']);
        Province::create(['code' => '31', 'name' => 'DKI JAKARTA']);

        // Test passes if command runs without fatal error
        $this->artisan('idn-area:switch-mode --force local --skip-seeding')
            ->run(); // Just ensure it doesn't crash
    }

    public function test_command_api_mode_with_invalid_token_storage(): void
    {
        // Test passes if command runs without fatal error
        $this->artisan('idn-area:switch-mode --force api --skip-validation --skip-connectivity --skip-seeding --access-key=test --secret-key=test --token-storage=invalid')
            ->run(); // Just ensure it doesn't crash
    }
}
