<?php

use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Services\IdnAreaSeeder;

// Test Seeder Service
describe('IdnAreaSeeder', function () {
    it('can instantiate seeder', function () {
        $seeder = new IdnAreaSeeder;

        expect($seeder)->toBeInstanceOf(IdnAreaSeeder::class);
    });

    it('has correct data path', function () {
        $seeder = new IdnAreaSeeder;

        // Use reflection to access protected property
        $reflection = new ReflectionClass($seeder);
        $property = $reflection->getProperty('dataPath');
        $property->setAccessible(true);
        $dataPath = $property->getValue($seeder);

        expect($dataPath)->toContain('database/seeders/data/');
    });

    it('detects existing data correctly', function () {
        $seeder = new IdnAreaSeeder;

        // Should return false when no data exists
        $reflection = new ReflectionClass($seeder);
        $method = $reflection->getMethod('hasData');
        $method->setAccessible(true);
        $hasData = $method->invoke($seeder);

        expect($hasData)->toBeFalse();

        // Create some data
        Province::create(['code' => '32', 'name' => 'JAWA BARAT']);

        // Should return true when data exists
        $hasData = $method->invoke($seeder);
        expect($hasData)->toBeTrue();
    });

    it('can parse boolean values correctly', function () {
        $seeder = new IdnAreaSeeder;

        $reflection = new ReflectionClass($seeder);
        $method = $reflection->getMethod('parseBool');
        $method->setAccessible(true);

        expect($method->invoke($seeder, true))->toBeTrue();
        expect($method->invoke($seeder, false))->toBeFalse();
        expect($method->invoke($seeder, 'true'))->toBeTrue();
        expect($method->invoke($seeder, 'false'))->toBeFalse();
        expect($method->invoke($seeder, '1'))->toBeTrue();
        expect($method->invoke($seeder, '0'))->toBeFalse();
        expect($method->invoke($seeder, 'yes'))->toBeTrue();
        expect($method->invoke($seeder, 'no'))->toBeFalse();
    });

    it('throws exception for missing CSV file', function () {
        $seeder = new IdnAreaSeeder;

        $reflection = new ReflectionClass($seeder);
        $method = $reflection->getMethod('readCsv');
        $method->setAccessible(true);

        expect(fn () => $method->invoke($seeder, '/nonexistent/file.csv'))
            ->toThrow(Exception::class, 'CSV file not found');
    });
});
