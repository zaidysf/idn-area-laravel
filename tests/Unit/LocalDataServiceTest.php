<?php

declare(strict_types=1);

use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;
use zaidysf\IdnArea\Services\LocalDataService;

beforeEach(function () {
    $this->service = new LocalDataService;

    // Create test data
    $this->province = Province::create([
        'code' => '11',
        'name' => 'ACEH',
    ]);

    $this->regency = Regency::create([
        'code' => '1101',
        'province_code' => '11',
        'name' => 'KAB. SIMEULUE',
    ]);

    $this->district = District::create([
        'code' => '110101',
        'regency_code' => '1101',
        'name' => 'TEUPAH SELATAN',
    ]);

    $this->village = Village::create([
        'code' => '1101011001',
        'district_code' => '110101',
        'name' => 'LUGU',
    ]);
});

it('can get all provinces', function () {
    $result = $this->service->getProvinces();

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and($result[0])->toHaveKeys(['code', 'name'])
        ->and($result[0]['name'])->toBe('ACEH');
});

it('can get regencies by province code', function () {
    $result = $this->service->getRegencies('11');

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('KAB. SIMEULUE');
});

it('can get districts by regency code', function () {
    $result = $this->service->getDistricts('1101');

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('TEUPAH SELATAN');
});

it('can get villages by district code', function () {
    $result = $this->service->getVillages('110101');

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('LUGU');
});

it('returns empty array for non-existent province', function () {
    $result = $this->service->getRegencies('99');

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(0);
});

it('can get province by code', function () {
    $result = $this->service->getProvinceByCode('11');

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('ACEH');
});

it('can get regency by code', function () {
    $result = $this->service->getRegencyByCode('1101');

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('KAB. SIMEULUE');
});

it('can get district by code', function () {
    $result = $this->service->getDistrictByCode('110101');

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('TEUPAH SELATAN');
});

it('can get village by code', function () {
    $result = $this->service->getVillageByCode('1101011001');

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('LUGU');
});

it('returns null for non-existent codes', function () {
    expect($this->service->getProvinceByCode('99'))->toBeNull();
    expect($this->service->getRegencyByCode('9999'))->toBeNull();
    expect($this->service->getDistrictByCode('999999'))->toBeNull();
    expect($this->service->getVillageByCode('9999999999'))->toBeNull();
});

it('can search provinces by name', function () {
    Province::create(['code' => '12', 'name' => 'SUMATERA UTARA']);

    $result = $this->service->searchProvinces('ACEH');

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('ACEH');
});

it('can search regencies by name', function () {
    Regency::create([
        'code' => '1102',
        'province_code' => '11',
        'name' => 'KAB. ACEH SINGKIL',
    ]);

    $result = $this->service->searchRegencies('SIMEULUE');

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('KAB. SIMEULUE');
});

it('can search districts by name', function () {
    District::create([
        'code' => '110102',
        'regency_code' => '1101',
        'name' => 'SIMEULUE TENGAH',
    ]);

    $result = $this->service->searchDistricts('SELATAN');

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('TEUPAH SELATAN');
});

it('can search villages by name', function () {
    Village::create([
        'code' => '1101011002',
        'district_code' => '110101',
        'name' => 'LABUHAN BAJAU',
    ]);

    $result = $this->service->searchVillages('LUGU');

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('LUGU');
});

it('handles case insensitive search', function () {
    $result = $this->service->searchProvinces('aceh');

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('ACEH');
});

it('handles partial name search', function () {
    $result = $this->service->searchProvinces('ACE');

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('ACEH');
});

it('returns paginated results when requested', function () {
    // Create additional test data
    for ($i = 2; $i <= 10; $i++) {
        Province::create([
            'code' => (string) $i,
            'name' => "PROVINCE {$i}",
        ]);
    }

    $result = $this->service->getProvinces(5, 1); // 5 per page, page 1

    expect($result)->toHaveCount(5);
});

it('can count total records', function () {
    expect($this->service->countProvinces())->toBe(1);
    expect($this->service->countRegencies())->toBe(1);
    expect($this->service->countDistricts())->toBe(1);
    expect($this->service->countVillages())->toBe(1);
});
