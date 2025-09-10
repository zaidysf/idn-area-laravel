<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;
use zaidysf\IdnArea\Commands\IdnAreaCommand;
use zaidysf\IdnArea\IdnAreaServiceProvider;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;

class IdnAreaCommandTest extends TestCase
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
        $command = new IdnAreaCommand;
        expect($command->signature)->toContain('idn-area:seed');
        expect($command->signature)->toContain('--force');
        expect($command->signature)->toContain('--verify');
        expect($command->signature)->toContain('--backup');
        expect($command->signature)->toContain('--chunk-size');
    }

    public function test_command_description_is_set(): void
    {
        $command = new IdnAreaCommand;
        expect($command->description)->toContain('Enhanced seeder');
        expect($command->description)->toContain('Indonesian area data');
    }

    public function test_command_runs_successfully_with_force_flag(): void
    {
        $this->artisan('idn-area:seed --force')
            ->expectsOutput('🚀 Starting Indonesian area data seeding...')
            ->assertExitCode(0);
    }

    public function test_command_shows_header(): void
    {
        $this->artisan('idn-area:seed --force')
            ->expectsOutput('│      Indonesian Area Data Seeder v3.0      │')
            ->assertExitCode(0);
    }

    public function test_command_validates_chunk_size(): void
    {
        $this->artisan('idn-area:seed --force --chunk-size=0')
            ->expectsOutput('Chunk size must be between 1 and 10,000')
            ->assertExitCode(1);

        $this->artisan('idn-area:seed --force --chunk-size=20000')
            ->expectsOutput('Chunk size must be between 1 and 10,000')
            ->assertExitCode(1);
    }

    public function test_command_shows_current_database_state(): void
    {
        Province::create(['code' => '31', 'name' => 'DKI JAKARTA']);
        Regency::create(['code' => '3171', 'province_code' => '31', 'name' => 'JAKARTA SELATAN']);

        $this->artisan('idn-area:seed --force')
            ->expectsOutput('📊 Current database state:')
            ->assertExitCode(0);
    }

    public function test_command_handles_existing_data_without_force(): void
    {
        Province::create(['code' => '31', 'name' => 'DKI JAKARTA']);

        $this->artisan('idn-area:seed')
            ->expectsOutput('⚠️  Existing data found:')
            ->expectsQuestion('Do you want to continue? This may overwrite existing data.', false)
            ->expectsOutput('Seeding cancelled.')
            ->assertExitCode(0);
    }

    public function test_command_continues_with_existing_data_when_confirmed(): void
    {
        Province::create(['code' => '31', 'name' => 'DKI JAKARTA']);

        $this->artisan('idn-area:seed')
            ->expectsOutput('⚠️  Existing data found:')
            ->expectsQuestion('Do you want to continue? This may overwrite existing data.', true)
            ->assertExitCode(0);
    }

    public function test_command_with_backup_option(): void
    {
        Province::create(['code' => '31', 'name' => 'DKI JAKARTA']);

        $this->artisan('idn-area:seed --force --backup')
            ->expectsOutput('💾 Backing up existing data...')
            ->expectsOutput('✅ Backup completed')
            ->assertExitCode(0);
    }

    public function test_command_with_verify_option(): void
    {
        $this->artisan('idn-area:seed --force --verify')
            ->expectsOutput('🔍 Verifying data integrity...')
            ->expectsOutput('✅ Data integrity verification passed')
            ->assertExitCode(0);
    }

    public function test_command_detects_orphaned_data(): void
    {
        Province::create(['code' => '31', 'name' => 'DKI JAKARTA']);
        Regency::create(['code' => '3171', 'province_code' => '99', 'name' => 'ORPHANED']); // Invalid province

        $this->artisan('idn-area:seed --force --verify')
            ->expectsOutput('⚠️  Data integrity issues found:')
            ->assertExitCode(0);
    }

    public function test_command_with_skip_cache_clear_option(): void
    {
        $this->artisan('idn-area:seed --force --skip-cache-clear')
            ->assertExitCode(0);
    }

    public function test_command_clears_cache_by_default(): void
    {
        Cache::shouldReceive('flush')->once();

        $this->artisan('idn-area:seed --force')
            ->expectsOutput('🧹 Clearing package cache...')
            ->assertExitCode(0);
    }

    public function test_command_handles_cache_clear_failure(): void
    {
        Cache::shouldReceive('getStore->tags')->andThrow(new Exception('Cache error'));

        $this->artisan('idn-area:seed --force')
            ->expectsOutput('⚠️  Failed to clear cache:')
            ->assertExitCode(0);
    }

    public function test_command_shows_success_statistics(): void
    {
        $this->artisan('idn-area:seed --force')
            ->expectsOutput('🎉 Seeding completed successfully!')
            ->expectsOutput('📈 Final statistics:')
            ->assertExitCode(0);
    }

    public function test_command_shows_helpful_comments(): void
    {
        $this->artisan('idn-area:seed --force')
            ->expectsOutput('💡 Use php artisan idn-area:stats to view detailed statistics')
            ->expectsOutput('💡 Use php artisan idn-area:cache to manage cache')
            ->assertExitCode(0);
    }

    public function test_command_with_only_option(): void
    {
        $this->artisan('idn-area:seed --force --only=provinces,regencies')
            ->assertExitCode(0);
    }

    public function test_command_with_exclude_option(): void
    {
        $this->artisan('idn-area:seed --force --exclude=villages')
            ->assertExitCode(0);
    }

    public function test_command_handles_exception_gracefully(): void
    {
        // Mock a database error
        DB::shouldReceive('transaction')->andThrow(new Exception('Database error'));

        $this->artisan('idn-area:seed --force')
            ->expectsOutput('❌ Seeding failed!')
            ->expectsOutput('Error: Database error')
            ->assertExitCode(1);
    }

    public function test_command_shows_verbose_error_output(): void
    {
        DB::shouldReceive('transaction')->andThrow(new Exception('Database error'));

        $this->artisan('idn-area:seed --force -v')
            ->expectsOutput('Stack trace:')
            ->assertExitCode(1);
    }

    public function test_command_shows_helpful_error_comments(): void
    {
        DB::shouldReceive('transaction')->andThrow(new Exception('Database error'));

        $this->artisan('idn-area:seed --force')
            ->expectsOutput('💡 Use --force flag to overwrite existing data')
            ->expectsOutput('💡 Use -v flag for verbose error output')
            ->assertExitCode(1);
    }
}
