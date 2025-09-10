<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use zaidysf\IdnArea\Facades\IdnArea;

class IdnAreaCacheCommand extends Command
{
    public $signature = 'idn-area:cache 
        {action : Action to perform (clear, warm, status)}
        {--force : Force cache operations without confirmation}';

    public $description = 'Manage Indonesian area data cache';

    public function handle(): int
    {
        /** @var string $action */
        $action = $this->argument('action');
        /** @var bool $force */
        $force = $this->option('force');

        $this->displayHeader();

        try {
            switch ($action) {
                case 'clear':
                    return $this->clearCache($force);

                case 'warm':
                case 'warmup':
                    return $this->warmCache($force);

                case 'status':
                    return $this->showCacheStatus();

                default:
                    $this->error("Unknown action: {$action}");
                    $this->comment('Available actions: clear, warm, status');

                    return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Error managing cache: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function displayHeader(): void
    {
        $this->info('┌─────────────────────────────────────────────┐');
        $this->info('│     Indonesian Area Data Cache Manager     │');
        $this->info('└─────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function clearCache(bool $force): int
    {
        if (! $force && ! $this->confirm('Are you sure you want to clear all Indonesian area cache?')) {
            $this->comment('Cache clear cancelled.');

            return self::SUCCESS;
        }

        $this->info('🧹 Clearing Indonesian area cache...');

        $success = IdnArea::clearCache();

        if ($success) {
            $this->info('✅ Cache cleared successfully');
        } else {
            $this->warn('⚠️  Cache clearing may have failed or cache driver doesn\'t support tags');
        }

        $this->newLine();

        return self::SUCCESS;
    }

    private function warmCache(bool $force): int
    {
        if (! $force && ! $this->confirm('This will pre-load cache with commonly accessed data. Continue?')) {
            $this->comment('Cache warming cancelled.');

            return self::SUCCESS;
        }

        $this->info('🔥 Warming up Indonesian area cache...');
        $this->newLine();

        $operations = [
            'Loading all provinces' => fn () => IdnArea::provinces(true),
            'Loading statistics' => fn () => IdnArea::statistics(true),
        ];

        // Add province hierarchies for major provinces
        $majorProvinces = ['31', '32', '33', '34', '35']; // Jakarta, West Java, Central Java, Yogyakarta, East Java
        foreach ($majorProvinces as $provinceCode) {
            $operations["Loading hierarchy for province {$provinceCode}"] =
                fn () => IdnArea::hierarchy($provinceCode, true);
        }

        $this->withProgressBar($operations, function (callable $operation, string $description): void {
            try {
                $operation();
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("Failed to warm: {$description} - {$e->getMessage()}");
            }
        });

        $this->newLine(2);
        $this->info('✅ Cache warming completed');
        $this->newLine();

        return self::SUCCESS;
    }

    private function showCacheStatus(): int
    {
        $this->info('📊 Cache Status:');
        $this->newLine();

        // Check cache driver
        $driver = config('cache.default');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Cache Driver', $driver],
                ['Package Cache Enabled', config('idn-area.cache.enabled') ? 'Yes' : 'No'],
                ['Cache TTL', config('idn-area.cache.ttl', 3600).' seconds'],
                ['Search Cache TTL', config('idn-area.cache.search_ttl', 1800).' seconds'],
                ['Stats Cache TTL', config('idn-area.cache.stats_ttl', 7200).' seconds'],
            ]
        );

        $this->newLine();

        // Test cache operations
        $this->info('🧪 Testing cache operations:');

        $testKey = 'idn_area_test_'.time();
        $testValue = 'test_value';

        try {
            // Test write
            Cache::put($testKey, $testValue, 60);
            $this->line('   ✅ Cache write: OK');

            // Test read
            $retrieved = Cache::get($testKey);
            if ($retrieved === $testValue) {
                $this->line('   ✅ Cache read: OK');
            } else {
                $this->line('   ❌ Cache read: FAILED');
            }

            // Test delete
            Cache::forget($testKey);
            $this->line('   ✅ Cache delete: OK');

            // Test tags support (if available)
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['test'])->put('test_tagged', 'value', 60);
                Cache::tags(['test'])->flush();
                $this->line('   ✅ Cache tags: Supported');
            } else {
                $this->line('   ⚠️  Cache tags: Not supported by current driver');
            }

        } catch (\Exception $e) {
            $this->line("   ❌ Cache test failed: {$e->getMessage()}");
        }

        $this->newLine();

        // Sample cache keys that might exist
        $this->info('🔍 Sample cache status:');

        $sampleKeys = [
            'idn_area.provinces' => 'All provinces',
            'idn_area.statistics' => 'General statistics',
        ];

        foreach ($sampleKeys as $key => $description) {
            $exists = Cache::has($key) ? '✅ Cached' : '❌ Not cached';
            $this->line("   {$description}: {$exists}");
        }

        $this->newLine();
        $this->comment('💡 Use php artisan idn-area:cache warm to pre-load common data');
        $this->comment('💡 Use php artisan idn-area:cache clear to clear all cached data');

        return self::SUCCESS;
    }
}
