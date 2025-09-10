<?php

use zaidysf\IdnArea\Facades\IdnArea as IdnAreaFacade;
use zaidysf\IdnArea\IdnArea;
use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

// Test main service class
it('can instantiate the main class', function () {
    $idnArea = new IdnArea;

    expect($idnArea)->toBeInstanceOf(IdnArea::class);
});

it('can get empty provinces collection', function () {
    $idnArea = new IdnArea;
    $provinces = $idnArea->provinces();

    expect($provinces)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($provinces)->toHaveCount(0); // Empty since no data seeded in tests
});

it('can search areas with empty results', function () {
    $idnArea = new IdnArea;
    $results = $idnArea->search('Jakarta');

    expect($results)->toBeArray();
    expect($results)->toHaveKeys(['provinces', 'regencies', 'districts', 'villages']);
});

it('can get statistics with zero counts', function () {
    $idnArea = new IdnArea;
    $stats = $idnArea->statistics();

    expect($stats)->toBeArray();
    expect($stats)->toHaveKeys([
        'provinces',
        'regencies',
        'districts',
        'villages',
    ]);
    expect($stats['provinces'])->toBe(0);
    expect($stats['regencies'])->toBe(0);
});

// Test models structure
it('province model has correct configuration', function () {
    $province = new Province;

    expect($province->getTable())->toBe('idn_provinces');
    expect($province->getKeyName())->toBe('code');
    expect($province->getKeyType())->toBe('string');
    expect($province->getIncrementing())->toBeFalse();
});

it('regency model has correct configuration', function () {
    $regency = new Regency;

    expect($regency->getTable())->toBe('idn_regencies');
    expect($regency->getKeyName())->toBe('code');
    expect($regency->getKeyType())->toBe('string');
    expect($regency->getIncrementing())->toBeFalse();
});

it('district model has correct configuration', function () {
    $district = new District;

    expect($district->getTable())->toBe('idn_districts');
    expect($district->getKeyName())->toBe('code');
    expect($district->getKeyType())->toBe('string');
    expect($district->getIncrementing())->toBeFalse();
});

it('village model has correct configuration', function () {
    $village = new Village;

    expect($village->getTable())->toBe('idn_villages');
    expect($village->getKeyName())->toBe('code');
    expect($village->getKeyType())->toBe('string');
    expect($village->getIncrementing())->toBeFalse();
});


// Test facade
it('can use facade', function () {
    $provinces = IdnAreaFacade::provinces();

    expect($provinces)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

// Test with sample data
it('can create and retrieve province', function () {
    $province = Province::create([
        'code' => '32',
        'name' => 'JAWA BARAT',
    ]);

    expect($province)->toBeInstanceOf(Province::class);
    expect($province->code)->toBe('32');
    expect($province->name)->toBe('JAWA BARAT');

    $retrieved = Province::find('32');
    expect($retrieved)->not->toBeNull();
    expect($retrieved->name)->toBe('JAWA BARAT');
});

it('can create regency with province relationship', function () {
    $province = Province::create([
        'code' => '32',
        'name' => 'JAWA BARAT',
    ]);

    $regency = Regency::create([
        'code' => '32.04',
        'province_code' => '32',
        'name' => 'KABUPATEN BANDUNG',
    ]);

    expect($regency)->toBeInstanceOf(Regency::class);
    expect($regency->province_code)->toBe('32');

    // Test relationship
    $relatedProvince = $regency->province;
    expect($relatedProvince)->toBeInstanceOf(Province::class);
    expect($relatedProvince->code)->toBe('32');
});


it('can test search with sample data', function () {
    Province::create(['code' => '31', 'name' => 'DKI JAKARTA']);
    Regency::create(['code' => '31.71', 'province_code' => '31', 'name' => 'KOTA ADMINISTRASI JAKARTA PUSAT']);

    $idnArea = new IdnArea;
    $results = $idnArea->search('JAKARTA');

    expect($results['provinces'])->toHaveCount(1);
    expect($results['regencies'])->toHaveCount(1);
    expect($results['provinces']->first()->name)->toBe('DKI JAKARTA');
});
