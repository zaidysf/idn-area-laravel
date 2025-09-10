<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Contracts;

use Illuminate\Support\Collection;

interface AreaDataServiceInterface
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAllProvinces(): Collection;

    /**
     * @return array<string, mixed>|null
     */
    public function getProvince(string $code): mixed;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAllRegencies(): Collection;

    /**
     * @return array<string, mixed>|null
     */
    public function getRegency(string $code): mixed;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getRegenciesByProvince(string $provinceCode): Collection;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAllDistricts(): Collection;

    /**
     * @return array<string, mixed>|null
     */
    public function getDistrict(string $code): mixed;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getDistrictsByRegency(string $regencyCode): Collection;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAllVillages(): Collection;

    /**
     * @return array<string, mixed>|null
     */
    public function getVillage(string $code): mixed;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getVillagesByDistrict(string $districtCode): Collection;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchByName(string $query, string $type = 'all'): Collection;
}
