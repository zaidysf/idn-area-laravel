<?php

declare(strict_types=1);

use zaidysf\IdnArea\IdnArea;
use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

beforeEach(function () {
    // Create test data
    $this->province = Province::create([
        'code' => '36',
        'name' => 'BANTEN',
    ]);

    $this->regency = Regency::create([
        'code' => '3671',
        'province_code' => '36',
        'name' => 'TANGERANG',
    ]);

    $this->district = District::create([
        'code' => '367101',
        'regency_code' => '3671',
        'name' => 'TIGARAKSA',
    ]);

    $this->village = Village::create([
        'code' => '3671011001',
        'district_code' => '367101',
        'name' => 'KEDAUNG WETAN',
    ]);

    Village::create([
        'code' => '3671011002',
        'district_code' => '367101',
        'name' => 'KEDAUNG',
    ]);

    $this->idnArea = new IdnArea();
});

describe('IdnArea Extended Methods', function () {
    it('can get mode', function () {
        $mode = $this->idnArea->getMode();
        expect($mode)->toBeString();
    });

    it('can check if API is available', function () {
        $available = $this->idnArea->isApiAvailable();
        expect($available)->toBeBool();
    });

    it('can get periods', function () {
        $periods = $this->idnArea->getPeriods();
        expect($periods)->toBeArray();
    });

    it('can clear cache', function () {
        $result = $this->idnArea->clearCache();
        expect($result)->toBeBool();
    });

    it('can get hierarchy', function () {
        $hierarchy = $this->idnArea->hierarchy('36', false);
        
        expect($hierarchy)->toBeArray()
            ->and($hierarchy)->toHaveKeys(['code', 'name', 'province', 'regencies'])
            ->and($hierarchy['code'])->toBe('36')
            ->and($hierarchy['name'])->toBe('BANTEN')
            ->and($hierarchy['regencies'])->toHaveCount(1);

        $regency = $hierarchy['regencies'][0];
        expect($regency)->toHaveKeys(['regency', 'districts'])
            ->and($regency['districts'])->toHaveCount(1);

        $district = $regency['districts'][0];
        expect($district)->toHaveKeys(['district', 'villages'])
            ->and($district['villages'])->toHaveCount(2);
    });

    it('can get multiple areas by codes', function () {
        $codes = ['36', '3671', '367101', '3671011001'];
        $results = $this->idnArea->getMultiple($codes, 'auto');
        
        expect($results)->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($results)->toHaveCount(4);

        // Check that each result has the right type
        $types = $results->pluck('type')->toArray();
        expect($types)->toContain('province', 'regency', 'district', 'village');
    });

    it('can get multiple areas with specific type', function () {
        $results = $this->idnArea->getMultiple(['36'], 'province');
        expect($results)->toHaveCount(1);

        $results = $this->idnArea->getMultiple(['3671'], 'regency');
        expect($results)->toHaveCount(1);

        $results = $this->idnArea->getMultiple(['367101'], 'district');
        expect($results)->toHaveCount(1);

        $results = $this->idnArea->getMultiple(['3671011001'], 'village');
        expect($results)->toHaveCount(1);
    });

    it('handles non-existent codes in multiple', function () {
        $results = $this->idnArea->getMultiple(['99999'], 'auto');
        expect($results)->toHaveCount(0);
    });

    it('handles empty hierarchy', function () {
        $hierarchy = $this->idnArea->hierarchy('99', false);
        expect($hierarchy)->toBeArray()->toBeEmpty();
    });

    it('can handle different config', function () {
        $idnArea = new IdnArea(['mode' => 'local']);
        expect($idnArea->getMode())->toBe('local');
    });

    it('can get all data types', function () {
        $provinces = $this->idnArea->provinces();
        expect($provinces)->toBeInstanceOf(\Illuminate\Support\Collection::class);

        $regencies = $this->idnArea->regencies();
        expect($regencies)->toBeInstanceOf(\Illuminate\Support\Collection::class);

        $districts = $this->idnArea->districts();
        expect($districts)->toBeInstanceOf(\Illuminate\Support\Collection::class);

        $villages = $this->idnArea->villages();
        expect($villages)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('can get specific items', function () {
        $province = $this->idnArea->province('36');
        expect($province)->not()->toBeNull();

        $regency = $this->idnArea->regency('3671');
        expect($regency)->not()->toBeNull();

        $district = $this->idnArea->district('367101');
        expect($district)->not()->toBeNull();

        $village = $this->idnArea->village('3671011001');
        expect($village)->not()->toBeNull();
    });

    it('can get related data', function () {
        $regencies = $this->idnArea->regenciesByProvince('36');
        expect($regencies)->toBeInstanceOf(\Illuminate\Support\Collection::class);

        $districts = $this->idnArea->districtsByRegency('3671');
        expect($districts)->toBeInstanceOf(\Illuminate\Support\Collection::class);

        $villages = $this->idnArea->villagesByDistrict('367101');
        expect($villages)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('can search different types', function () {
        $allResults = $this->idnArea->search('BANTEN', 'all');
        expect($allResults)->toBeArray()
            ->and($allResults)->toHaveKeys(['provinces', 'regencies', 'districts', 'villages']);

        $provinceResults = $this->idnArea->search('BANTEN', 'provinces');
        expect($provinceResults)->toBeArray()
            ->and($provinceResults)->toHaveKey('provinces');

        $regencyResults = $this->idnArea->search('TANGERANG', 'regencies');
        expect($regencyResults)->toBeArray()
            ->and($regencyResults)->toHaveKey('regencies');
    });
});