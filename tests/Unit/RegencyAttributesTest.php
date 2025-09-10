<?php

declare(strict_types=1);

use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

beforeEach(function () {
    $this->province = Province::create([
        'code' => '33',
        'name' => 'JAWA TENGAH',
    ]);

    $this->regency = Regency::create([
        'code' => '3374',
        'province_code' => '33',
        'name' => 'SEMARANG',
    ]);

    // Create districts and villages for testing
    $this->district1 = District::create([
        'code' => '337401',
        'regency_code' => '3374',
        'name' => 'BANYUMANIK',
    ]);

    $this->district2 = District::create([
        'code' => '337402',
        'regency_code' => '3374',
        'name' => 'GAJAH MUNGKUR',
    ]);

    Village::create([
        'code' => '3374011001',
        'district_code' => '337401',
        'name' => 'SRONDOL WETAN',
    ]);

    Village::create([
        'code' => '3374011002',
        'district_code' => '337401',
        'name' => 'SRONDOL KULON',
    ]);

    Village::create([
        'code' => '3374021001',
        'district_code' => '337402',
        'name' => 'SAMPANGAN',
    ]);
});

describe('Regency Attributes', function () {
    it('gets formatted name attribute', function () {
        expect($this->regency->formatted_name)->toBe('3374 - SEMARANG');
    });

    it('gets full formatted name attribute', function () {
        $expected = 'SEMARANG, JAWA TENGAH';
        expect($this->regency->full_formatted_name)->toBe($expected);
    });

    it('uses code as route key', function () {
        expect($this->regency->getRouteKeyName())->toBe('code');
    });
});

describe('Regency Static Methods', function () {
    it('can find by code static method', function () {
        $regency = Regency::findByCode('3374');
        expect($regency)->not()->toBeNull()
            ->and($regency->name)->toBe('SEMARANG');

        $notFound = Regency::findByCode('9999');
        expect($notFound)->toBeNull();
    });

    it('can search by name static method', function () {
        $results = Regency::search('SEMARANG');
        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('SEMARANG');

        $noResults = Regency::search('NONEXISTENT');
        expect($noResults)->toHaveCount(0);
    });

    it('can get regencies by province static method', function () {
        $regencies = Regency::byProvince('33');
        expect($regencies)->toHaveCount(1)
            ->and($regencies->first()->name)->toBe('SEMARANG');
    });
});

describe('Regency Scopes', function () {
    it('can scope search regencies', function () {
        $results = Regency::query()->search('SEMARANG')->get();
        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('SEMARANG');
    });

    it('can scope regencies in province', function () {
        $results = Regency::query()->inProvince('33')->get();
        expect($results)->toHaveCount(1);
    });

    it('can scope with district count', function () {
        $results = Regency::query()->withDistrictCount()->get();
        $regency = $results->first();

        expect($regency->districts_count)->toBe(2);
    });

    it('can scope with village count', function () {
        $results = Regency::query()->withVillageCount()->get();
        $regency = $results->first();

        expect($regency->villages_count)->toBe(3);
    });
});

describe('Regency Relationships', function () {
    it('has province relationship', function () {
        expect($this->regency->province)->not()->toBeNull()
            ->and($this->regency->province->name)->toBe('JAWA TENGAH');
    });

    it('has districts relationship', function () {
        $districts = $this->regency->districts;
        expect($districts)->toHaveCount(2);

        $names = $districts->pluck('name')->toArray();
        expect($names)->toContain('BANYUMANIK', 'GAJAH MUNGKUR');
    });

    it('has villages through districts relationship', function () {
        $villages = $this->regency->villages;
        expect($villages)->toHaveCount(3);

        $names = $villages->pluck('name')->toArray();
        expect($names)->toContain('SRONDOL WETAN', 'SRONDOL KULON', 'SAMPANGAN');
    });
});
