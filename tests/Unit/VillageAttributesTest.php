<?php

declare(strict_types=1);

use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

beforeEach(function () {
    $this->province = Province::create([
        'code' => '32',
        'name' => 'JAWA BARAT',
    ]);

    $this->regency = Regency::create([
        'code' => '3204',
        'province_code' => '32',
        'name' => 'KAB. BANDUNG',
    ]);

    $this->district = District::create([
        'code' => '320401',
        'regency_code' => '3204',
        'name' => 'ARJASARI',
    ]);

    // Village with even last digit (kelurahan)
    $this->kelurahan = Village::create([
        'code' => '3204011002',
        'district_code' => '320401',
        'name' => 'ARJASARI',
    ]);

    // Village with odd last digit (desa)
    $this->desa = Village::create([
        'code' => '3204011001',
        'district_code' => '320401',
        'name' => 'BAROS',
    ]);
});

describe('Village Attributes', function () {
    it('gets formatted name attribute', function () {
        expect($this->kelurahan->formatted_name)->toBe('3204011002 - ARJASARI');
        expect($this->desa->formatted_name)->toBe('3204011001 - BAROS');
    });

    it('gets full formatted name attribute', function () {
        $expected = 'ARJASARI, ARJASARI, KAB. BANDUNG, JAWA BARAT';
        expect($this->kelurahan->full_formatted_name)->toBe($expected);
    });

    it('gets regency code attribute', function () {
        expect($this->kelurahan->regency_code)->toBe('3204');
        expect($this->desa->regency_code)->toBe('3204');
    });

    it('gets province code attribute', function () {
        expect($this->kelurahan->province_code)->toBe('32');
        expect($this->desa->province_code)->toBe('32');
    });

    it('gets district name attribute', function () {
        expect($this->kelurahan->district_name)->toBe('ARJASARI');
        expect($this->desa->district_name)->toBe('ARJASARI');
    });

    it('gets regency name attribute', function () {
        expect($this->kelurahan->regency_name)->toBe('KAB. BANDUNG');
        expect($this->desa->regency_name)->toBe('KAB. BANDUNG');
    });

    it('gets province name attribute', function () {
        expect($this->kelurahan->province_name)->toBe('JAWA BARAT');
        expect($this->desa->province_name)->toBe('JAWA BARAT');
    });

    it('determines village type from code', function () {
        expect($this->kelurahan->type)->toBe('kelurahan'); // even last digit
        expect($this->desa->type)->toBe('desa'); // odd last digit
    });

    it('checks if is kelurahan', function () {
        expect($this->kelurahan->is_kelurahan)->toBeTrue();
        expect($this->desa->is_kelurahan)->toBeFalse();
    });

    it('checks if is desa', function () {
        expect($this->kelurahan->is_desa)->toBeFalse();
        expect($this->desa->is_desa)->toBeTrue();
    });

    it('gets village hierarchy attribute', function () {
        $hierarchy = $this->kelurahan->hierarchy;

        expect($hierarchy)->toBeArray()
            ->and($hierarchy)->toHaveKeys(['village', 'district', 'regency', 'province'])
            ->and($hierarchy['village'])->toHaveKeys(['code', 'name', 'type'])
            ->and($hierarchy['village']['code'])->toBe('3204011002')
            ->and($hierarchy['village']['name'])->toBe('ARJASARI')
            ->and($hierarchy['village']['type'])->toBe('kelurahan')
            ->and($hierarchy['district']['code'])->toBe('320401')
            ->and($hierarchy['district']['name'])->toBe('ARJASARI')
            ->and($hierarchy['regency']['code'])->toBe('3204')
            ->and($hierarchy['regency']['name'])->toBe('KAB. BANDUNG')
            ->and($hierarchy['province']['code'])->toBe('32')
            ->and($hierarchy['province']['name'])->toBe('JAWA BARAT');
    });
});

describe('Village Static Methods', function () {
    it('can find by code static method', function () {
        $village = Village::findByCode('3204011002');
        expect($village)->not()->toBeNull()
            ->and($village->name)->toBe('ARJASARI');

        $notFound = Village::findByCode('9999999999');
        expect($notFound)->toBeNull();
    });

    it('can search by name static method', function () {
        $results = Village::search('ARJASARI');
        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('ARJASARI');

        $noResults = Village::search('NONEXISTENT');
        expect($noResults)->toHaveCount(0);
    });

    it('can get villages by district static method', function () {
        $villages = Village::byDistrict('320401');
        expect($villages)->toHaveCount(2);

        $names = $villages->pluck('name')->toArray();
        expect($names)->toContain('ARJASARI', 'BAROS');
    });
});

describe('Village Scopes', function () {
    it('can scope search villages', function () {
        $results = Village::query()->search('BAROS')->get();
        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('BAROS');
    });

    it('can scope villages in district', function () {
        $results = Village::query()->inDistrict('320401')->get();
        expect($results)->toHaveCount(2);
    });

    it('can scope villages in regency', function () {
        $results = Village::query()->inRegency('3204')->get();
        expect($results)->toHaveCount(2);
    });

    it('can scope villages in province', function () {
        $results = Village::query()->inProvince('32')->get();
        expect($results)->toHaveCount(2);
    });
});

describe('Village Relationships', function () {
    it('has regency through district relationship', function () {
        $regency = $this->kelurahan->regency;
        expect($regency)->not()->toBeNull()
            ->and($regency->name)->toBe('KAB. BANDUNG');
    });

    it('gets province method', function () {
        $province = $this->kelurahan->province();
        expect($province)->not()->toBeNull()
            ->and($province->name)->toBe('JAWA BARAT');
    });
});
