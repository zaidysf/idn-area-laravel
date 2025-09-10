<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Island;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

class IdnAreaSeeder
{
    protected string $dataPath;

    public function __construct()
    {
        $this->dataPath = __DIR__.'/../../database/seeders/data/';
    }

    public function seed(bool $force = false, ?Command $command = null, array $options = []): array
    {
        $this->checkTables();

        if (! $force && $this->hasData()) {
            if ($command) {
                $command->warn('Data already exists. Use --force to reseed.');
            }

            return [];
        }

        $startTime = microtime(true);
        $results = [];

        try {
            if ($force) {
                $this->clearData($command);
            }

            $only = $options['only'] ?? [];
            $exclude = $options['exclude'] ?? [];

            if ($this->shouldSeed('provinces', $only, $exclude)) {
                $result = $this->seedProvinces($command);
                $results['provinces'] = $result;
            }

            if ($this->shouldSeed('regencies', $only, $exclude)) {
                $result = $this->seedRegencies($command);
                $results['regencies'] = $result;
            }

            if ($this->shouldSeed('districts', $only, $exclude)) {
                $result = $this->seedDistricts($command);
                $results['districts'] = $result;
            }

            if ($this->shouldSeed('villages', $only, $exclude)) {
                $result = $this->seedVillages($command);
                $results['villages'] = $result;
            }

            if ($this->shouldSeed('islands', $only, $exclude)) {
                $result = $this->seedIslands($command);
                $results['islands'] = $result;
            }

            $totalTime = round(microtime(true) - $startTime, 2);
            $results['total_duration'] = $totalTime.'s';

        } catch (\Exception $e) {
            if ($command) {
                $command->error('Seeding failed: '.$e->getMessage());
            }
            throw $e;
        }

        return $results;
    }

    protected function checkTables(): void
    {
        $tables = ['idn_provinces', 'idn_regencies', 'idn_districts', 'idn_villages', 'idn_islands'];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                throw new \Exception("Table {$table} does not exist. Please run migrations first.");
            }
        }
    }

    protected function hasData(): bool
    {
        return Province::count() > 0 ||
               Regency::count() > 0 ||
               District::count() > 0 ||
               Village::count() > 0 ||
               Island::count() > 0;
    }

    protected function shouldSeed(string $type, array $only, array $exclude): bool
    {
        if (! empty($only)) {
            return in_array($type, $only);
        }

        if (! empty($exclude)) {
            return ! in_array($type, $exclude);
        }

        return true;
    }

    protected function clearData(?Command $command = null): void
    {
        if ($command) {
            $command->info('Clearing existing data...');
        }

        // Use delete instead of truncate to avoid transaction issues
        Village::query()->delete();
        District::query()->delete();
        Regency::query()->delete();
        Province::query()->delete();
        Island::query()->delete();

        if ($command) {
            $command->info('Existing data cleared successfully');
        }
    }

    protected function seedProvinces(?Command $command = null): array
    {
        $startTime = microtime(true);

        if ($command) {
            $command->info('Seeding provinces...');
        }

        $csvFile = $this->dataPath.'provinces.csv';
        $data = $this->readCsv($csvFile);

        $provinces = [];
        foreach ($data as $row) {
            $provinces[] = [
                'code' => $row['code'],
                'name' => $row['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Province::insert($provinces);

        $duration = round(microtime(true) - $startTime, 2).'s';

        if ($command) {
            $command->info('Seeded '.count($provinces).' provinces');
        }

        return [
            'count' => count($provinces),
            'duration' => $duration,
        ];
    }

    protected function seedRegencies(?Command $command = null): array
    {
        $startTime = microtime(true);

        if ($command) {
            $command->info('Seeding regencies...');
        }

        $csvFile = $this->dataPath.'regencies.csv';
        $data = $this->readCsv($csvFile);

        $regencies = [];
        foreach ($data as $row) {
            $regencies[] = [
                'code' => $row['code'],
                'province_code' => $row['province_code'],
                'name' => $row['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($regencies, 100) as $chunk) {
            Regency::insert($chunk);
        }

        $duration = round(microtime(true) - $startTime, 2).'s';

        if ($command) {
            $command->info('Seeded '.count($regencies).' regencies');
        }

        return [
            'count' => count($regencies),
            'duration' => $duration,
        ];
    }

    protected function seedDistricts(?Command $command = null): array
    {
        $startTime = microtime(true);

        if ($command) {
            $command->info('Seeding districts...');
        }

        $csvFile = $this->dataPath.'districts.csv';
        $data = $this->readCsv($csvFile);

        $districts = [];
        foreach ($data as $row) {
            $districts[] = [
                'code' => $row['code'],
                'regency_code' => $row['regency_code'],
                'name' => $row['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($districts, 100) as $chunk) {
            District::insert($chunk);
        }

        $duration = round(microtime(true) - $startTime, 2).'s';

        if ($command) {
            $command->info('Seeded '.count($districts).' districts');
        }

        return [
            'count' => count($districts),
            'duration' => $duration,
        ];
    }

    protected function seedVillages(?Command $command = null): array
    {
        $startTime = microtime(true);

        if ($command) {
            $command->info('Seeding villages...');
        }

        $csvFile = $this->dataPath.'villages.csv';
        $data = $this->readCsv($csvFile);

        $villages = [];
        $count = 0;

        foreach ($data as $row) {
            $villages[] = [
                'code' => $row['code'],
                'district_code' => $row['district_code'],
                'name' => $row['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $count++;

            // Insert in smaller chunks for villages due to large dataset
            if (count($villages) >= 50) {
                Village::insert($villages);
                $villages = [];

                if ($command && $count % 1000 == 0) {
                    $command->info("Seeded {$count} villages...");
                }
            }
        }

        // Insert remaining villages
        if (! empty($villages)) {
            Village::insert($villages);
        }

        $duration = round(microtime(true) - $startTime, 2).'s';

        if ($command) {
            $command->info('Seeded '.$count.' villages');
        }

        return [
            'count' => $count,
            'duration' => $duration,
        ];
    }

    protected function seedIslands(?Command $command = null): array
    {
        $startTime = microtime(true);

        if ($command) {
            $command->info('Seeding islands...');
        }

        $csvFile = $this->dataPath.'islands.csv';
        $data = $this->readCsv($csvFile);

        $islands = [];
        foreach ($data as $row) {
            $islands[] = [
                'code' => $row['code'] ?? null,
                'coordinate' => $row['coordinate'] ?? null,
                'name' => $row['name'],
                'is_outermost_small' => $this->parseBool($row['is_outermost_small'] ?? false),
                'is_populated' => $this->parseBool($row['is_populated'] ?? false),
                'regency_code' => $row['regency_code'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($islands, 100) as $chunk) {
            Island::insert($chunk);
        }

        $duration = round(microtime(true) - $startTime, 2).'s';

        if ($command) {
            $command->info('Seeded '.count($islands).' islands');
        }

        return [
            'count' => count($islands),
            'duration' => $duration,
        ];
    }

    protected function readCsv(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new \Exception("CSV file not found: {$filePath}");
        }

        $data = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \Exception("Could not open CSV file: {$filePath}");
        }

        // Read header row
        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);
            throw new \Exception("Could not read headers from CSV file: {$filePath}");
        }

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $data[] = array_combine($headers, $row);
            }
        }

        fclose($handle);

        return $data;
    }

    protected function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        }

        return (bool) $value;
    }
}
