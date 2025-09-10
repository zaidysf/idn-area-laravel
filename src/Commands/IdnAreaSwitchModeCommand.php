<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Village;
use zaidysf\IdnArea\Services\BpsApiService;
use zaidysf\IdnArea\Services\DataTokoApiService;
use zaidysf\IdnArea\Exceptions\DataTokoApiException;

class IdnAreaSwitchModeCommand extends Command
{
    public $signature = 'idn-area:switch-mode 
        {mode? : Mode to switch to (api or local)}
        {--force : Switch without confirmation}
        {--skip-validation : Skip data validation}
        {--skip-migration : Skip running migrations}
        {--skip-seeding : Skip data seeding for local mode}
        {--skip-connectivity : Skip API connectivity testing}
        {--access-key= : DataToko API access key (for non-interactive setup)}
        {--secret-key= : DataToko API secret key (for non-interactive setup)}
        {--token-storage= : Token storage method (cache, redis, file)}';

    public $description = 'Switch between API and Local data modes';

    public function handle(): int
    {
        $this->displayWelcome();

        $currentMode = config('idn-area.mode', 'local');
        $this->line("Current mode: <fg=yellow>{$currentMode}</fg=yellow>");
        $this->newLine();

        $targetMode = $this->argument('mode');

        if (!$targetMode) {
            $targetMode = $this->chooseMode($currentMode);
        }

        if (!in_array($targetMode, ['api', 'local'])) {
            $this->error("Invalid mode: {$targetMode}. Must be 'api' or 'local'.");
            return self::FAILURE;
        }

        if ($targetMode === $currentMode) {
            $this->info("✅ Already in {$targetMode} mode.");
            return self::SUCCESS;
        }

        if (!$this->option('force')) {
            if (!$this->confirmSwitch($currentMode, $targetMode)) {
                $this->line('Mode switch cancelled.');
                return self::SUCCESS;
            }
        }

        try {
            // Step 1: Validate prerequisites
            if (!$this->option('skip-validation')) {
                $this->validatePrerequisites($targetMode);
            }

            // Step 2: Run migrations if needed
            if (!$this->option('skip-migration')) {
                $this->ensureMigrations();
            }

            // Step 3: Switch mode
            $this->switchToMode($targetMode);

            // Step 4: Handle local mode data requirements
            if ($targetMode === 'local' && !$this->option('skip-seeding')) {
                $this->handleLocalModeData();
            }
            
            $this->newLine();
            $this->info("✅ <bg=green;fg=white> MODE SWITCH SUCCESSFUL </bg=green;fg=white>");
            $this->line("🎉 Switched to {$targetMode} mode successfully!");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to switch mode: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function displayWelcome(): void
    {
        $this->info('┌─────────────────────────────────────────────────────────┐');
        $this->info('│         🔄 Mode Switcher - by zaidysf                  │');
        $this->info('│           Switch between API and Local modes           │');
        $this->info('└─────────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function chooseMode(string $currentMode): string
    {
        $this->info('🎛️ Choose your preferred mode:');
        $this->newLine();

        if ($currentMode === 'local') {
            $this->line('🌐 <info>[1] API Mode (Real-time)</info>');
            $this->line('   • Always latest data');
            $this->line('   • Direct from DataToko API (data.toko.center)');
            $this->line('   • Requires DataToko credentials');
            $this->line('   • ⚠️  Slower performance');
            $this->line('   • ⚠️  Depends on internet connectivity');
            $this->newLine();
            
            $this->line('🏠 <comment>[2] Local Mode (Current)</comment>');
            $this->line('   • Fast and reliable');
            $this->line('   • Works offline');
            $this->line('   • Uses synced BPS data');
        } else {
            $this->line('🌐 <comment>[1] API Mode (Current)</comment>');
            $this->line('   • Always latest data');
            $this->line('   • Direct from DataToko API (data.toko.center)');
            $this->line('   • Requires DataToko credentials');
            $this->line('   • ⚠️  Slower performance');
            $this->line('   • ⚠️  Depends on internet connectivity');
            $this->newLine();
            
            $this->line('🏠 <info>[2] Local Mode (Recommended)</info>');
            $this->line('   • Fast and reliable');
            $this->line('   • Works offline');
            $this->line('   • Uses synced BPS data');
        }

        $this->newLine();

        $choice = $this->choice('Which mode would you like to switch to?', [
            1 => 'API Mode (Real-time)',
            2 => 'Local Mode (Recommended)'
        ], $currentMode === 'local' ? 2 : 1);

        return $choice === 'API Mode (Real-time)' ? 'api' : 'local';
    }

    private function confirmSwitch(string $from, string $to): bool
    {
        $this->newLine();
        $this->line("You are about to switch from <fg=red>{$from}</fg=red> mode to <fg=green>{$to}</fg=green> mode.");
        $this->newLine();

        if ($to === 'api') {
            $this->warn('⚠️  Switching to API mode means:');
            $this->line('   • Data will be fetched in real-time from DataToko API');
            $this->line('   • Requires DataToko credentials');
            $this->line('   • Slower response times');
            $this->line('   • Requires stable internet connection');
            $this->line('   • Subject to API rate limits');
        } else {
            $this->info('✅ Switching to Local mode means:');
            $this->line('   • Faster response times');
            $this->line('   • Works offline');
            $this->line('   • Requires periodic data sync');
            
            if (!$this->hasLocalData()) {
                $this->newLine();
                $this->warn('⚠️  You will need to sync data after switching:');
                $this->line('   php artisan idn-area:sync-bps --initial');
            }
        }

        $this->newLine();
        return $this->confirm('Continue with mode switch?', true);
    }

    private function switchToMode(string $mode): void
    {
        $this->info("🔄 Switching to {$mode} mode...");

        // Update config file
        $this->updateConfig($mode);

        // Update .env file
        $this->updateEnvFile($mode);

        $this->line("   ✅ Configuration updated");
    }

    private function updateConfig(string $mode): void
    {
        $configPath = config_path('idn-area.php');
        
        if (!File::exists($configPath)) {
            $this->warn('Config file not found. You may need to publish it first:');
            $this->line('php artisan vendor:publish --tag="idn-area-config"');
            return;
        }

        $config = File::get($configPath);
        $config = preg_replace(
            "/'mode' => env\('IDN_AREA_MODE', '[^']+'\)/",
            "'mode' => env('IDN_AREA_MODE', '{$mode}')",
            $config
        );
        File::put($configPath, $config);
    }

    private function updateEnvFile(string $mode): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            $this->warn('.env file not found. You may need to set IDN_AREA_MODE manually.');
            return;
        }

        $env = File::get($envPath);
        
        if (str_contains($env, 'IDN_AREA_MODE=')) {
            $env = preg_replace('/IDN_AREA_MODE=.*/', "IDN_AREA_MODE={$mode}", $env);
        } else {
            $env .= "\nIDN_AREA_MODE={$mode}\n";
        }
        
        File::put($envPath, $env);
    }

    private function hasLocalData(): bool
    {
        try {
            return Province::count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function validatePrerequisites(string $targetMode): void
    {
        $this->info('🔍 Validating prerequisites...');

        if ($targetMode === 'api') {
            $this->validateApiMode();
        } else {
            $this->validateLocalMode();
        }

        $this->line('   ✅ Prerequisites validated');
    }

    private function validateApiMode(): void
    {
        $this->line('   • Checking DataToko API credentials...');
        
        $accessKey = config('idn-area.datatoko_api.access_key');
        $secretKey = config('idn-area.datatoko_api.secret_key');
        
        if (empty($accessKey) || empty($secretKey)) {
            $this->line('   • DataToko credentials not found, prompting for setup...');
            $this->promptForDataTokoCredentials();
        } else {
            $this->line('   • DataToko credentials found');
            
            // Test connectivity with existing credentials
            $this->line('   • Testing DataToko API connectivity...');
            try {
                $dataTokoService = new DataTokoApiService();
                $provinces = $dataTokoService->getAllProvinces();
                $this->line("   • DataToko API test: OK ({$provinces->count()} provinces)");
            } catch (DataTokoApiException $e) {
                $this->warn('   • DataToko API connection failed with current credentials');
                if ($this->confirm('Would you like to re-enter your DataToko credentials?', true)) {
                    $this->promptForDataTokoCredentials();
                } else {
                    throw new \Exception('DataToko API validation failed: ' . $e->getMessage());
                }
            }
        }
    }

    private function validateLocalMode(): void
    {
        $this->line('   • Checking database configuration...');
        
        // Check database connection
        try {
            \DB::connection()->getPdo();
            $this->line('   • Database connection: OK');
        } catch (\Exception $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }

        // Check if CSV data files exist for seeding
        $dataPath = __DIR__ . '/../../database/seeders/data/';
        $requiredFiles = ['provinces.csv', 'regencies.csv', 'districts.csv'];
        
        foreach ($requiredFiles as $file) {
            if (!File::exists($dataPath . $file)) {
                $this->warn("   • Missing data file: {$file}");
            } else {
                $this->line("   • Data file {$file}: OK");
            }
        }

        // Check villages.csv separately as it might still be generating
        if (!File::exists($dataPath . 'villages.csv')) {
            if (File::exists($dataPath . 'villages_partial.csv')) {
                $this->warn('   • Villages data is still being generated (partial file found)');
            } else {
                $this->warn('   • Villages data file not found - will need to generate or sync');
            }
        } else {
            $this->line('   • Data file villages.csv: OK');
        }
    }

    private function ensureMigrations(): void
    {
        $this->info('📊 Checking database migrations...');

        $tables = ['idn_provinces', 'idn_regencies', 'idn_districts', 'idn_villages'];
        $missingTables = [];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }

        if (!empty($missingTables)) {
            $this->warn('   Missing tables: ' . implode(', ', $missingTables));
            $this->info('   Running migrations...');
            
            try {
                Artisan::call('migrate', ['--force' => true]);
                $this->line('   ✅ Migrations completed successfully');
            } catch (\Exception $e) {
                throw new \Exception('Migration failed: ' . $e->getMessage());
            }
        } else {
            $this->line('   ✅ All required tables exist');
        }
    }

    private function handleLocalModeData(): void
    {
        if ($this->hasLocalData()) {
            $counts = $this->getDataCounts();
            $this->line("   📊 Current local data:");
            $this->line("      • Provinces: {$counts['provinces']}");
            $this->line("      • Regencies: {$counts['regencies']}");
            $this->line("      • Districts: {$counts['districts']}");
            $this->line("      • Villages: {$counts['villages']}");

            if ($counts['total'] < 50000) { // Expected ~90k total records
                $this->warn('   ⚠️  Local data seems incomplete');
                
                if ($this->confirm('Would you like to sync data from BPS API now?', true)) {
                    $this->syncLocalData();
                }
            } else {
                $this->line('   ✅ Local data appears complete');
            }
        } else {
            $this->warn('   ⚠️  No local data found');
            
            if ($this->hasDataFiles()) {
                if ($this->confirm('Would you like to seed data from CSV files?', true)) {
                    $this->seedLocalData();
                }
            } else {
                if ($this->confirm('Would you like to seed data now?', true)) {
                    $this->syncLocalData();
                } else {
                    $this->warn('   You will need to seed data later: php artisan idn-area:seed --force');
                }
            }
        }
    }

    private function hasDataFiles(): bool
    {
        $dataPath = __DIR__ . '/../../database/seeders/data/';
        $requiredFiles = ['provinces.csv', 'regencies.csv', 'districts.csv'];
        
        foreach ($requiredFiles as $file) {
            if (!File::exists($dataPath . $file)) {
                return false;
            }
        }
        
        return true;
    }

    private function syncLocalData(): void
    {
        $this->info('🌱 Seeding data from pre-bundled CSV files...');
        
        try {
            // Try CSV seeder first (much faster for local mode)
            $exitCode = Artisan::call('idn-area:seed', ['--force' => true]);
            
            if ($exitCode !== 0) {
                $this->warn('   CSV seeding failed, trying BPS API sync as fallback...');
                $this->info('🔄 Syncing data from BPS API...');
                Artisan::call('idn-area:sync-bps', ['--initial' => true]);
            }
            
            $this->line('   ✅ Data seeding completed successfully');
        } catch (\Exception $e) {
            throw new \Exception('Data seeding failed: ' . $e->getMessage());
        }
    }

    private function seedLocalData(): void
    {
        $this->info('🌱 Seeding data from CSV files...');
        
        try {
            Artisan::call('idn-area:seed', ['--force' => true]);
            $this->line('   ✅ Data seeding completed successfully');
        } catch (\Exception $e) {
            throw new \Exception('Data seeding failed: ' . $e->getMessage());
        }
    }

    private function getDataCounts(): array
    {
        try {
            $provinces = Province::count();
            $regencies = Regency::count();
            $districts = District::count();
            $villages = Village::count();
            
            return [
                'provinces' => $provinces,
                'regencies' => $regencies,
                'districts' => $districts,
                'villages' => $villages,
                'total' => $provinces + $regencies + $districts + $villages,
            ];
        } catch (\Exception $e) {
            return [
                'provinces' => 0,
                'regencies' => 0,
                'districts' => 0,
                'villages' => 0,
                'total' => 0,
            ];
        }
    }

    private function promptForDataTokoCredentials(): void
    {
        $this->newLine();
        $this->info('🔐 DataToko API Credentials Setup');
        $this->line('You need DataToko API credentials to use API mode.');
        $this->newLine();

        // Get credentials from options or prompt
        $accessKey = $this->option('access-key');
        if (empty($accessKey)) {
            $accessKey = $this->ask('Enter your DataToko Access Key');
        }
        if (empty($accessKey)) {
            throw new \Exception('Access key is required for API mode');
        }

        $secretKey = $this->option('secret-key');
        if (empty($secretKey)) {
            $secretKey = $this->secret('Enter your DataToko Secret Key');
        }
        if (empty($secretKey)) {
            throw new \Exception('Secret key is required for API mode');
        }

        // Get token storage from options or prompt
        $tokenStorage = $this->option('token-storage');
        if (empty($tokenStorage)) {
            $this->newLine();
            $this->info('📦 Choose token storage method:');
            $tokenStorage = $this->choice(
                'How should authentication tokens be stored?',
                ['cache', 'redis', 'file'],
                'cache'
            );
        } else {
            // Validate the provided token storage option
            if (!in_array($tokenStorage, ['cache', 'redis', 'file'])) {
                throw new \Exception('Invalid token storage method. Must be: cache, redis, or file');
            }
        }

        // Update environment variables
        $this->updateDataTokoCredentials($accessKey, $secretKey, $tokenStorage);
        
        // Test the new credentials unless skipped
        if (!$this->option('skip-connectivity')) {
            $this->line('   • Testing new DataToko credentials...');
            try {
                $this->testDataTokoConnection($accessKey, $secretKey);
                $this->line('   • DataToko API connection successful');
            } catch (\Exception $e) {
                throw new \Exception('DataToko API connection failed: ' . $e->getMessage());
            }
        } else {
            $this->line('   • Skipping API connectivity test');
        }
    }

    private function updateDataTokoCredentials(string $accessKey, string $secretKey, string $tokenStorage): void
    {
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $env = File::get($envPath);
            
            // Update or add DataToko credentials
            $updates = [
                'IDN_AREA_ACCESS_KEY' => $accessKey,
                'IDN_AREA_SECRET_KEY' => $secretKey,
                'IDN_AREA_TOKEN_STORAGE' => $tokenStorage,
                'IDN_AREA_DATATOKO_URL' => 'https://data.toko.center'
            ];
            
            foreach ($updates as $key => $value) {
                if (str_contains($env, "{$key}=")) {
                    $env = preg_replace("/{$key}=.*/", "{$key}={$value}", $env);
                } else {
                    $env .= "\n{$key}={$value}";
                }
            }
            
            File::put($envPath, $env);
            $this->line('   • DataToko credentials saved to .env file');
        }
    }

    private function testDataTokoConnection(string $accessKey, string $secretKey): void
    {
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));
        
        // Create HMAC signature
        $message = $accessKey . $timestamp . $nonce;
        $signature = hash_hmac('sha256', $message, $secretKey);
        
        $postData = json_encode([
            'access_key' => $accessKey,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'signature' => $signature
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                'content' => $postData,
                'timeout' => 30
            ]
        ]);
        
        $response = file_get_contents('https://data.toko.center/api/auth/login', false, $context);
        
        if (!$response) {
            throw new \Exception('No response from DataToko API');
        }
        
        $data = json_decode($response, true);
        if (!isset($data['data']['token']) && !isset($data['token'])) {
            throw new \Exception('Invalid response format or authentication failed');
        }
    }
}