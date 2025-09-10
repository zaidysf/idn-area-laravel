<?php

declare(strict_types=1);

use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

beforeEach(function () {
    $this->province = Province::create([
        'code' => '31',
        'name' => 'DKI JAKARTA',
    ]);

    $this->regency = Regency::create([
        'code' => '3173',
        'province_code' => '31',
        'name' => 'JAKARTA SELATAN',
    ]);

    $this->district = District::create([
        'code' => '317306',
        'regency_code' => '3173',
        'name' => 'KEBAYORAN BARU',
    ]);

    // Create some villages for testing
    Village::create([
        'code' => '3173061001',
        'district_code' => '317306',
        'name' => 'KRAMAT PELA',
    ]);

    Village::create([
        'code' => '3173061002',
        'district_code' => '317306',
        'name' => 'GANDARIA UTARA',
    ]);
});

describe('District Attributes', function () {
    it('gets formatted name attribute', function () {
        expect($this->district->formatted_name)->toBe('317306 - KEBAYORAN BARU');
    });

    it('gets full formatted name attribute', function () {
        $expected = 'KEBAYORAN BARU, JAKARTA SELATAN, DKI JAKARTA';
        expect($this->district->full_formatted_name)->toBe($expected);
    });

    it('uses code as route key', function () {
        expect($this->district->getRouteKeyName())->toBe('code');
    });
});

describe('District Static Methods', function () {
    it('can find by code static method', function () {
        $district = District::findByCode('317306');
        expect($district)->not()->toBeNull()
            ->and($district->name)->toBe('KEBAYORAN BARU');

        $notFound = District::findByCode('999999');
        expect($notFound)->toBeNull();
    });

    it('can search by name static method', function () {
        $results = District::search('KEBAYORAN');
        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('KEBAYORAN BARU');

        $noResults = District::search('NONEXISTENT');
        expect($noResults)->toHaveCount(0);
    });

    it('can get districts by regency static method', function () {
        $districts = District::byRegency('3173');
        expect($districts)->toHaveCount(1)
            ->and($districts->first()->name)->toBe('KEBAYORAN BARU');
    });
});

describe('District Scopes', function () {
    it('can scope search districts', function () {
        $results = District::query()->search('KEBAYORAN')->get();
        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('KEBAYORAN BARU');
    });

    it('can scope districts in regency', function () {
        $results = District::query()->inRegency('3173')->get();
        expect($results)->toHaveCount(1);
    });

    it('can scope districts in province', function () {
        $results = District::query()->inProvince('31')->get();
        expect($results)->toHaveCount(1);
    });

    it('can scope with village count', function () {
        $results = District::query()->withVillageCount()->get();
        $district = $results->first();
        
        expect($district->villages_count)->toBe(2);
    });
});

describe('District Relationships', function () {
    it('has regency relationship', function () {
        expect($this->district->regency)->not()->toBeNull()
            ->and($this->district->regency->name)->toBe('JAKARTA SELATAN');
    });

    it('has villages relationship', function () {
        $villages = $this->district->villages;
        expect($villages)->toHaveCount(2);
        
        $names = $villages->pluck('name')->toArray();
        expect($names)->toContain('KRAMAT PELA', 'GANDARIA UTARA');
    });
});