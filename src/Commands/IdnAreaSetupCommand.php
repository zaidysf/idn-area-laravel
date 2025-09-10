<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class IdnAreaSetupCommand extends Command
{
    public $signature = 'idn-area:setup 
        {--mode= : Choose operation mode (local or api)}
        {--force : Force setup even if already configured}
        {--skip-validation : Skip system validation checks}
        {--skip-migration : Skip running database migrations}
        {--skip-seeding : Skip data seeding process}
        {--skip-connectivity : Skip API connectivity testing}
        {--access-key= : DataToko API access key (for non-interactive setup)}
        {--secret-key= : DataToko API secret key (for non-interactive setup)}
        {--token-storage= : Token storage method (cache, redis, file)}';

    public $description = 'Initial setup for Indonesian Area Data package - Choose data source mode';

    public function handle(): int
    {
        $this->displayWelcome();

        if ($this->isAlreadySetup() && ! $this->option('force')) {
            $this->info('✅ Package is already configured!');
            $this->line('Current mode: '.$this->getCurrentMode());
            $this->line('Use --force to reconfigure or run: php artisan idn-area:switch-mode');

            return self::SUCCESS;
        }

        // Get mode from parameter or interactive choice
        $mode = $this->option('mode') ?? $this->chooseMode();

        // Validate mode parameter
        if ($this->option('mode') && ! in_array($mode, ['local', 'api'])) {
            $this->error("Invalid mode: {$mode}. Must be 'local' or 'api'.");

            return self::FAILURE;
        }

        $this->info("🚀 Setting up Indonesian Area Data in {$mode} mode...");
        $this->newLine();

        try {
            // Publish migrations first
            $this->publishMigrations();

            // Run migrations unless skipped
            if (! $this->option('skip-migration')) {
                $this->runMigrations();
            } else {
                $this->warn('⚠️  Skipping database migrations');
            }

            if ($mode === 'local') {
                return $this->setupLocalMode();
            } else {
                return $this->setupApiMode();
            }
        } catch (\Exception $e) {
            $this->error('Setup failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function displayWelcome(): void
    {
        $this->info('┌─────────────────────────────────────────────────────────┐');
        $this->info('│         🇮🇩 Indonesian Area Data Package Setup         │');
        $this->info('│                     Version 2.0                        │');
        $this->info('└─────────────────────────────────────────────────────────┘');
        $this->newLine();
        $this->line('This package provides Indonesian administrative area data');
        $this->line('(provinces, regencies, districts, villages) from official sources.');
        $this->newLine();
    }

    private function chooseMode(): string
    {
        // Return default for non-interactive mode
        if ($this->option('force') && ! $this->input->isInteractive()) {
            return 'local'; // Default to local mode for automated setups
        }

        $this->info('📋 Please choose your data source mode:');
        $this->newLine();

        $this->line('🏠 <info>[1] Local Data Mode (Recommended)</info>');
        $this->line('   • Fast and reliable');
        $this->line('   • Works offline');
        $this->line('   • Uses pre-synced data from official BPS API');
        $this->line('   • Updated via sync commands');
        $this->newLine();

        $this->line('🌐 <info>[2] API Mode (Real-time)</info>');
        $this->line('   • Always latest data');
        $this->line('   • Direct from DataToko API (data.toko.center)');
        $this->line('   • Requires DataToko credentials');
        $this->line('   • ⚠️  Slower performance');
        $this->line('   • ⚠️  Dependent on API availability');
        $this->newLine();

        $choice = $this->choice('Which mode do you prefer?', [
            'Local Data Mode (Recommended)',
            'API Mode (Real-time)',
        ], 'Local Data Mode (Recommended)');

        return $choice === 'Local Data Mode (Recommended)' ? 'local' : 'api';
    }

    private function publishMigrations(): void
    {
        $this->info('📦 Publishing migrations...');
        try {
            $this->call('vendor:publish', [
                '--tag' => 'idn-area-migrations',
                '--force' => true,
            ]);
            $this->line('✅ Migrations published');
        } catch (\Exception $e) {
            // In testing environments, migrations might already be available
            if (app()->environment('testing')) {
                $this->line('⚠️  Migration publishing skipped in testing environment');
            } else {
                $this->warn('⚠️  Could not publish migrations: '.$e->getMessage());
                $this->line('Continuing with setup...');
            }
        }
    }

    private function runMigrations(): void
    {
        $this->info('🗄️ Running database migrations...');
        try {
            $this->call('migrate', ['--force' => true]);
            $this->line('✅ Database tables created');
        } catch (\Exception $e) {
            if (app()->environment('testing')) {
                $this->line('⚠️  Migration run skipped in testing environment');
            } else {
                $this->warn('⚠️  Could not run migrations: '.$e->getMessage());
                $this->line('You may need to run migrations manually: php artisan migrate');
            }
        }
    }

    private function setupLocalMode(): int
    {
        // Update config
        $this->updateConfig('local');

        if (! $this->option('skip-seeding')) {
            $this->info('🌱 Seeding data from pre-bundled CSV files...');
            $this->newLine();

            try {
                // Use CSV seeder for local mode (much faster)
                $exitCode = $this->call('idn-area:seed', ['--force' => true]);

                if ($exitCode !== 0) {
                    if (app()->environment('testing')) {
                        $this->warn('⚠️  Data seeding skipped in testing environment');
                    } else {
                        $this->error('Failed to seed data from CSV files');
                        $this->line('💡 If CSV files are missing, contact the package maintainer');

                        return self::FAILURE;
                    }
                }
            } catch (\Exception $e) {
                if (app()->environment('testing')) {
                    $this->warn('⚠️  Data seeding skipped in testing environment');
                } else {
                    $this->error('Seeding failed: '.$e->getMessage());

                    return self::FAILURE;
                }
            }
        } else {
            $this->warn('⚠️  Skipping data seeding - you will need to run data seeding later');
        }

        $this->newLine();
        $this->info('✅ <bg=green;fg=white> LOCAL MODE SETUP COMPLETE </bg=green;fg=white>');
        $this->newLine();
        $this->line('🎉 Your package is ready to use!');

        if (! $this->option('skip-seeding')) {
            $this->line('📝 Data seeded from pre-bundled CSV files');
            $this->line('💡 Package maintainer updates CSV files from official sources');
        } else {
            $this->line('📝 Run "php artisan idn-area:seed --force" to seed from CSV files');
        }

        return self::SUCCESS;
    }

    private function setupApiMode(): int
    {
        $this->info('🔐 Setting up DataToko API credentials...');
        $this->newLine();

        // Get credentials from options or prompt
        $accessKey = $this->option('access-key');
        if (empty($accessKey)) {
            $accessKey = $this->ask('Enter your DataToko Access Key');
        }
        if (empty($accessKey)) {
            $this->error('Access key is required for API mode');

            return self::FAILURE;
        }

        $secretKey = $this->option('secret-key');
        if (empty($secretKey)) {
            $secretKey = $this->secret('Enter your DataToko Secret Key');
        }
        if (empty($secretKey)) {
            $this->error('Secret key is required for API mode');

            return self::FAILURE;
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
            if (! in_array($tokenStorage, ['cache', 'redis', 'file'])) {
                $this->error('Invalid token storage method. Must be: cache, redis, or file');

                return self::FAILURE;
            }
        }

        // Update config and environment
        $this->updateConfig('api');
        $this->updateDataTokoCredentials($accessKey, $secretKey, $tokenStorage);

        // Test API connectivity unless skipped
        if (! $this->option('skip-connectivity')) {
            $this->info('🔍 Testing DataToko API connectivity...');

            try {
                $this->testDataTokoConnection($accessKey, $secretKey);
                $this->line('✅ DataToko API connection successful');
            } catch (\Exception $e) {
                $this->warn('⚠️  Could not connect to DataToko API: '.$e->getMessage());
                $this->warn('You can still proceed, but API calls may fail.');

                if (! $this->confirm('Continue with API mode setup?')) {
                    $this->line('Setup cancelled. Run the command again to choose a different mode.');

                    return self::FAILURE;
                }
            }
        } else {
            $this->line('⏭️ Skipping API connectivity test');
        }

        $this->newLine();
        $this->info('✅ <bg=green;fg=white> API MODE SETUP COMPLETE </bg=green;fg=white>');
        $this->newLine();
        $this->line('🎉 Your package is ready to use!');
        $this->line('⚡ Real-time data from DataToko API (data.toko.center)');
        $this->line('🔐 Credentials stored securely');
        $this->line('📦 Token storage: '.$tokenStorage);
        $this->line('⚠️  Remember: API calls may be slower and have limits');

        return self::SUCCESS;
    }

    private function updateConfig(string $mode): void
    {
        $configPath = config_path('idn-area.php');

        if (File::exists($configPath)) {
            $config = File::get($configPath);
            $config = preg_replace(
                "/'mode' => env\('IDN_AREA_MODE', '[^']+'\)/",
                "'mode' => env('IDN_AREA_MODE', '{$mode}')",
                $config
            );
            File::put($configPath, $config);
        }

        // Update .env if exists
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $env = File::get($envPath);
            if (str_contains($env, 'IDN_AREA_MODE=')) {
                $env = preg_replace('/IDN_AREA_MODE=.*/', "IDN_AREA_MODE={$mode}", $env);
            } else {
                $env .= "\nIDN_AREA_MODE={$mode}\n";
            }
            File::put($envPath, $env);
        }

        $this->line("✅ Configuration updated: mode = {$mode}");
    }

    private function isAlreadySetup(): bool
    {
        return config('idn-area.setup_completed', false) ||
               File::exists(database_path('migrations/create_idn_provinces_table.php'));
    }

    private function getCurrentMode(): string
    {
        return config('idn-area.mode', 'not configured');
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
                'IDN_AREA_DATATOKO_URL' => 'https://data.toko.center',
            ];

            foreach ($updates as $key => $value) {
                if (str_contains($env, "{$key}=")) {
                    $env = preg_replace("/{$key}=.*/", "{$key}={$value}", $env);
                } else {
                    $env .= "\n{$key}={$value}";
                }
            }

            File::put($envPath, $env);
            $this->line('✅ DataToko credentials saved to .env file');
        }
    }

    private function testDataTokoConnection(string $accessKey, string $secretKey): void
    {
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));

        // Create HMAC signature
        $message = $accessKey.$timestamp.$nonce;
        $signature = hash_hmac('sha256', $message, $secretKey);

        $postData = json_encode([
            'access_key' => $accessKey,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'signature' => $signature,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                'content' => $postData,
                'timeout' => 30,
            ],
        ]);

        $response = file_get_contents('https://data.toko.center/api/auth/login', false, $context);

        if (! $response) {
            throw new \Exception('No response from DataToko API');
        }

        $data = json_decode($response, true);
        if (! isset($data['data']['token']) && ! isset($data['token'])) {
            throw new \Exception('Invalid response format or authentication failed');
        }
    }
}
