<?php

declare(strict_types=1);

namespace zaidysf\IdnArea;

use Illuminate\Support\Collection;

/**
 * Indonesian Area Data Package - BPS Official Data by zaidysf
 * Facade class for IdnAreaManager
 */
final class IdnArea
{
    private IdnAreaManager $manager;

    public function __construct(array $config = [])
    {
        $this->manager = new IdnAreaManager($config);
    }

    /**
     * Get all provinces with optional caching.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function provinces(bool $cached = true): Collection
    {
        return $this->manager->provinces($cached);
    }

    /**
     * Get province by code.
     */
    public function province(string $code, bool $cached = true): mixed
    {
        return $this->manager->province($code, $cached);
    }

    /**
     * Get regencies by province code.
     */
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function regenciesByProvince(string $provinceCode, bool $cached = true): Collection
    {
        return $this->manager->regenciesByProvince($provinceCode, $cached);
    }

    /**
     * Get all regencies.
     */
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function regencies(bool $cached = true): Collection
    {
        return $this->manager->regencies($cached);
    }

    /**
     * Get regency by code.
     */
    public function regency(string $code, bool $cached = true): mixed
    {
        return $this->manager->regency($code, $cached);
    }

    /**
     * Get districts by regency code.
     */
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function districtsByRegency(string $regencyCode, bool $cached = true): Collection
    {
        return $this->manager->districtsByRegency($regencyCode, $cached);
    }

    /**
     * Get all districts.
     */
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function districts(bool $cached = true): Collection
    {
        return $this->manager->districts($cached);
    }

    /**
     * Get district by code.
     */
    public function district(string $code, bool $cached = true): mixed
    {
        return $this->manager->district($code, $cached);
    }

    /**
     * Get villages by district code.
     */
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function villagesByDistrict(string $districtCode, bool $cached = true): Collection
    {
        return $this->manager->villagesByDistrict($districtCode, $cached);
    }

    /**
     * Get all villages.
     */
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function villages(bool $cached = true): Collection
    {
        return $this->manager->villages($cached);
    }

    /**
     * Get village by code.
     */
    public function village(string $code, bool $cached = true): mixed
    {
        return $this->manager->village($code, $cached);
    }

    /**
     * Search across all area types by name.
     *
     * @return Collection<int, array<string, mixed>>|array<string, mixed>
     */
    public function search(string $query, string $type = 'all', bool $cached = true): Collection|array
    {
        $result = $this->manager->search($query, $type, $cached);

        // For specific types like 'provinces', wrap in expected structure
        if ($type !== 'all') {
            return [
                $type => $result,
            ];
        }

        // For type 'all', return structured array but keep collections as collections
        if ($result instanceof Collection && $result->has('provinces')) {
            return [
                'provinces' => $result['provinces'],
                'regencies' => $result['regencies'],
                'districts' => $result['districts'],
                'villages' => $result['villages'],
            ];
        }

        return $result;
    }

    /**
     * Get current operation mode (api or local).
     */
    public function getMode(): string
    {
        return $this->manager->getMode();
    }

    /**
     * Check if BPS API is available (for API mode).
     */
    public function isApiAvailable(): bool
    {
        return $this->manager->isApiAvailable();
    }

    /**
     * Get available periods from BPS API (for API mode).
     */
    public function getPeriods(): array
    {
        return $this->manager->getPeriods();
    }

    /**
     * Get comprehensive statistics.
     */
    public function statistics(bool $cached = true): array
    {
        return $this->manager->statistics($cached);
    }

    /**
     * Clear all package caches.
     */
    public function clearCache(): bool
    {
        return $this->manager->clearCache();
    }

    /**
     * Get area hierarchy (province with all its children).
     */
    public function hierarchy(string $provinceCode, bool $cached = true): array
    {
        if ($cached && config('idn-area.cache.enabled', true)) {
            $cacheKey = "idn_area.hierarchy.{$provinceCode}";

            return \Illuminate\Support\Facades\Cache::remember($cacheKey, config('idn-area.cache.ttl', 3600), function () use ($provinceCode) {
                return $this->buildHierarchy($provinceCode);
            });
        }

        return $this->buildHierarchy($provinceCode);
    }

    /**
     * Get multiple areas by codes.
     *
     * @param  array<int, string>  $codes
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function getMultiple(array $codes, string $type = 'auto'): \Illuminate\Support\Collection
    {
        $results = collect();

        foreach ($codes as $code) {
            switch ($type) {
                case 'province':
                    if ($province = $this->province($code, false)) {
                        $results->push($province);
                    }
                    break;
                case 'regency':
                    if ($regency = $this->regency($code, false)) {
                        $results->push($regency);
                    }
                    break;
                case 'district':
                    if ($district = $this->district($code, false)) {
                        $results->push($district);
                    }
                    break;
                case 'village':
                    if ($village = $this->village($code, false)) {
                        $results->push($village);
                    }
                    break;
                default: // auto-detect
                    $codeLength = strlen($code);
                    if ($codeLength <= 2 && ($province = $this->province($code, false))) {
                        $provinceArray = is_array($province) ? $province : ['code' => $province->code, 'name' => $province->name];
                        $results->push(array_merge($provinceArray, ['type' => 'province']));
                    } elseif ($codeLength <= 4 && ($regency = $this->regency($code, false))) {
                        $regencyArray = is_array($regency) ? $regency : ['code' => $regency->code, 'province_code' => $regency->province_code, 'name' => $regency->name];
                        $results->push(array_merge($regencyArray, ['type' => 'regency']));
                    } elseif ($codeLength <= 7 && ($district = $this->district($code, false))) {
                        $districtArray = is_array($district) ? $district : ['code' => $district->code, 'regency_code' => $district->regency_code, 'name' => $district->name];
                        $results->push(array_merge($districtArray, ['type' => 'district']));
                    } elseif ($codeLength >= 10 && ($village = $this->village($code, false))) {
                        $villageArray = is_array($village) ? $village : ['code' => $village->code, 'district_code' => $village->district_code, 'name' => $village->name];
                        $results->push(array_merge($villageArray, ['type' => 'village']));
                    }
                    break;
            }
        }

        return $results;
    }

    /**
     * Build hierarchy data for a province.
     */
    private function buildHierarchy(string $provinceCode): array
    {
        $province = $this->province($provinceCode, false);
        if (! $province) {
            return [];
        }

        $regencies = $this->regenciesByProvince($provinceCode, false);
        $provinceName = is_array($province) ? $province['name'] : $province->name;
        $provinceCode = is_array($province) ? $province['code'] : $province->code;

        $hierarchy = [
            'code' => $provinceCode,
            'name' => $provinceName,
            'province' => $province,
            'regencies' => [],
        ];

        foreach ($regencies as $regency) {
            $regencyCode = is_array($regency) ? $regency['code'] : $regency->code;
            $districts = $this->districtsByRegency($regencyCode, false);
            $regencyData = [
                'regency' => $regency,
                'districts' => [],
            ];

            foreach ($districts as $district) {
                $districtCode = is_array($district) ? $district['code'] : $district->code;
                $villages = $this->villagesByDistrict($districtCode, false);
                $villagesArray = $villages->toArray();
                $regencyData['districts'][] = [
                    'district' => $district,
                    'villages' => $villagesArray,
                ];
            }

            $hierarchy['regencies'][] = $regencyData;
        }

        return $hierarchy;
    }
}
