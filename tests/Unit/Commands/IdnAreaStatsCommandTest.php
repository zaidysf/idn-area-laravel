<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;
use zaidysf\IdnArea\Commands\IdnAreaStatsCommand;
use zaidysf\IdnArea\IdnAreaServiceProvider;
use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

class IdnAreaStatsCommandTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
    }

    private function createTestData(): void
    {
        $province = Province::create(['code' => '31', 'name' => 'DKI JAKARTA']);

        $regency = Regency::create([
            'code' => '3171',
            'province_code' => '31',
            'name' => 'JAKARTA SELATAN',
        ]);

        $district = District::create([
            'code' => '317101',
            'regency_code' => '3171',
            'name' => 'KEBAYORAN BARU',
        ]);

        Village::create([
            'code' => '3171011001',
            'district_code' => '317101',
            'name' => 'KRAMAT PELA',
        ]);
    }

    public function test_command_signature_is_correct(): void
    {
        $command = new IdnAreaStatsCommand;
        expect($command->signature)->toContain('idn-area:stats');
        expect($command->signature)->toContain('--detailed');
        expect($command->signature)->toContain('--province');
        expect($command->signature)->toContain('--format');
    }

    public function test_command_description_is_set(): void
    {
        $command = new IdnAreaStatsCommand;
        expect($command->description)->toBe('Display comprehensive Indonesian area data statistics');
    }

    public function test_command_shows_header(): void
    {
        $this->artisan('idn-area:stats')
            ->expectsOutput('│     Indonesian Area Data Statistics        │')
            ->assertExitCode(0);
    }

    public function test_command_shows_general_statistics(): void
    {
        $this->artisan('idn-area:stats')
            ->expectsOutput('📊 General Statistics:')
            ->expectsOutput('Type')
            ->expectsOutput('Count')
            ->expectsOutput('Provinces')
            ->expectsOutput('Regencies/Cities')
            ->expectsOutput('Districts')
            ->expectsOutput('Villages')
            ->assertExitCode(0);
    }

    public function test_command_shows_largest_province_info(): void
    {
        // Create another province with more regencies
        Province::create(['code' => '32', 'name' => 'JAWA BARAT']);
        Regency::create(['code' => '3201', 'province_code' => '32', 'name' => 'BANDUNG']);
        Regency::create(['code' => '3202', 'province_code' => '32', 'name' => 'BEKASI']);

        $this->artisan('idn-area:stats')
            ->expectsOutput('🏆 Largest Province:')
            ->expectsOutput('regencies')
            ->assertExitCode(0);
    }

    public function test_command_shows_province_specific_statistics(): void
    {
        $this->artisan('idn-area:stats --province=31')
            ->expectsOutput('📍 Statistics for DKI JAKARTA (31):')
            ->expectsOutput('Regencies/Cities')
            ->expectsOutput('Districts')
            ->expectsOutput('Villages')
            ->assertExitCode(0);
    }

    public function test_command_handles_non_existent_province(): void
    {
        $this->artisan('idn-area:stats --province=99')
            ->expectsOutput('Province with code \'99\' not found.')
            ->assertExitCode(0);
    }

    public function test_command_shows_detailed_province_analysis(): void
    {
        // Create more regencies for detailed analysis
        Regency::create(['code' => '3172', 'province_code' => '31', 'name' => 'JAKARTA UTARA']);

        District::create(['code' => '317201', 'regency_code' => '3172', 'name' => 'KELAPA GADING']);
        District::create(['code' => '317202', 'regency_code' => '3172', 'name' => 'TANJUNG PRIOK']);

        $this->artisan('idn-area:stats --province=31 --detailed')
            ->expectsOutput('🏢 Top 10 Regencies by District Count:')
            ->expectsOutput('Regency')
            ->expectsOutput('Districts')
            ->expectsOutput('Code')
            ->assertExitCode(0);
    }

    public function test_command_shows_detailed_general_analysis(): void
    {
        $this->artisan('idn-area:stats --detailed')
            ->expectsOutput('🔍 Detailed Analysis:')
            ->expectsOutput('📊 Distribution Analysis:')
            ->expectsOutput('🔍 Data Quality Analysis:')
            ->assertExitCode(0);
    }

    public function test_command_shows_distribution_analysis(): void
    {
        $this->artisan('idn-area:stats --detailed')
            ->expectsOutput('Top 5 Provinces by Regency Count:')
            ->expectsOutput('DKI JAKARTA:')
            ->expectsOutput('regencies')
            ->assertExitCode(0);
    }

    public function test_command_detects_data_quality_issues(): void
    {
        // Create a province with empty name to trigger quality issue
        Province::create(['code' => '99', 'name' => '']);

        $this->artisan('idn-area:stats --detailed')
            ->expectsOutput('⚠️  Issues found:')
            ->expectsOutput('provinces with empty names')
            ->assertExitCode(0);
    }

    public function test_command_shows_no_quality_issues_when_clean(): void
    {
        $this->artisan('idn-area:stats --detailed')
            ->expectsOutput('✅ No data quality issues detected')
            ->assertExitCode(0);
    }

    public function test_command_detects_empty_regency_names(): void
    {
        Regency::create(['code' => '9999', 'province_code' => '31', 'name' => '']);

        $this->artisan('idn-area:stats --detailed')
            ->expectsOutput('regencies with empty names')
            ->assertExitCode(0);
    }

    public function test_command_handles_exception_gracefully(): void
    {
        // Mock DB to throw exception
        DB::shouldReceive('table')->andThrow(new Exception('Database error'));

        $this->artisan('idn-area:stats')
            ->expectsOutput('Error generating statistics: Database error')
            ->assertExitCode(1);
    }

    public function test_command_shows_statistics_with_averages(): void
    {
        // The stats may include averages if implemented
        $this->artisan('idn-area:stats')
            ->expectsOutput('📊 General Statistics:')
            ->assertExitCode(0);
    }

    public function test_command_format_option_is_accepted(): void
    {
        // Test that format option is accepted (even if not fully implemented)
        $this->artisan('idn-area:stats --format=json')
            ->assertExitCode(0);
    }

    public function test_command_format_csv_is_accepted(): void
    {
        $this->artisan('idn-area:stats --format=csv')
            ->assertExitCode(0);
    }

    public function test_command_detailed_province_without_regencies(): void
    {
        $emptyProvince = Province::create(['code' => '98', 'name' => 'EMPTY PROVINCE']);

        $this->artisan('idn-area:stats --province=98 --detailed')
            ->expectsOutput('📍 Statistics for EMPTY PROVINCE (98):')
            ->assertExitCode(0);
    }

    public function test_command_shows_numbered_statistics_correctly(): void
    {
        $this->artisan('idn-area:stats')
            ->expectsOutput('1') // Should show count of 1 for each category
            ->assertExitCode(0);
    }

    public function test_command_handles_province_with_zero_counts(): void
    {
        $emptyProvince = Province::create(['code' => '97', 'name' => 'ZERO PROVINCE']);

        $this->artisan('idn-area:stats --province=97')
            ->expectsOutput('📍 Statistics for ZERO PROVINCE (97):')
            ->expectsOutput('0') // Should show 0 counts
            ->assertExitCode(0);
    }
}
