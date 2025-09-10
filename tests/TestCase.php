<?php

namespace zaidysf\IdnArea\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use zaidysf\IdnArea\IdnAreaServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'zaidysf\\IdnArea\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            IdnAreaServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('idn-area.cache.enabled', false);

        // Load package migrations
        $migration = include __DIR__.'/../database/migrations/create_idn_provinces_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_idn_regencies_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_idn_districts_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_idn_villages_table.php.stub';
        $migration->up();
    }

    protected function defineDatabaseMigrations()
    {
        // This method is called automatically by TestBench
        // to set up the database before running tests
    }
}
