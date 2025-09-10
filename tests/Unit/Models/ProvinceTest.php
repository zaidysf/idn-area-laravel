<?php

declare(strict_types=1);

use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

it('can create a province', function () {
    $province = Province::factory()->create([
        'code' => '32',
        'name' => 'West Java',
    ]);

    expect($province->code)->toBe('32');
    expect($province->name)->toBe('West Java');
    expect($province->exists)->toBeTrue();
});

it('has correct fillable attributes', function () {
    $province = new Province;

    expect($province->getFillable())->toBe(['code', 'name']);
});

it('has correct casts', function () {
    $province = new Province;

    expect($province->getCasts())->toMatchArray([
        'code' => 'string',
        'name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ]);
});

it('uses code as primary key', function () {
    $province = new Province;

    expect($province->getKeyName())->toBe('code');
    expect($province->getIncrementing())->toBeFalse();
    expect($province->getKeyType())->toBe('string');
});

it('can have regencies', function () {
    $province = Province::factory()->create(['code' => '32']);
    $regency = Regency::factory()->create(['province_code' => '32']);

    expect($province->regencies)->toHaveCount(1);
    expect($province->regencies->first())->toBeInstanceOf(Regency::class);
});

it('can access districts through regencies', function () {
    $province = Province::factory()->create(['code' => '32']);
    $regency = Regency::factory()->forProvince($province->code)->create();
    $district = District::factory()->forRegency($regency->code)->create();

    $districts = $province->districts;

    expect($districts)->toHaveCount(1);
    expect($districts->first())->toBeInstanceOf(District::class);
});

it('can search provinces', function () {
    Province::factory()->create(['code' => '31', 'name' => 'Jakarta']);
    Province::factory()->create(['code' => '32', 'name' => 'West Java']);

    $results = Province::search('Jakarta');

    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Jakarta');
});

it('can get provinces with regency count', function () {
    $province = Province::factory()->create(['code' => '32']);
    Regency::factory()->create(['province_code' => '32']);
    Regency::factory()->create(['province_code' => '32']);

    $provinceWithCount = Province::withRegencyCount()->find('32');

    expect($provinceWithCount->regencies_count)->toBe(2);
});

it('has formatted name attribute', function () {
    $province = Province::factory()->create(['code' => '32', 'name' => 'West Java']);

    expect($province->formatted_name)->toBe('32 - West Java');
});

it('can check if has regencies', function () {
    $provinceWithRegencies = Province::factory()->create(['code' => '32']);
    Regency::factory()->create(['province_code' => '32']);

    $provinceWithoutRegencies = Province::factory()->create(['code' => '33']);

    expect($provinceWithRegencies->hasRegencies())->toBeTrue();
    expect($provinceWithoutRegencies->hasRegencies())->toBeFalse();
});

it('can get total districts count', function () {
    $province = Province::factory()->create(['code' => '32']);
    $regency = Regency::factory()->forProvince($province->code)->create();
    $district = District::factory()->forRegency($regency->code)->create();

    expect($province->total_districts)->toBe(1);
});

it('can get total villages count', function () {
    $province = Province::factory()->create(['code' => '32']);
    $regency = Regency::factory()->forProvince($province->code)->create();
    $district = District::factory()->forRegency($regency->code)->create();
    $village = Village::factory()->forDistrict($district->code)->create();

    expect($province->total_villages)->toBe(1);
});

it('uses code as route key', function () {
    $province = new Province;

    expect($province->getRouteKeyName())->toBe('code');
});
