<?php

declare(strict_types=1);

use zaidysf\IdnArea\IdnAreaManager;
use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;
use zaidysf\IdnArea\Services\LocalDataService;

beforeEach(function () {
    // Create test data
    $this->province = Province::create([
        'code' => '35',
        'name' => 'JAWA TIMUR',
    ]);

    $this->regency = Regency::create([
        'code' => '3578',
        'province_code' => '35',
        'name' => 'SURABAYA',
    ]);

    $this->district = District::create([
        'code' => '357801',
        'regency_code' => '3578',
        'name' => 'GUBENG',
    ]);

    $this->village = Village::create([
        'code' => '3578011001',
        'district_code' => '357801',
        'name' => 'AIRLANGGA',
    ]);

    $this->manager = new IdnAreaManager(['mode' => 'local']);
});

describe('IdnAreaManager Extended Methods', function () {
    it('can get periods', function () {
        $periods = $this->manager->getPeriods();
        expect($periods)->toBeArray();
    });

    it('can clear cache', function () {
        $result = $this->manager->clearCache();
        expect($result)->toBeBool();
    });

    it('can get mode information', function () {
        $mode = $this->manager->getMode();
        expect($mode)->toBe('local');

        $currentMode = $this->manager->getCurrentMode();
        expect($currentMode)->toBe('local');
    });

    it('can check if API is available', function () {
        $available = $this->manager->isApiAvailable();
        expect($available)->toBeBool();
    });

    it('can get statistics', function () {
        $stats = $this->manager->statistics();
        
        expect($stats)->toBeArray()
            ->and($stats)->toHaveKeys(['provinces', 'regencies', 'districts', 'villages'])
            ->and($stats['provinces'])->toBe(1)
            ->and($stats['regencies'])->toBe(1)
            ->and($stats['districts'])->toBe(1)
            ->and($stats['villages'])->toBe(1);
    });

    it('can search provinces', function () {
        $results = $this->manager->searchProvinces('JAWA');
        expect($results)->toBeArray();
        expect($results[0]['name'])->toContain('JAWA');
    });

    it('can search regencies', function () {
        $results = $this->manager->searchRegencies('SURABAYA');
        expect($results)->toBeArray();
        expect($results[0]['name'])->toContain('SURABAYA');
    });

    it('can search districts', function () {
        $results = $this->manager->searchDistricts('GUBENG');
        expect($results)->toBeArray();
        expect($results[0]['name'])->toContain('GUBENG');
    });

    it('can search villages', function () {
        $results = $this->manager->searchVillages('AIRLANGGA');
        expect($results)->toBeArray();
        expect($results[0]['name'])->toContain('AIRLANGGA');
    });

    it('can get current service', function () {
        $service = $this->manager->getCurrentService();
        expect($service)->toBeInstanceOf(LocalDataService::class);
    });

    it('can get current mode', function () {
        $mode = $this->manager->getCurrentMode();
        expect($mode)->toBe('local');
    });

    it('can check if service is available', function () {
        $available = $this->manager->isServiceAvailable();
        expect($available)->toBeBool();
    });

    it('handles different config values', function () {
        // Test with different mode settings - local only to avoid API credential requirements
        $manager = new IdnAreaManager(['mode' => 'local']);
        expect($manager->getMode())->toBe('local');

        $manager = new IdnAreaManager(['cache' => ['enabled' => true, 'ttl' => 7200]]);
        expect($manager->getMode())->toBe('local'); // Default mode
    });

    it('can get all data methods with caching', function () {
        $provinces = $this->manager->provinces(true);
        expect($provinces)->toBeInstanceOf(\Illuminate\Support\Collection::class);

        $regencies = $this->manager->regencies(true);
        expect($regencies)->toBeInstanceOf(\Illuminate\Support\Collection::class);

        $districts = $this->manager->districts(true);
        expect($districts)->toBeInstanceOf(\Illuminate\Support\Collection::class);

        $villages = $this->manager->villages(true);
        expect($villages)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('can get individual items', function () {
        $province = $this->manager->province('35');
        expect($province)->not()->toBeNull();

        $regency = $this->manager->regency('3578');
        expect($regency)->not()->toBeNull();

        $district = $this->manager->district('357801');
        expect($district)->not()->toBeNull();

        $village = $this->manager->village('3578011001');
        expect($village)->not()->toBeNull();
    });

    it('can get related data', function () {
        $regencies = $this->manager->regenciesByProvince('35');
        expect($regencies)->toBeInstanceOf(\Illuminate\Support\Collection::class);

        $districts = $this->manager->districtsByRegency('3578');
        expect($districts)->toBeInstanceOf(\Illuminate\Support\Collection::class);

        $villages = $this->manager->villagesByDistrict('357801');
        expect($villages)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });
});