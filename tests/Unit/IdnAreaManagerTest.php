<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use zaidysf\IdnArea\IdnAreaManager;
use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

beforeEach(function () {
    // Create test data
    Province::create(['code' => '11', 'name' => 'ACEH']);
    Regency::create(['code' => '1101', 'province_code' => '11', 'name' => 'KAB. SIMEULUE']);
    District::create(['code' => '110101', 'regency_code' => '1101', 'name' => 'TEUPAH SELATAN']);
    Village::create(['code' => '1101011001', 'district_code' => '110101', 'name' => 'LUGU']);
});

it('uses local service when in local mode', function () {
    Config::set('idn-area.mode', 'local');

    $manager = new IdnAreaManager;
    $result = $manager->getProvinces();

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('ACEH');
});

it('uses API service when in API mode', function () {
    Config::set('idn-area.mode', 'api');
    Config::set('idn-area.datatoko_api.access_key', 'test_access_key');
    Config::set('idn-area.datatoko_api.secret_key', 'test_secret_key');
    Config::set('idn-area.datatoko_api.base_url', 'https://data.toko.center');

    Http::fake([
        'data.toko.center/api/auth/login' => Http::response([
            'data' => ['token' => 'test_token'],
        ], 200),
        'data.toko.center/api/indonesia/provinces' => Http::response([
            'data' => [['code' => '11', 'name' => 'ACEH']],
        ], 200),
    ]);

    $manager = new IdnAreaManager;
    $result = $manager->getProvinces();

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1);
});

it('defaults to local mode when mode is not configured', function () {
    Config::set('idn-area.mode', null);

    $manager = new IdnAreaManager;
    $result = $manager->getProvinces();

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('ACEH');
});

it('can get regencies by province code in local mode', function () {
    Config::set('idn-area.mode', 'local');

    $manager = new IdnAreaManager;
    $result = $manager->getRegencies('11');

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('KAB. SIMEULUE');
});

it('can get regencies by province code in API mode', function () {
    Config::set('idn-area.mode', 'api');
    Config::set('idn-area.datatoko_api.access_key', 'test_access_key');
    Config::set('idn-area.datatoko_api.secret_key', 'test_secret_key');
    Config::set('idn-area.datatoko_api.base_url', 'https://data.toko.center');

    Http::fake([
        'data.toko.center/api/auth/login' => Http::response([
            'data' => ['token' => 'test_token'],
        ], 200),
        'data.toko.center/api/indonesia/provinces/11/regencies' => Http::response([
            'data' => [['code' => '1101', 'name' => 'KAB. SIMEULUE', 'province_code' => '11']],
        ], 200),
    ]);

    $manager = new IdnAreaManager;
    $result = $manager->getRegencies('11');

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1);
});

it('can get districts by regency code in both modes', function () {
    // Test local mode
    Config::set('idn-area.mode', 'local');
    $manager = new IdnAreaManager;
    $localResult = $manager->getDistricts('1101');

    expect($localResult)->toBeArray()
        ->and($localResult)->toHaveCount(1)
        ->and($localResult[0]['name'])->toBe('TEUPAH SELATAN');

    // Test API mode
    Config::set('idn-area.mode', 'api');
    Config::set('idn-area.datatoko_api.access_key', 'test_access_key');
    Config::set('idn-area.datatoko_api.secret_key', 'test_secret_key');
    Config::set('idn-area.datatoko_api.base_url', 'https://data.toko.center');

    Http::fake([
        'data.toko.center/api/auth/login' => Http::response([
            'data' => ['token' => 'test_token'],
        ], 200),
        'data.toko.center/api/indonesia/regencies/1101/districts' => Http::response([
            'data' => [['code' => '110101', 'name' => 'TEUPAH SELATAN', 'regency_code' => '1101']],
        ], 200),
    ]);

    $apiResult = $manager->getDistricts('1101');
    expect($apiResult)->toBeArray()
        ->and($apiResult)->toHaveCount(1);
});

it('can get villages by district code in both modes', function () {
    // Test local mode
    Config::set('idn-area.mode', 'local');
    $manager = new IdnAreaManager;
    $localResult = $manager->getVillages('110101');

    expect($localResult)->toBeArray()
        ->and($localResult)->toHaveCount(1)
        ->and($localResult[0]['name'])->toBe('LUGU');

    // Test API mode
    Config::set('idn-area.mode', 'api');
    Config::set('idn-area.datatoko_api.access_key', 'test_access_key');
    Config::set('idn-area.datatoko_api.secret_key', 'test_secret_key');
    Config::set('idn-area.datatoko_api.base_url', 'https://data.toko.center');

    Http::fake([
        'data.toko.center/api/auth/login' => Http::response([
            'data' => ['token' => 'test_token'],
        ], 200),
        'data.toko.center/api/indonesia/districts/110101/villages' => Http::response([
            'data' => [['code' => '1101011001', 'name' => 'LUGU', 'district_code' => '110101']],
        ], 200),
    ]);

    $apiResult = $manager->getVillages('110101');
    expect($apiResult)->toBeArray()
        ->and($apiResult)->toHaveCount(1);
});

