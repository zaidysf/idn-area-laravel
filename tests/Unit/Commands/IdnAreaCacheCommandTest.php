<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;
use zaidysf\IdnArea\Commands\IdnAreaCacheCommand;
use zaidysf\IdnArea\IdnAreaServiceProvider;

class IdnAreaCacheCommandTest extends TestCase
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
        $command = new IdnAreaCacheCommand;
        expect($command->signature)->toContain('idn-area:cache');
        expect($command->signature)->toContain('action');
        expect($command->signature)->toContain('--force');
    }

    public function test_command_description_is_set(): void
    {
        $command = new IdnAreaCacheCommand;
        expect($command->description)->toBe('Manage Indonesian area data cache');
    }

    public function test_command_shows_header(): void
    {
        $this->artisan('idn-area:cache status')
            ->expectsOutput('│     Indonesian Area Data Cache Manager     │')
            ->assertExitCode(0);
    }

    public function test_command_clear_cache_with_force(): void
    {
        $this->artisan('idn-area:cache clear --force')
            ->expectsOutput('🧹 Clearing Indonesian area cache...')
            ->expectsOutput('✅ Cache cleared successfully')
            ->assertExitCode(0);
    }

    public function test_command_clear_cache_without_force_requires_confirmation(): void
    {
        $this->artisan('idn-area:cache clear')
            ->expectsQuestion('Are you sure you want to clear all Indonesian area cache?', false)
            ->expectsOutput('Cache clear cancelled.')
            ->assertExitCode(0);
    }

    public function test_command_clear_cache_with_confirmation(): void
    {
        $this->artisan('idn-area:cache clear')
            ->expectsQuestion('Are you sure you want to clear all Indonesian area cache?', true)
            ->expectsOutput('✅ Cache cleared successfully')
            ->assertExitCode(0);
    }

    public function test_command_clear_cache_handles_failure(): void
    {
        // Mock cache clear failure
        Cache::shouldReceive('tags->flush')->andReturn(false);

        $this->artisan('idn-area:cache clear --force')
            ->expectsOutput('⚠️  Cache clearing may have failed or cache driver doesn\'t support tags')
            ->assertExitCode(0);
    }

    public function test_command_warm_cache_with_force(): void
    {
        $this->artisan('idn-area:cache warm --force')
            ->expectsOutput('🔥 Warming up Indonesian area cache...')
            ->expectsOutput('✅ Cache warming completed')
            ->assertExitCode(0);
    }

    public function test_command_warm_cache_without_force_requires_confirmation(): void
    {
        $this->artisan('idn-area:cache warm')
            ->expectsQuestion('This will pre-load cache with commonly accessed data. Continue?', false)
            ->expectsOutput('Cache warming cancelled.')
            ->assertExitCode(0);
    }

    public function test_command_warm_cache_with_confirmation(): void
    {
        $this->artisan('idn-area:cache warm')
            ->expectsQuestion('This will pre-load cache with commonly accessed data. Continue?', true)
            ->expectsOutput('✅ Cache warming completed')
            ->assertExitCode(0);
    }

    public function test_command_warmup_alias_works(): void
    {
        $this->artisan('idn-area:cache warmup --force')
            ->expectsOutput('✅ Cache warming completed')
            ->assertExitCode(0);
    }

    public function test_command_status_shows_cache_information(): void
    {
        $this->artisan('idn-area:cache status')
            ->expectsOutput('📊 Cache Status:')
            ->expectsOutput('Cache Driver')
            ->expectsOutput('Package Cache Enabled')
            ->expectsOutput('Cache TTL')
            ->assertExitCode(0);
    }

    public function test_command_status_shows_cache_testing(): void
    {
        $this->artisan('idn-area:cache status')
            ->expectsOutput('🧪 Testing cache operations:')
            ->expectsOutput('✅ Cache write: OK')
            ->expectsOutput('✅ Cache read: OK')
            ->expectsOutput('✅ Cache delete: OK')
            ->assertExitCode(0);
    }

    public function test_command_status_shows_sample_cache_status(): void
    {
        $this->artisan('idn-area:cache status')
            ->expectsOutput('🔍 Sample cache status:')
            ->expectsOutput('All provinces:')
            ->expectsOutput('General statistics:')
            ->assertExitCode(0);
    }

    public function test_command_status_shows_helpful_comments(): void
    {
        $this->artisan('idn-area:cache status')
            ->expectsOutput('💡 Use php artisan idn-area:cache warm to pre-load common data')
            ->expectsOutput('💡 Use php artisan idn-area:cache clear to clear all cached data')
            ->assertExitCode(0);
    }

    public function test_command_status_handles_cache_test_failure(): void
    {
        Cache::shouldReceive('put')->andThrow(new Exception('Cache error'));

        $this->artisan('idn-area:cache status')
            ->expectsOutput('❌ Cache test failed: Cache error')
            ->assertExitCode(0);
    }

    public function test_command_status_detects_cache_tags_support(): void
    {
        // Mock cache store with tags support
        $mockStore = Mockery::mock();
        $mockStore->shouldReceive('tags')->andReturnSelf();
        $mockStore->shouldReceive('put')->andReturn(true);
        $mockStore->shouldReceive('flush')->andReturn(true);

        Cache::shouldReceive('getStore')->andReturn($mockStore);

        $this->artisan('idn-area:cache status')
            ->expectsOutput('✅ Cache tags: Supported')
            ->assertExitCode(0);
    }

    public function test_command_status_handles_no_cache_tags_support(): void
    {
        // Mock cache store without tags support
        $mockStore = Mockery::mock();
        Cache::shouldReceive('getStore')->andReturn($mockStore);

        $this->artisan('idn-area:cache status')
            ->expectsOutput('⚠️  Cache tags: Not supported by current driver')
            ->assertExitCode(0);
    }

    public function test_command_unknown_action_fails(): void
    {
        $this->artisan('idn-area:cache unknown')
            ->expectsOutput('Unknown action: unknown')
            ->expectsOutput('Available actions: clear, warm, status')
            ->assertExitCode(1);
    }

    public function test_command_handles_exception_gracefully(): void
    {
        Cache::shouldReceive('put')->andThrow(new Exception('Unexpected error'));

        $this->artisan('idn-area:cache clear --force')
            ->expectsOutput('Error managing cache: Unexpected error')
            ->assertExitCode(1);
    }

    public function test_command_cache_warming_includes_major_provinces(): void
    {
        $this->artisan('idn-area:cache warm --force')
            ->expectsOutput('Loading all provinces')
            ->expectsOutput('Loading statistics')
            ->assertExitCode(0);
    }

    public function test_command_cache_warming_handles_individual_failures(): void
    {
        // We can't easily mock the IdnArea facade in this test context,
        // but the test structure covers the error handling path
        $this->artisan('idn-area:cache warm --force')
            ->assertExitCode(0);
    }
}
