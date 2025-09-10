<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Island;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;
use zaidysf\IdnArea\Services\IdnAreaSeeder;

class IdnAreaCommand extends Command
{
    public $signature = 'idn-area:seed 
        {--force : Force seeding even if data exists}
        {--verify : Verify data integrity after seeding}
        {--backup : Backup existing data before seeding}
        {--chunk-size=500 : Number of records to process at once}
        {--skip-cache-clear : Skip clearing cache after seeding}
        {--only= : Seed only specific types (provinces,regencies,districts,villages,islands)}
        {--exclude= : Exclude specific types from seeding}';

    public $description = 'Enhanced seeder for Indonesian area data with modern Laravel features';

    public function handle(): int
    {
        $this->displayHeader();

        $options = $this->gatherOptions();

        if (! $this->confirmSeeding($options)) {
            $this->comment('Seeding cancelled.');

            return self::SUCCESS;
        }

        $seeder = new IdnAreaSeeder;

        try {
            $this->info('🚀 Starting Indonesian area data seeding...');
            $this->newLine();

            // Show current database state
            $this->showCurrentState();

            // Backup if requested
            if ($options['backup'] ?? false) {
                $this->backupExistingData();
            }

            // Perform seeding
            $result = $seeder->seed($options['force'] ?? false, $this, $options);

            // Verify data integrity if requested
            if ($options['verify'] ?? false) {
                $this->verifyDataIntegrity();
            }

            // Clear cache if not skipped
            if (! ($options['skip_cache_clear'] ?? false)) {
                $this->clearCache();
            }

            $this->displaySuccess($result);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->displayError($e);

            return self::FAILURE;
        }
    }

