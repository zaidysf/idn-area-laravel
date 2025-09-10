<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use zaidysf\IdnArea\Facades\IdnArea;

class IdnAreaStatsCommand extends Command
{
    public $signature = 'idn-area:stats 
        {--detailed : Show detailed statistics}
        {--province= : Show statistics for specific province}
        {--format=table : Output format (table, json, csv)}';

    public $description = 'Display comprehensive Indonesian area data statistics';

    public function handle(): int
    {
        $detailed = $this->option('detailed');
        $province = $this->option('province');
        $format = $this->option('format');

        $this->displayHeader();

        try {
            if ($province) {
                $this->showProvinceStats($province, $detailed);
            } else {
                $this->showGeneralStats($detailed);
            }

            if ($detailed) {
                $this->showDetailedAnalysis();
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error generating statistics: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function displayHeader(): void
    {
        $this->info('┌─────────────────────────────────────────────┐');
        $this->info('│     Indonesian Area Data Statistics        │');
        $this->info('└─────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function showGeneralStats(bool $detailed): void
    {
        $stats = IdnArea::statistics();

        $this->info('📊 General Statistics:');
        $this->newLine();

        $data = [
            ['Type', 'Count'],
            ['Provinces', number_format($stats['provinces'])],
            ['Regencies/Cities', number_format($stats['regencies'])],
            ['Districts', number_format($stats['districts'])],
            ['Villages', number_format($stats['villages'])],
        ];

        if (isset($stats['average_districts_per_regency'])) {
            $data[] = ['Avg Districts/Regency', $stats['average_districts_per_regency']];
        }

        if (isset($stats['average_villages_per_district'])) {
            $data[] = ['Avg Villages/District', $stats['average_villages_per_district']];
        }

        $this->table($data[0], array_slice($data, 1));
        $this->newLine();

        if (isset($stats['largest_province_by_regencies'])) {
            $largest = $stats['largest_province_by_regencies'];
            $this->info("🏆 Largest Province: {$largest['name']} ({$largest['regency_count']} regencies)");
        }

        if (isset($stats['smallest_province_by_regencies'])) {
            $smallest = $stats['smallest_province_by_regencies'];
            $this->info("🎯 Smallest Province: {$smallest['name']} ({$smallest['regency_count']} regencies)");
        }

        $this->newLine();
    }

    private function showProvinceStats(string $provinceCode, bool $detailed): void
    {
        $province = IdnArea::province($provinceCode);

        if (! $province) {
            $this->error("Province with code '{$provinceCode}' not found.");

            return;
        }

        $this->info("📍 Statistics for {$province->name} ({$province->code}):");
        $this->newLine();

        $regencyCount = $province->regencies()->count();
        $districtCount = $province->districts()->count();
        $villageCount = $province->villages()->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Regencies/Cities', number_format($regencyCount)],
                ['Districts', number_format($districtCount)],
                ['Villages', number_format($villageCount)],
            ]
        );

        if ($detailed && $regencyCount > 0) {
            $this->newLine();
            $this->info('🏢 Top 10 Regencies by District Count:');

            $topRegencies = $province->regencies()
                ->withCount('districts')
                ->orderBy('districts_count', 'desc')
                ->limit(10)
                ->get();

            $regencyData = [['Regency', 'Districts', 'Code']];
            foreach ($topRegencies as $regency) {
                $regencyData[] = [
                    $regency->name,
                    $regency->districts_count,
                    $regency->code,
                ];
            }

            $this->table($regencyData[0], array_slice($regencyData, 1));
        }

        $this->newLine();
    }

    private function showDetailedAnalysis(): void
    {
        $this->info('🔍 Detailed Analysis:');
        $this->newLine();

        // Distribution analysis
        $this->showDistributionAnalysis();

        // Data quality analysis
        $this->showDataQualityAnalysis();

    }

    private function showDistributionAnalysis(): void
    {
        $this->comment('📊 Distribution Analysis:');

        // Regencies per province distribution
        $regencyDistribution = DB::table('idn_provinces')
            ->leftJoin('idn_regencies', 'idn_provinces.code', '=', 'idn_regencies.province_code')
            ->select('idn_provinces.name', DB::raw('COUNT(idn_regencies.code) as regency_count'))
            ->groupBy('idn_provinces.code', 'idn_provinces.name')
            ->orderBy('regency_count', 'desc')
            ->limit(5)
            ->get();

        $this->line('   Top 5 Provinces by Regency Count:');
        foreach ($regencyDistribution as $item) {
            $this->line("   • {$item->name}: {$item->regency_count} regencies");
        }

        $this->newLine();
    }

    private function showDataQualityAnalysis(): void
    {
        $this->comment('🔍 Data Quality Analysis:');

        // Check for potential issues
        $issues = [];

        // Empty names
        $emptyProvinces = DB::table('idn_provinces')->where('name', '')->count();
        if ($emptyProvinces > 0) {
            $issues[] = "{$emptyProvinces} provinces with empty names";
        }

        $emptyRegencies = DB::table('idn_regencies')->where('name', '')->count();
        if ($emptyRegencies > 0) {
            $issues[] = "{$emptyRegencies} regencies with empty names";
        }

        if (! empty($issues)) {
            $this->line('   ⚠️  Issues found:');
            foreach ($issues as $issue) {
                $this->line("     • {$issue}");
            }
        } else {
            $this->line('   ✅ No data quality issues detected');
        }

        $this->newLine();
    }
}
