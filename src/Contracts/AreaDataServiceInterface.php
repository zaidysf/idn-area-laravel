<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Contracts;

use Illuminate\Support\Collection;

interface AreaDataServiceInterface
{
    public function getAllProvinces(): Collection;
    public function getProvince(string $code): mixed;
    
    public function getAllRegencies(): Collection;
    public function getRegency(string $code): mixed;
    public function getRegenciesByProvince(string $provinceCode): Collection;
    
    public function getAllDistricts(): Collection;
    public function getDistrict(string $code): mixed;
    public function getDistrictsByRegency(string $regencyCode): Collection;
    
    public function getAllVillages(): Collection;
    public function getVillage(string $code): mixed;
    public function getVillagesByDistrict(string $districtCode): Collection;
    
    public function searchByName(string $query, string $type = 'all'): Collection;
}