    private function displayHeader(): void
    {
        $this->info('┌─────────────────────────────────────────────┐');
        $this->info('│      Indonesian Area Data Seeder v3.0      │');
        $this->info('│     Enhanced for Laravel 10/11/12         │');
        $this->info('└─────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function gatherOptions(): array
    {
        /** @var bool $force */
        $force = $this->option('force');
        /** @var bool $verify */
        $verify = $this->option('verify');
        /** @var bool $backup */
        $backup = $this->option('backup');
        /** @var string $chunkSizeOption */
        $chunkSizeOption = $this->option('chunk-size');
        $chunkSize = (int) $chunkSizeOption;
        /** @var bool $skipCacheClear */
        $skipCacheClear = $this->option('skip-cache-clear');
        /** @var string|null $onlyOption */
        $onlyOption = $this->option('only');
        $only = $onlyOption ? explode(',', $onlyOption) : null;
        /** @var string|null $excludeOption */
        $excludeOption = $this->option('exclude');
        $exclude = $excludeOption ? explode(',', $excludeOption) : null;

        // Validate chunk size
        if ($chunkSize < 1 || $chunkSize > 10000) {
            $this->error('Chunk size must be between 1 and 10,000');
            throw new \InvalidArgumentException('Chunk size must be between 1 and 10,000');
        }

        return [
            'force' => $force,
            'verify' => $verify,
            'backup' => $backup,
            'chunkSize' => $chunkSize,
            'skipCacheClear' => $skipCacheClear,
            'only' => $only,
            'exclude' => $exclude,
        ];
    }

    private function confirmSeeding(array $options): bool
    {
        if ($options['force'] ?? false) {
            return true;
        }

        // Check if data already exists
        $hasData = $this->checkExistingData();

        if ($hasData['has_any']) {
            $this->warn('⚠️  Existing data found:');

            foreach ($hasData as $type => $count) {
                if ($type !== 'has_any' && $count > 0) {
                    $this->line('   • '.ucfirst($type).": {$count} records");
                }
            }

            $this->newLine();

            if (! $this->confirm('Do you want to continue? This may overwrite existing data.')) {
                return false;
            }
        }

        return true;
    }

    private function checkExistingData(): array
    {
        $provinces = Province::count();
        $regencies = Regency::count();
        $districts = District::count();
        $villages = Village::count();
        $islands = Island::count();

        return [
            'provinces' => $provinces,
            'regencies' => $regencies,
            'districts' => $districts,
            'villages' => $villages,
            'islands' => $islands,
            'has_any' => $provinces + $regencies + $districts + $villages + $islands > 0,
        ];
    }

    private function showCurrentState(): void
    {
        $this->info('📊 Current database state:');

        $data = $this->checkExistingData();

        $this->table(
            ['Type', 'Count'],
            [
                ['Provinces', number_format($data['provinces'])],
                ['Regencies', number_format($data['regencies'])],
                ['Districts', number_format($data['districts'])],
                ['Villages', number_format($data['villages'])],
                ['Islands', number_format($data['islands'])],
            ]
        );

        $this->newLine();
    }

    private function backupExistingData(): void
    {
        $this->info('💾 Backing up existing data...');

        $timestamp = now()->format('Y_m_d_His');
        $backupTables = [
            'idn_provinces' => "idn_provinces_backup_{$timestamp}",
            'idn_regencies' => "idn_regencies_backup_{$timestamp}",
            'idn_districts' => "idn_districts_backup_{$timestamp}",
            'idn_villages' => "idn_villages_backup_{$timestamp}",
            'idn_islands' => "idn_islands_backup_{$timestamp}",
        ];

        $this->withProgressBar($backupTables, function (string $backupTable, string $originalTable): void {
            if (DB::getSchemaBuilder()->hasTable($originalTable)) {
                $count = DB::table($originalTable)->count();
                if ($count > 0) {
                    DB::statement("CREATE TABLE {$backupTable} AS SELECT * FROM {$originalTable}");
                }
            }
        });

        $this->newLine(2);
        $this->info('✅ Backup completed');
        $this->newLine();
    }

    private function verifyDataIntegrity(): void
    {
        $this->info('🔍 Verifying data integrity...');
        $this->newLine();

        $issues = [];

        // Check for orphaned records
        $orphanedRegencies = Regency::whereDoesntHave('province')->count();
        if ($orphanedRegencies > 0) {
            $issues[] = "Found {$orphanedRegencies} regencies without provinces";
        }

        $orphanedDistricts = District::whereDoesntHave('regency')->count();
        if ($orphanedDistricts > 0) {
            $issues[] = "Found {$orphanedDistricts} districts without regencies";
        }

        $orphanedVillages = Village::whereDoesntHave('district')->count();
        if ($orphanedVillages > 0) {
            $issues[] = "Found {$orphanedVillages} villages without districts";
        }

        $orphanedIslands = Island::whereNotNull('regency_code')
            ->whereDoesntHave('regency')->count();
        if ($orphanedIslands > 0) {
            $issues[] = "Found {$orphanedIslands} islands with invalid regency codes";
        }

        if (empty($issues)) {
            $this->info('✅ Data integrity verification passed');
        } else {
            $this->warn('⚠️  Data integrity issues found:');
            foreach ($issues as $issue) {
                $this->line("   • {$issue}");
            }
        }

        $this->newLine();
    }

    private function clearCache(): void
    {
        $this->info('🧹 Clearing package cache...');

        try {
            // Clear package-specific cache
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['idn_area'])->flush();
            } else {
                // Fallback for cache drivers that don't support tags
                $patterns = [
                    'idn_area.*',
                ];

                foreach ($patterns as $pattern) {
                    Cache::flush(); // Simple flush for non-tagged cache drivers
                    break; // Only need to flush once
                }
            }

            $this->info('✅ Cache cleared successfully');
        } catch (\Exception $e) {
            $this->warn('⚠️  Failed to clear cache: '.$e->getMessage());
        }

        $this->newLine();
    }

    private function displaySuccess(array $result): void
    {
        $this->newLine();
        $this->info('🎉 Seeding completed successfully!');
        $this->newLine();

        if (! empty($result)) {
            $this->table(
                ['Type', 'Seeded', 'Duration'],
                collect($result)
                    ->except(['total_duration'])
                    ->map(function (array|string $data, string $type): array {
                        if (is_string($data)) {
                            return [ucfirst($type), 'N/A', $data];
                        }

                        return [
                            ucfirst($type),
                            number_format($data['count'] ?? 0),
                            $data['duration'] ?? 'N/A',
                        ];
                    })->toArray()
            );
        }

        $this->newLine();
        $this->info('📈 Final statistics:');
        $this->showCurrentState();

        $this->comment('💡 Use php artisan idn-area:stats to view detailed statistics');
        $this->comment('💡 Use php artisan idn-area:cache to manage cache');
    }

    private function displayError(\Exception $e): void
    {
        $this->newLine();
        $this->error('❌ Seeding failed!');
        $this->newLine();

        $this->error('Error: '.$e->getMessage());

        if ($this->getOutput()->isVerbose()) {
            $this->newLine();
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
        }

        $this->newLine();
        $this->comment('💡 Use --force flag to overwrite existing data');
        $this->comment('💡 Use -v flag for verbose error output');
    }
}
