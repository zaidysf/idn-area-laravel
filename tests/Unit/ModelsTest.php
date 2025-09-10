<?php

declare(strict_types=1);

use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

beforeEach(function () {
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

describe('Province Model', function () {
    it('can create a province', function () {
        $province = Province::create([
            'code' => '12',
            'name' => 'SUMATERA UTARA',
        ]);

        expect($province->code)->toBe('12')
            ->and($province->name)->toBe('SUMATERA UTARA');
    });

    it('has regencies relationship', function () {
        $regencies = $this->province->regencies;

        expect($regencies)->toHaveCount(1)
            ->and($regencies->first()->name)->toBe('KAB. SIMEULUE');
    });

    it('can search by name', function () {
        Province::create(['code' => '12', 'name' => 'SUMATERA UTARA']);

        $results = Province::search('ACEH');

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('ACEH');
    });

    it('can find by code', function () {
        $province = Province::findByCode('11');

        expect($province)->not->toBeNull()
            ->and($province->name)->toBe('ACEH');
    });

    it('returns null for non-existent code', function () {
        $province = Province::findByCode('99');

        expect($province)->toBeNull();
    });

    it('uses correct table name', function () {
        expect((new Province)->getTable())->toBe('idn_provinces');
    });

    it('has fillable attributes', function () {
        $fillable = (new Province)->getFillable();

        expect($fillable)->toContain('code')
            ->and($fillable)->toContain('name');
    });
});

describe('Regency Model', function () {
    it('can create a regency', function () {
        $regency = Regency::create([
            'code' => '1102',
            'province_code' => '11',
            'name' => 'KAB. ACEH SINGKIL',
        ]);

        expect($regency->code)->toBe('1102')
            ->and($regency->province_code)->toBe('11')
            ->and($regency->name)->toBe('KAB. ACEH SINGKIL');
    });

    it('has province relationship', function () {
        $province = $this->regency->province;

        expect($province)->not->toBeNull()
            ->and($province->name)->toBe('ACEH');
    });

    it('has districts relationship', function () {
        $districts = $this->regency->districts;

        expect($districts)->toHaveCount(1)
            ->and($districts->first()->name)->toBe('TEUPAH SELATAN');
    });

    it('can find by province', function () {
        $regencies = Regency::byProvince('11');

        expect($regencies)->toHaveCount(1)
            ->and($regencies->first()->name)->toBe('KAB. SIMEULUE');
    });

    it('uses correct table name', function () {
        expect((new Regency)->getTable())->toBe('idn_regencies');
    });
});

describe('District Model', function () {
    it('can create a district', function () {
        $district = District::create([
            'code' => '110102',
            'regency_code' => '1101',
            'name' => 'SIMEULUE TENGAH',
        ]);

        expect($district->code)->toBe('110102')
            ->and($district->regency_code)->toBe('1101')
            ->and($district->name)->toBe('SIMEULUE TENGAH');
    });

    it('has regency relationship', function () {
        $regency = $this->district->regency;

        expect($regency)->not->toBeNull()
            ->and($regency->name)->toBe('KAB. SIMEULUE');
    });

    it('has villages relationship', function () {
        $villages = $this->district->villages;

        expect($villages)->toHaveCount(1)
            ->and($villages->first()->name)->toBe('LUGU');
    });

    it('can find by regency', function () {
        $districts = District::byRegency('1101');

        expect($districts)->toHaveCount(1)
            ->and($districts->first()->name)->toBe('TEUPAH SELATAN');
    });

    it('uses correct table name', function () {
        expect((new District)->getTable())->toBe('idn_districts');
    });
});

describe('Village Model', function () {
    it('can create a village', function () {
        $village = Village::create([
            'code' => '1101011002',
            'district_code' => '110101',
            'name' => 'LABUHAN BAJAU',
        ]);

        expect($village->code)->toBe('1101011002')
            ->and($village->district_code)->toBe('110101')
            ->and($village->name)->toBe('LABUHAN BAJAU');
    });

    it('has district relationship', function () {
        $district = $this->village->district;

        expect($district)->not->toBeNull()
            ->and($district->name)->toBe('TEUPAH SELATAN');
    });

    it('can find by district', function () {
        $villages = Village::byDistrict('110101');

        expect($villages)->toHaveCount(1)
            ->and($villages->first()->name)->toBe('LUGU');
    });

    it('uses correct table name', function () {
        expect((new Village)->getTable())->toBe('idn_villages');
    });
});

describe('Model Search Functionality', function () {
    it('performs case insensitive search', function () {
        $results = Province::search('aceh');

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('ACEH');
    });

    it('performs partial name search', function () {
        $results = Province::search('ACE');

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('ACEH');
    });

    it('returns empty results for non-matching search', function () {
        $results = Province::search('NONEXISTENT');

        expect($results)->toHaveCount(0);
    });
});

describe('Model Validation', function () {
    it('requires code field', function () {
        expect(fn () => Province::create(['name' => 'TEST']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('requires name field', function () {
        expect(fn () => Province::create(['code' => '99']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('enforces unique code constraint', function () {
        Province::create(['code' => '13', 'name' => 'TEST 1']);

        expect(fn () => Province::create(['code' => '13', 'name' => 'TEST 2']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });
});

describe('Model Relationships Cascade', function () {
    it('maintains referential integrity', function () {
        // Create a complete hierarchy
        $province = Province::create(['code' => '99', 'name' => 'TEST PROVINCE']);
        $regency = Regency::create(['code' => '9901', 'province_code' => '99', 'name' => 'TEST REGENCY']);
        $district = District::create(['code' => '990101', 'regency_code' => '9901', 'name' => 'TEST DISTRICT']);
        $village = Village::create(['code' => '9901011001', 'district_code' => '990101', 'name' => 'TEST VILLAGE']);

        // Verify relationships exist
        expect($province->regencies)->toHaveCount(1);
        expect($regency->districts)->toHaveCount(1);
        expect($district->villages)->toHaveCount(1);

        // Verify reverse relationships
        expect($village->district->regency->province->name)->toBe('TEST PROVINCE');
    });
});
