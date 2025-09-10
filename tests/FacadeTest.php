<?php

use zaidysf\IdnArea\Facades\IdnArea;
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

        expect($regencies)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
        expect($regencies)->toHaveCount(0);
        expect($districts)->toHaveCount(0);
        expect($villages)->toHaveCount(0);
    });
});
