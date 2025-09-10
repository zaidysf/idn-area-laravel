<?php

declare(strict_types=1);

use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

beforeEach(function () {
    $this->province = Province::create([
        'code' => '34',
        'name' => 'DI YOGYAKARTA',
    ]);

    // Create regencies, districts, and villages for testing
    $this->regency1 = Regency::create([
        'code' => '3471',
        'province_code' => '34',
        'name' => 'KULON PROGO',
    ]);

    $this->regency2 = Regency::create([
        'code' => '3401',
        'province_code' => '34',
        'name' => 'BANTUL',
    ]);

    $this->district1 = District::create([
        'code' => '347101',
        'regency_code' => '3471',
        'name' => 'TEMON',
    ]);

    $this->district2 = District::create([
        'code' => '347102',
        'regency_code' => '3471',
        'name' => 'WATES',
    ]);

    $this->district3 = District::create([
        'code' => '340101',
        'regency_code' => '3401',
        'name' => 'SRANDAKAN',
    ]);

    Village::create([
        'code' => '3471011001',
        'district_code' => '347101',
        'name' => 'JANGKARAN',
    ]);

    Village::create([
        'code' => '3471011002',
        'district_code' => '347101',
        'name' => 'KEBONREJO',
    ]);

    Village::create([
        'code' => '3471021001',
        'district_code' => '347102',
        'name' => 'WATES',
    ]);
});

describe('Province Attributes', function () {
    it('gets formatted name attribute', function () {
        expect($this->province->formatted_name)->toBe('34 - DI YOGYAKARTA');
    });

    it('uses code as route key', function () {
        expect($this->province->getRouteKeyName())->toBe('code');
    });

    it('checks if has regencies', function () {
        expect($this->province->hasRegencies())->toBeTrue();

        // Test province without regencies
        $emptyProvince = Province::create([
            'code' => '99',
            'name' => 'TEST PROVINCE',
        ]);
        expect($emptyProvince->hasRegencies())->toBeFalse();
    });

    it('gets total districts count', function () {
        expect($this->province->total_districts)->toBe(3);
    });

    it('gets total villages count', function () {
        expect($this->province->total_villages)->toBe(3);
    });
});

describe('Province Static Methods', function () {
    it('can find by code static method', function () {
        $province = Province::findByCode('34');
        expect($province)->not()->toBeNull()
            ->and($province->name)->toBe('DI YOGYAKARTA');

        $notFound = Province::findByCode('99');
        expect($notFound)->toBeNull();
    });

    it('can search by name static method', function () {
        $results = Province::search('YOGYAKARTA');
        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('DI YOGYAKARTA');

        $noResults = Province::search('NONEXISTENT');
        expect($noResults)->toHaveCount(0);
    });
});

describe('Province Scopes', function () {
    it('can scope search provinces', function () {
        $results = Province::query()->search('YOGYAKARTA')->get();
        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('DI YOGYAKARTA');
    });

    it('can scope with regency count', function () {
        $results = Province::query()->withRegencyCount()->get();
        $province = $results->where('code', '34')->first();

        expect($province->regencies_count)->toBe(2);
    });

    it('can scope with district count', function () {
        $results = Province::query()->withDistrictCount()->get();
        $province = $results->where('code', '34')->first();

        expect($province->districts_count)->toBe(3);
    });
});

describe('Province Relationships', function () {
    it('has regencies relationship', function () {
        $regencies = $this->province->regencies;
        expect($regencies)->toHaveCount(2);

        $names = $regencies->pluck('name')->toArray();
        expect($names)->toContain('KULON PROGO', 'BANTUL');
    });

    it('has districts through regencies relationship', function () {
        $districts = $this->province->districts;
        expect($districts)->toHaveCount(3);

        $names = $districts->pluck('name')->toArray();
        expect($names)->toContain('TEMON', 'WATES', 'SRANDAKAN');
    });

    it('can get villages through query builder', function () {
        $villages = $this->province->villages()->get();
        expect($villages)->toHaveCount(3);

        $names = $villages->pluck('name')->toArray();
        expect($names)->toContain('JANGKARAN', 'KEBONREJO', 'WATES');
    });
});
