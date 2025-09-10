<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Services;

use Illuminate\Support\Collection;
use zaidysf\IdnArea\Contracts\AreaDataServiceInterface;
use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

class LocalDataService implements AreaDataServiceInterface
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAllProvinces(): Collection
    {
        /** @phpstan-ignore-next-line */
        return Province::orderBy('name')->get()->map(function ($province) {
            return $province->toArray();
        })->values();
    }

    public function getProvince(string $code): ?\zaidysf\IdnArea\Models\Province
    {
        return Province::find($code);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getRegenciesByProvince(string $provinceCode): Collection
    {
        /** @phpstan-ignore-next-line */
        return Regency::where('province_code', $provinceCode)
            ->orderBy('name')
            ->get()
            ->map(function ($regency) {
                return $regency->toArray();
            })->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAllRegencies(): Collection
    {
        /** @phpstan-ignore-next-line */
        return Regency::orderBy('name')->get()->map(function ($regency) {
            return $regency->toArray();
        })->values();
    }

    public function getRegency(string $code): ?\zaidysf\IdnArea\Models\Regency
    {
        return Regency::find($code);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getDistrictsByRegency(string $regencyCode): Collection
    {
        /** @phpstan-ignore-next-line */
        return District::where('regency_code', $regencyCode)
            ->orderBy('name')
            ->get()
            ->map(function ($district) {
                return $district->toArray();
            })->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAllDistricts(): Collection
    {
        /** @phpstan-ignore-next-line */
        return District::orderBy('name')->get()->map(function ($district) {
            return $district->toArray();
        })->values();
    }

    public function getDistrict(string $code): ?\zaidysf\IdnArea\Models\District
    {
        return District::find($code);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getVillagesByDistrict(string $districtCode): Collection
    {
        /** @phpstan-ignore-next-line */
        return Village::where('district_code', $districtCode)
            ->orderBy('name')
            ->get()
            ->map(function ($village) {
                return $village->toArray();
            })->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAllVillages(): Collection
    {
        /** @phpstan-ignore-next-line */
        return Village::orderBy('name')->get()->map(function ($village) {
            return $village->toArray();
        })->values();
    }

    public function getVillage(string $code): ?\zaidysf\IdnArea\Models\Village
    {
        return Village::find($code);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchByName(string $query, string $type = 'all'): Collection
    {
        switch ($type) {
            case 'provinces':
                /** @phpstan-ignore-next-line */
                return Province::search($query);

            case 'regencies':
                /** @phpstan-ignore-next-line */
                return Regency::search($query);

            case 'districts':
                /** @phpstan-ignore-next-line */
                return District::search($query);

            case 'villages':
                /** @phpstan-ignore-next-line */
                return Village::search($query);

            default: // 'all' - merge all results into flat collection
                $provinces = Province::search($query);
                $regencies = Regency::search($query);
                $districts = District::search($query);
                $villages = Village::search($query);

                // Flatten all results into a single collection
                return collect()
                    ->merge($provinces)
                    ->merge($regencies)
                    ->merge($districts)
                    ->merge($villages)
                    ->values();
        }
    }

    // Additional methods for testing compatibility

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getProvinces(?int $limit = null, ?int $offset = null): array
    {
        $query = Province::orderBy('name');

        if ($limit) {
            $query->limit($limit);
        }

        if ($offset) {
            $query->offset($offset);
        }

        return $query->get()->map(function ($province) {
            return [
                'code' => $province->code,
                'name' => $province->name,
            ];
        })->toArray();
    }

    public function getRegencies(string $provinceCode): array
    {
        return $this->getRegenciesByProvince($provinceCode)->toArray();
    }

    public function getDistricts(string $regencyCode): array
    {
        return $this->getDistrictsByRegency($regencyCode)->toArray();
    }

    public function getVillages(string $districtCode): array
    {
        return $this->getVillagesByDistrict($districtCode)->toArray();
    }

    public function getProvinceByCode(string $code): ?array
    {
        $province = $this->getProvince($code);

        return $province ? ['code' => $province->code, 'name' => $province->name] : null;
    }

    public function getRegencyByCode(string $code): ?array
    {
        $regency = $this->getRegency($code);

        return $regency ? ['code' => $regency->code, 'province_code' => $regency->province_code, 'name' => $regency->name] : null;
    }

    public function getDistrictByCode(string $code): ?array
    {
        $district = $this->getDistrict($code);

        return $district ? ['code' => $district->code, 'regency_code' => $district->regency_code, 'name' => $district->name] : null;
    }

    public function getVillageByCode(string $code): ?array
    {
        $village = $this->getVillage($code);

        return $village ? ['code' => $village->code, 'district_code' => $village->district_code, 'name' => $village->name] : null;
    }

    public function searchProvinces(string $term): array
    {
        return Province::search($term)->map(function ($item) {
            return [
                'code' => $item->code,
                'name' => $item->name,
            ];
        })->toArray();
    }

    public function searchRegencies(string $term): array
    {
        return Regency::search($term)->map(function ($item) {
            return [
                'code' => $item->code,
                'province_code' => $item->province_code,
                'name' => $item->name,
            ];
        })->toArray();
    }

    public function searchDistricts(string $term): array
    {
        return District::search($term)->map(function ($item) {
            return [
                'code' => $item->code,
                'regency_code' => $item->regency_code,
                'name' => $item->name,
            ];
        })->toArray();
    }

    public function searchVillages(string $term): array
    {
        return Village::search($term)->map(function ($item) {
            return [
                'code' => $item->code,
                'district_code' => $item->district_code,
                'name' => $item->name,
            ];
        })->toArray();
    }

    public function countProvinces(): int
    {
        return Province::count();
    }

    public function countRegencies(): int
    {
        return Regency::count();
    }

    public function countDistricts(): int
    {
        return District::count();
    }

    public function countVillages(): int
    {
        return Village::count();
    }
}
