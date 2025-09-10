<?php

use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

// Test Model Relationships
describe('Model Relationships', function () {
    beforeEach(function () {
        // Create test data
        $province = Province::create(['code' => '32', 'name' => 'JAWA BARAT']);
        $regency = Regency::create(['code' => '32.04', 'province_code' => '32', 'name' => 'KABUPATEN BANDUNG']);
        $district = District::create(['code' => '32.04.01', 'regency_code' => '32.04', 'name' => 'SUKASARI']);
        $village = Village::create(['code' => '32.04.01.2001', 'district_code' => '32.04.01', 'name' => 'SUKAMAJU']);
    });

    it('province has many regencies', function () {
        $province = Province::find('32');
        $regencies = $province->regencies;

        expect($regencies)->toHaveCount(1);
        expect($regencies->first()->name)->toBe('KABUPATEN BANDUNG');
    });

    it('province has many districts through regencies', function () {
        $province = Province::find('32');
        $districts = $province->districts;

        expect($districts)->toHaveCount(1);
        expect($districts->first()->name)->toBe('SUKASARI');
    });

    it('regency belongs to province', function () {
        $regency = Regency::find('32.04');
        $province = $regency->province;

        expect($province)->toBeInstanceOf(Province::class);
        expect($province->name)->toBe('JAWA BARAT');
    });

    it('regency has many districts', function () {
        $regency = Regency::find('32.04');
        $districts = $regency->districts;

        expect($districts)->toHaveCount(1);
        expect($districts->first()->name)->toBe('SUKASARI');
    });

    it('district belongs to regency', function () {
        $district = District::find('32.04.01');
        $regency = $district->regency;

        expect($regency)->toBeInstanceOf(Regency::class);
        expect($regency->name)->toBe('KABUPATEN BANDUNG');
    });

    it('district has many villages', function () {
        $district = District::find('32.04.01');
        $villages = $district->villages;

        expect($villages)->toHaveCount(1);
        expect($villages->first()->name)->toBe('SUKAMAJU');
    });

    it('village belongs to district', function () {
        $village = Village::find('32.04.01.2001');
        $district = $village->district;

        expect($district)->toBeInstanceOf(District::class);
        expect($district->name)->toBe('SUKASARI');
    });

    it('village has regency through district', function () {
        $village = Village::find('32.04.01.2001');
        $regency = $village->regency()->first();

        expect($regency)->toBeInstanceOf(Regency::class);
        expect($regency->name)->toBe('KABUPATEN BANDUNG');
    });

});
