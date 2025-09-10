<?php

use zaidysf\IdnArea\Facades\IdnArea;
use zaidysf\IdnArea\Models\Island;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;

// Test Facade Integration
describe('IdnArea Facade', function () {
    beforeEach(function () {
        // Create test data
        Province::create(['code' => '31', 'name' => 'DKI JAKARTA']);
        Province::create(['code' => '32', 'name' => 'JAWA BARAT']);

        Regency::create(['code' => '31.71', 'province_code' => '31', 'name' => 'KOTA ADMINISTRASI JAKARTA PUSAT']);
        Regency::create(['code' => '32.04', 'province_code' => '32', 'name' => 'KABUPATEN BANDUNG']);

        Island::create(['name' => 'Pulau Bidadari', 'is_populated' => true, 'regency_code' => '31.71']);
        Island::create(['name' => 'Pulau Seribu', 'is_outermost_small' => true, 'regency_code' => '31.71']);
    });

    it('can get provinces via facade', function () {
        $provinces = IdnArea::provinces();

        expect($provinces)->toHaveCount(2);
        expect($provinces->pluck('name')->toArray())->toContain('DKI JAKARTA');
    });

    it('can get specific province via facade', function () {
        $province = IdnArea::province('31');

        expect($province)->not->toBeNull();
        expect($province->name)->toBe('DKI JAKARTA');
    });

    it('can get regencies by province via facade', function () {
        $regencies = IdnArea::regenciesByProvince('31');

        expect($regencies)->toHaveCount(1);
        expect($regencies->first()['name'])->toBe('KOTA ADMINISTRASI JAKARTA PUSAT');
    });

    it('can search areas via facade', function () {
        $results = IdnArea::search('JAKARTA');

        expect($results['provinces'])->toHaveCount(1);
        expect($results['regencies'])->toHaveCount(1);
        expect($results['provinces']->first()->name)->toBe('DKI JAKARTA');
    });

    it('can get area statistics via facade', function () {
        $stats = IdnArea::statistics();

        expect($stats['provinces'])->toBe(2);
        expect($stats['regencies'])->toBe(2);
        expect($stats['islands'])->toBe(2);
        expect($stats['populated_islands'])->toBe(1);
        expect($stats['outermost_small_islands'])->toBe(1);
    });

    it('can get populated islands via facade', function () {
        $islands = IdnArea::populatedIslands();

        expect($islands)->toHaveCount(1);
        expect($islands->first()->name)->toBe('Pulau Bidadari');
    });

    it('can get outermost small islands via facade', function () {
        $islands = IdnArea::outermostSmallIslands();

        expect($islands)->toHaveCount(1);
        expect($islands->first()->name)->toBe('Pulau Seribu');
    });

    it('can get islands by regency via facade', function () {
        $islands = IdnArea::islandsByRegency('31.71');

        expect($islands)->toHaveCount(2);
        expect($islands->pluck('name')->toArray())->toContain('Pulau Bidadari');
    });

    it('returns null for non-existent areas', function () {
        $province = IdnArea::province('99');
        $regency = IdnArea::regency('99.99');
        $district = IdnArea::district('99.99.99');
        $village = IdnArea::village('99.99.99.9999');

        expect($province)->toBeNull();
        expect($regency)->toBeNull();
        expect($district)->toBeNull();
        expect($village)->toBeNull();
    });

    it('returns empty collections for non-existent parent areas', function () {
        $regencies = IdnArea::regenciesByProvince('99');
        $districts = IdnArea::districtsByRegency('99.99');
        $villages = IdnArea::villagesByDistrict('99.99.99');
        $islands = IdnArea::islandsByRegency('99.99');

        expect($regencies)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
        expect($regencies)->toHaveCount(0);
        expect($districts)->toHaveCount(0);
        expect($villages)->toHaveCount(0);
        expect($islands)->toHaveCount(0);
    });
});