it('can find by code in local mode', function () {
    Config::set('idn-area.mode', 'local');

    $manager = new IdnAreaManager;

    expect($manager->findProvince('11'))->not->toBeNull()
        ->and($manager->findProvince('11')['name'])->toBe('ACEH');

    expect($manager->findRegency('1101'))->not->toBeNull()
        ->and($manager->findRegency('1101')['name'])->toBe('KAB. SIMEULUE');

    expect($manager->findDistrict('110101'))->not->toBeNull()
        ->and($manager->findDistrict('110101')['name'])->toBe('TEUPAH SELATAN');

    expect($manager->findVillage('1101011001'))->not->toBeNull()
        ->and($manager->findVillage('1101011001')['name'])->toBe('LUGU');
});

it('can search by name in local mode', function () {
    Config::set('idn-area.mode', 'local');

    $manager = new IdnAreaManager;

    $provinces = $manager->searchProvinces('ACEH');
    expect($provinces)->toHaveCount(1)
        ->and($provinces[0]['name'])->toBe('ACEH');

    $regencies = $manager->searchRegencies('SIMEULUE');
    expect($regencies)->toHaveCount(1)
        ->and($regencies[0]['name'])->toBe('KAB. SIMEULUE');

    $districts = $manager->searchDistricts('SELATAN');
    expect($districts)->toHaveCount(1)
        ->and($districts[0]['name'])->toBe('TEUPAH SELATAN');

    $villages = $manager->searchVillages('LUGU');
    expect($villages)->toHaveCount(1)
        ->and($villages[0]['name'])->toBe('LUGU');
});

it('handles configuration changes dynamically', function () {
    $manager = new IdnAreaManager(['mode' => 'local']);
    $localResult = $manager->getProvinces();

    expect($localResult)->toHaveCount(1)
        ->and($localResult[0]['name'])->toBe('ACEH');

    Http::fake([
        'data.toko.center/api/auth/login' => Http::response([
            'data' => ['token' => 'test_token'],
        ], 200),
        'data.toko.center/api/indonesia/provinces' => Http::response([
            'data' => [['code' => '11', 'name' => 'ACEH (API)']],
        ], 200),
    ]);

    $manager = new IdnAreaManager([
        'mode' => 'api',
        'datatoko_api' => [
            'access_key' => 'test_access_key',
            'secret_key' => 'test_secret_key',
            'base_url' => 'https://data.toko.center',
        ],
    ]);
    $apiResult = $manager->getProvinces();

    expect($apiResult)->toHaveCount(1);
});

it('maintains service instances correctly', function () {
    Config::set('idn-area.mode', 'local');

    $manager = new IdnAreaManager;
    $service1 = $manager->getCurrentService();
    $service2 = $manager->getCurrentService();

    expect($service1)->toBe($service2);
});

it('can get current mode', function () {
    Config::set('idn-area.mode', 'local');
    $manager = new IdnAreaManager;

    expect($manager->getCurrentMode())->toBe('local');

    Config::set('idn-area.mode', 'api');
    Config::set('idn-area.datatoko_api.access_key', 'test_access_key');
    Config::set('idn-area.datatoko_api.secret_key', 'test_secret_key');
    Config::set('idn-area.datatoko_api.base_url', 'https://data.toko.center');
    $manager = new IdnAreaManager;

    expect($manager->getCurrentMode())->toBe('api');
});

it('validates service availability', function () {
    Config::set('idn-area.mode', 'local');
    $manager = new IdnAreaManager;

    expect($manager->isServiceAvailable())->toBeTrue();

    Config::set('idn-area.mode', 'api');
    Config::set('idn-area.datatoko_api.access_key', 'test_access_key');
    Config::set('idn-area.datatoko_api.secret_key', 'test_secret_key');
    Config::set('idn-area.datatoko_api.base_url', 'https://data.toko.center');

    Http::fake([
        'data.toko.center/api/auth/login' => Http::response([], 500),
    ]);

    $manager = new IdnAreaManager;
    expect($manager->isServiceAvailable())->toBeFalse();
});
