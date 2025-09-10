<?php

use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

// Test Model Factories
describe('Model Factories', function () {
    it('can create province using factory', function () {
        $province = Province::factory()->create();

        expect($province)->toBeInstanceOf(Province::class);
        expect($province->code)->toBeString();
        expect($province->name)->toBeString();
        expect($province->exists)->toBeTrue();
    });

    it('can create province with specific states', function () {
        $jakarta = Province::factory()->jakarta()->create();
        $westJava = Province::factory()->westJava()->create();

        expect($jakarta->code)->toBe('31');
        expect($jakarta->name)->toBe('DKI JAKARTA');
        expect($westJava->code)->toBe('32');
        expect($westJava->name)->toBe('JAWA BARAT');
    });

    it('can create regency using factory', function () {
        $regency = Regency::factory()->create();

        expect($regency)->toBeInstanceOf(Regency::class);
        expect($regency->code)->toBeString();
        expect($regency->province_code)->toBeString();
        expect($regency->name)->toBeString();
        expect($regency->exists)->toBeTrue();
    });

    it('can create regency for specific province', function () {
        $province = Province::factory()->jakarta()->create();
        $regency = Regency::factory()->forProvince($province->code)->create();

        expect($regency->province_code)->toBe($province->code);
        expect($regency->code)->toStartWith($province->code.'.');
    });

    it('can create regency with specific types', function () {
        $city = Regency::factory()->city()->create();
        $regency = Regency::factory()->regency()->create();

        expect($city->name)->toStartWith('KOTA');
        expect($regency->name)->toStartWith('KABUPATEN');
    });

    it('can create district using factory', function () {
        $district = District::factory()->create();

        expect($district)->toBeInstanceOf(District::class);
        expect($district->code)->toBeString();
        expect($district->regency_code)->toBeString();
        expect($district->name)->toBeString();
        expect($district->exists)->toBeTrue();
    });

    it('can create district for specific regency', function () {
        $regency = Regency::factory()->bandung()->create();
        $district = District::factory()->forRegency($regency->code)->create();

        expect($district->regency_code)->toBe($regency->code);
        expect($district->code)->toStartWith($regency->code.'.');
    });

    it('can create village using factory', function () {
        $village = Village::factory()->create();

        expect($village)->toBeInstanceOf(Village::class);
        expect($village->code)->toBeString();
        expect($village->district_code)->toBeString();
        expect($village->name)->toBeString();
        expect($village->exists)->toBeTrue();
    });

    it('can create village for specific district', function () {
        $district = District::factory()->sukasari()->create();
        $village = Village::factory()->forDistrict($district->code)->create();

        expect($village->district_code)->toBe($district->code);
        expect($village->code)->toStartWith($district->code.'.');
    });

    it('can create complete hierarchy using factories', function () {
        $province = Province::factory()->jakarta()->create();
        $regency = Regency::factory()->jakartaCentral()->create();
        $district = District::factory()->menteng()->create();
        $village = Village::factory()->gondangdia()->create();

        // Test relationships
        expect($regency->province->code)->toBe($province->code);
        expect($district->regency->code)->toBe($regency->code);
        expect($village->district->code)->toBe($district->code);

        // Test specific data
        expect($province->name)->toBe('DKI JAKARTA');
        expect($regency->name)->toBe('KOTA ADMINISTRASI JAKARTA PUSAT');
        expect($district->name)->toBe('MENTENG');
        expect($village->name)->toBe('GONDANGDIA');
    });

    it('can create multiple models using factories', function () {
        $provinces = Province::factory()->count(3)->create();
        $regencies = Regency::factory()->count(5)->create();

        expect($provinces)->toHaveCount(3);
        expect($regencies)->toHaveCount(5);
    });

    it('can make models without persisting to database', function () {
        $province = Province::factory()->make();
        $regency = Regency::factory()->make();

        expect($province->exists)->toBeFalse();
        expect($regency->exists)->toBeFalse();

        expect($province->code)->toBeString();
        expect($regency->name)->toBeString();
    });
});
