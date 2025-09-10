<?php

declare(strict_types=1);

use zaidysf\IdnArea\Facades\IdnArea;
use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

it('can get all provinces', function () {
    Province::factory()->create(['code' => '32', 'name' => 'West Java']);
    Province::factory()->create(['code' => '33', 'name' => 'Central Java']);

    $provinces = IdnArea::provinces(false); // Disable cache for test

    expect($provinces)->toHaveCount(2);
    expect($provinces->pluck('name')->toArray())->toContain('West Java', 'Central Java');
});

it('can get a province by code', function () {
    $created = Province::factory()->create(['code' => '32', 'name' => 'West Java']);

    $province = IdnArea::province('32', false);

    expect($province)->not->toBeNull();
    expect($province->code)->toBe('32');
    expect($province->name)->toBe('West Java');
});

it('returns null for non-existent province', function () {
    $province = IdnArea::province('99', false);

    expect($province)->toBeNull();
});

it('can get regencies by province', function () {
    $province = Province::factory()->create(['code' => '32']);
    $regency = Regency::factory()->forProvince($province->code)->create();
    $district = District::factory()->forRegency($regency->code)->create();
    $village = Village::factory()->forDistrict($district->code)->create();

    $regencies = IdnArea::regenciesByProvince('32', false);

    expect($regencies)->toHaveCount(1);
    expect($regencies->first()['code'])->toBe($regency->code);
    expect($regencies->first()['name'])->toBe($regency->name);
});

it('can get districts by regency', function () {
    $province = Province::factory()->create(['code' => '32']);
    $regency = Regency::factory()->forProvince($province->code)->create();
    $district = District::factory()->forRegency($regency->code)->create();
    $village = Village::factory()->forDistrict($district->code)->create();

    $districts = IdnArea::districtsByRegency($regency->code, 0, false);

    expect($districts)->toHaveCount(1);
    expect($districts->first()['code'])->toBe($district->code);
    expect($districts->first()['name'])->toBe($district->name);
});

it('can get all districts by regency', function () {
    $regency = Regency::factory()->create(['code' => '32.04', 'province_code' => '32']);
    District::factory()->create(['code' => '32.04.01', 'regency_code' => '32.04']);
    District::factory()->create(['code' => '32.04.02', 'regency_code' => '32.04']);
    District::factory()->create(['code' => '32.04.03', 'regency_code' => '32.04']);

    $districts = IdnArea::districtsByRegency('32.04', false);

    expect($districts)->toHaveCount(3);
});

it('can get villages by district', function () {
    $province = Province::factory()->create(['code' => '32']);
    $regency = Regency::factory()->forProvince($province->code)->create();
    $district = District::factory()->forRegency($regency->code)->create();
    $village = Village::factory()->forDistrict($district->code)->create();

    $villages = IdnArea::villagesByDistrict($district->code, 100, false);

    expect($villages)->toHaveCount(1);
    expect($villages->first()['code'])->toBe($village->code);
    expect($villages->first()['name'])->toBe($village->name);
});

it('can search areas', function () {
    Province::factory()->create(['code' => '31', 'name' => 'Jakarta']);
    Regency::factory()->create(['code' => '32.04', 'province_code' => '32', 'name' => 'Bandung']);
    District::factory()->create(['code' => '32.04.01', 'regency_code' => '32.04', 'name' => 'Sukasari']);

    $results = IdnArea::search('Jakarta', 'all', false);

    expect($results)->toHaveKey('provinces');
    expect($results['provinces'])->toHaveCount(1);
    expect($results['provinces']->first()->name)->toBe('Jakarta');
});

it('can search with partial match', function () {
    Province::factory()->create(['code' => '31', 'name' => 'Jakarta']);
    Province::factory()->create(['code' => '32', 'name' => 'Jakarta Barat']);

    $results = IdnArea::search('Jakarta', 'provinces', false);

    expect($results['provinces'])->toHaveCount(2);
    expect($results['provinces']->first()['name'])->toContain('Jakarta');
});

it('can get hierarchy', function () {
    $province = Province::factory()->create(['code' => '32']);
    $regency = Regency::factory()->forProvince($province->code)->create();
    $district = District::factory()->forRegency($regency->code)->create();
    $village = Village::factory()->forDistrict($district->code)->create();

    $result = IdnArea::hierarchy('32', false);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('name', $province->name);
    expect($result)->toHaveKey('regencies');
    expect($result['regencies'])->toHaveCount(1);
});

it('can get statistics', function () {
    $province = Province::factory()->create(['code' => '32']);
    $regency = Regency::factory()->forProvince($province->code)->create();
    $district = District::factory()->forRegency($regency->code)->create();
    $village = Village::factory()->forDistrict($district->code)->create();

    $stats = IdnArea::statistics(false);

    expect($stats)->toBeArray();
    expect($stats)->toHaveKeys([
        'provinces',
        'regencies',
        'districts',
        'villages',
    ]);
    expect($stats['provinces'])->toBe(1);
    expect($stats['regencies'])->toBe(1);
    expect($stats['districts'])->toBe(1);
    expect($stats['villages'])->toBe(1);
});

it('can get multiple areas by codes', function () {
    Province::factory()->create(['code' => '31', 'name' => 'Jakarta']);
    Province::factory()->create(['code' => '32', 'name' => 'West Java']);
    Province::factory()->create(['code' => '33', 'name' => 'Central Java']);

    $provinces = IdnArea::getMultiple(['31', '33'], 'provinces');

    expect($provinces)->toHaveCount(2);
    expect($provinces->pluck('name')->toArray())->toContain('Jakarta', 'Central Java');
});

it('can clear cache', function () {
    $result = IdnArea::clearCache();

    expect($result)->toBeBool();
});

it('handles empty search results gracefully', function () {
    $results = IdnArea::search('NonExistentPlace', 'all', false);

    expect($results)->toBeArray();
    expect($results['provinces'])->toBeEmpty();
    expect($results['regencies'])->toBeEmpty();
    expect($results['districts'])->toBeEmpty();
    expect($results['villages'])->toBeEmpty();
});

it('returns empty array for non-existent province hierarchy', function () {
    $result = IdnArea::hierarchy('99', false);

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});
