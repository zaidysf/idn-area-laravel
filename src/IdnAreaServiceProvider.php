<?php

declare(strict_types=1);

namespace zaidysf\IdnArea;

use Illuminate\Foundation\Console\AboutCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use zaidysf\IdnArea\Commands\IdnAreaCacheCommand;
use zaidysf\IdnArea\Commands\IdnAreaCommand;
use zaidysf\IdnArea\Commands\IdnAreaSetupCommand;
use zaidysf\IdnArea\Commands\IdnAreaStatsCommand;
use zaidysf\IdnArea\Commands\IdnAreaSwitchModeCommand;

class IdnAreaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * Enhanced Package Service Provider for Laravel 10/11/12
         * with modern features and optimizations
         */
        $package
            ->name('idn-area')
            ->hasConfigFile('idn-area')
            ->hasViews('idn-area')
            ->hasMigrations([
                'create_idn_area_table',
                'create_idn_provinces_table',
                'create_idn_regencies_table',
                'create_idn_districts_table',
                'create_idn_villages_table',
                'create_idn_islands_table',
            ])
            ->hasCommands([
                IdnAreaSetupCommand::class,
                IdnAreaSwitchModeCommand::class,
                IdnAreaCommand::class,
                IdnAreaStatsCommand::class,
                IdnAreaCacheCommand::class,
            ])
            ->hasTranslations()
            ->publishesServiceProvider('IdnAreaServiceProvider');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(IdnArea::class, function (\Illuminate\Contracts\Foundation\Application $app): IdnArea {
            return new IdnArea($app['config']['idn-area'] ?? []);
        });

        $this->app->alias(IdnArea::class, 'idn-area');

        // Register the IdnAreaManager as well for direct access
        $this->app->singleton(IdnAreaManager::class, function (\Illuminate\Contracts\Foundation\Application $app): IdnAreaManager {
            return new IdnAreaManager($app['config']['idn-area'] ?? []);
        });
    }

    public function packageBooted(): void
    {
        // Add information to Laravel's about command
        if (class_exists(AboutCommand::class)) {
            AboutCommand::add('Indonesian Area Package - by zaidysf', fn () => [
                'Version' => '3.0.0',
                'Data Source' => 'Official Indonesian Government Data (curated)',
                'Mode' => config('idn-area.mode', 'local'),
                'Laravel Compatibility' => '10.x, 11.x, 12.x',
                'PHP Compatibility' => '8.1+',
                'Data Coverage' => 'Provinces, Regencies, Districts, Villages',
                'Package Type' => 'Clean User Package (no direct API access)',
            ]);
        }

        // Register optimization commands if running in console
        if ($this->app->runningInConsole()) {
            $this->optimizes(
                optimize: 'idn-area:cache warm',
                clear: 'idn-area:cache clear',
            );
        }
    }
}
