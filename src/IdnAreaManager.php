<?php

declare(strict_types=1);

namespace zaidysf\IdnArea;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use zaidysf\IdnArea\Contracts\AreaDataServiceInterface;
use zaidysf\IdnArea\Services\DataTokoApiService;
use zaidysf\IdnArea\Services\LocalDataService;

/**
 * Indonesian Area Data Manager - Dual mode system by zaidysf
 * Supports both BPS API mode and Local data mode
 */
final class IdnAreaManager
{
    private AreaDataServiceInterface $dataService;

    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->dataService = $this->createDataService();
    }

    /**
     * Get all provinces with optional caching.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function provinces(bool $cached = true): Collection
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = 'idn_area.provinces';

            return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
                return $this->dataService->getAllProvinces();
            });
        }

        return $this->dataService->getAllProvinces();
    }

    /**
     * Get province by code.
     */
    public function province(string $code, bool $cached = true): mixed
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = "idn_area.province.{$code}";

            return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($code) {
                return $this->dataService->getProvince($code);
            });
        }

        return $this->dataService->getProvince($code);
    }

    /**
     * Get regencies by province code.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function regenciesByProvince(string $provinceCode, bool $cached = true): Collection
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = "idn_area.regencies.province.{$provinceCode}";

            return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($provinceCode) {
                return $this->dataService->getRegenciesByProvince($provinceCode);
            });
        }

        return $this->dataService->getRegenciesByProvince($provinceCode);
    }

    /**
     * Get all regencies.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function regencies(bool $cached = true): Collection
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = 'idn_area.regencies';

            return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
                return $this->dataService->getAllRegencies();
            });
        }

        return $this->dataService->getAllRegencies();
    }

    /**
     * Get regency by code.
     */
    public function regency(string $code, bool $cached = true): mixed
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = "idn_area.regency.{$code}";

            return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($code) {
                return $this->dataService->getRegency($code);
            });
        }

        return $this->dataService->getRegency($code);
    }

    /**
     * Get districts by regency code.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function districtsByRegency(string $regencyCode, bool $cached = true): Collection
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = "idn_area.districts.regency.{$regencyCode}";

            return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($regencyCode) {
                return $this->dataService->getDistrictsByRegency($regencyCode);
            });
        }

        return $this->dataService->getDistrictsByRegency($regencyCode);
    }

    /**
     * Get all districts.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function districts(bool $cached = true): Collection
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = 'idn_area.districts';

            return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
                return $this->dataService->getAllDistricts();
            });
        }

        return $this->dataService->getAllDistricts();
    }

    /**
     * Get district by code.
     */
    public function district(string $code, bool $cached = true): mixed
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = "idn_area.district.{$code}";

            return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($code) {
                return $this->dataService->getDistrict($code);
            });
        }

        return $this->dataService->getDistrict($code);
    }

    /**
     * Get villages by district code.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function villagesByDistrict(string $districtCode, bool $cached = true): Collection
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = "idn_area.villages.district.{$districtCode}";

            return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($districtCode) {
                return $this->dataService->getVillagesByDistrict($districtCode);
            });
        }

        return $this->dataService->getVillagesByDistrict($districtCode);
    }

    /**
     * Get all villages.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function villages(bool $cached = true): Collection
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = 'idn_area.villages';

            return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
                return $this->dataService->getAllVillages();
            });
        }

        return $this->dataService->getAllVillages();
    }

    /**
     * Get village by code.
     */
    public function village(string $code, bool $cached = true): mixed
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = "idn_area.village.{$code}";

            return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($code) {
                return $this->dataService->getVillage($code);
            });
        }

        return $this->dataService->getVillage($code);
    }

    /**
     * Search by name across all area types.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function search(string $query, string $type = 'all', bool $cached = true): Collection
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = "idn_area.search.{$type}.".md5($query);

            return Cache::remember($cacheKey, $this->getSearchCacheTtl(), function () use ($query, $type) {
                return $this->dataService->searchByName($query, $type);
            });
        }

        return $this->dataService->searchByName($query, $type);
    }

    /**
     * Get current operation mode (api or local).
     */
    public function getMode(): string
    {
        return $this->config['mode'] ?? config('idn-area.mode') ?? 'local';
    }

    /**
     * Check if DataToko API is available (for API mode).
     */
    public function isApiAvailable(): bool
    {
        if (! $this->isApiMode() || ! $this->dataService instanceof DataTokoApiService) {
            return false;
        }

        try {
            // Test API connectivity by attempting to get provinces
            $this->dataService->getAllProvinces();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available periods from DataToko API (for API mode).
     */
    public function getPeriods(): array
    {
        return []; // DataToko API doesn't provide periods concept
    }

    /**
     * Clear all package caches.
     */
    public function clearCache(): bool
    {
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(['idn_area'])->flush();
        } else {
            // Simple pattern-based clearing for drivers without tag support
            $patterns = [
                'idn_area.*',
            ];

            foreach ($patterns as $pattern) {
                Cache::flush();
                break; // Just flush all for simplicity
            }
        }

        return true;
    }

    /**
     * Get comprehensive statistics.
     */
    public function statistics(bool $cached = true): array
    {
        if ($cached && $this->isCacheEnabled() && $this->isLocalMode()) {
            $cacheKey = 'idn_area.statistics';

            return Cache::remember($cacheKey, $this->getStatsCacheTtl(), function () {
                return $this->buildStatistics();
            });
        }

        return $this->buildStatistics();
    }

    /**
     * Create the appropriate data service based on configuration.
     */
    private function createDataService(): AreaDataServiceInterface
    {
        $mode = $this->getMode();

        return match ($mode) {
            'api' => new DataTokoApiService($this->config),
            'local' => new LocalDataService,
            default => new LocalDataService,
        };
    }

    /**
     * Check if currently in API mode.
     */
    private function isApiMode(): bool
    {
        return $this->getMode() === 'api';
    }

    /**
     * Check if currently in local mode.
     */
    private function isLocalMode(): bool
    {
        return $this->getMode() === 'local';
    }

    /**
     * Check if caching is enabled.
     */
    private function isCacheEnabled(): bool
    {
        return $this->config['cache']['enabled'] ?? config('idn-area.cache.enabled', true);
    }

    /**
     * Get cache TTL from config.
     */
    private function getCacheTtl(): int
    {
        return $this->config['cache']['ttl'] ?? config('idn-area.cache.ttl', 3600);
    }

    /**
     * Get search cache TTL.
     */
    private function getSearchCacheTtl(): int
    {
        return $this->config['cache']['search_ttl'] ?? config('idn-area.cache.search_ttl', 1800);
    }

    /**
     * Get statistics cache TTL.
     */
    private function getStatsCacheTtl(): int
    {
        return $this->config['cache']['stats_ttl'] ?? config('idn-area.cache.stats_ttl', 7200);
    }

    /**
     * Build comprehensive statistics.
     */
    private function buildStatistics(): array
    {
        $provinces = $this->provinces(false);
        $regencies = $this->regencies(false);
        $districts = $this->districts(false);
        $villages = $this->villages(false);

        return [
            'mode' => $this->getMode(),
            'api_available' => $this->isApiAvailable(),
            'provinces' => $provinces->count(),
            'regencies' => $regencies->count(),
            'districts' => $districts->count(),
            'villages' => $villages->count(),
            'largest_province_by_regencies' => $this->getLargestProvinceByRegencies(),
            'generated_by' => 'zaidysf Indonesian Area Data Package',
        ];
    }

    /**
     * Get largest province by number of regencies.
     */
    private function getLargestProvinceByRegencies(): ?array
    {
        $regencies = $this->regencies(false);
        $grouped = $regencies->groupBy('province_code');

        $largest = $grouped->sortByDesc(fn ($items) => $items->count())->first();

        if (! $largest) {
            return null;
        }

        $provinceCode = $largest->first()['province_code'] ?? null;
        if (! $provinceCode) {
            return null;
        }

        $province = $this->province($provinceCode, false);

        return $province ? [
            'code' => $provinceCode,
            'name' => $province['name'],
            'regency_count' => $largest->count(),
        ] : null;
    }

    // Additional methods for testing compatibility

    public function getProvinces(): array
    {
        if ($this->dataService instanceof LocalDataService) {
            return $this->dataService->getProvinces();
        }

        // For API service, convert collection to array
        return $this->provinces(false)->toArray();
    }

    public function getRegencies(string $provinceCode): array
    {
        if ($this->dataService instanceof LocalDataService) {
            return $this->dataService->getRegencies($provinceCode);
        }

        return $this->regenciesByProvince($provinceCode, false)->toArray();
    }

    public function getDistricts(string $regencyCode): array
    {
        if ($this->dataService instanceof LocalDataService) {
            return $this->dataService->getDistricts($regencyCode);
        }

        return $this->districtsByRegency($regencyCode, false)->toArray();
    }

    public function getVillages(string $districtCode): array
    {
        if ($this->dataService instanceof LocalDataService) {
            return $this->dataService->getVillages($districtCode);
        }

        return $this->villagesByDistrict($districtCode, false)->toArray();
    }

    public function findProvince(string $code): ?array
    {
        $province = $this->province($code, false);
        if (! $province) {
            return null;
        }

        // Convert model to array if needed
        if (is_array($province)) {
            return $province;
        }

        return [
            'code' => $province->code,
            'name' => $province->name,
        ];
    }

    public function findRegency(string $code): ?array
    {
        $regency = $this->regency($code, false);
        if (! $regency) {
            return null;
        }

        if (is_array($regency)) {
            return $regency;
        }

        return [
            'code' => $regency->code,
            'province_code' => $regency->province_code,
            'name' => $regency->name,
        ];
    }

    public function findDistrict(string $code): ?array
    {
        $district = $this->district($code, false);
        if (! $district) {
            return null;
        }

        if (is_array($district)) {
            return $district;
        }

        return [
            'code' => $district->code,
            'regency_code' => $district->regency_code,
            'name' => $district->name,
        ];
    }

    public function findVillage(string $code): ?array
    {
        $village = $this->village($code, false);
        if (! $village) {
            return null;
        }

        if (is_array($village)) {
            return $village;
        }

        return [
            'code' => $village->code,
            'district_code' => $village->district_code,
            'name' => $village->name,
        ];
    }

    public function searchProvinces(string $term): array
    {
        if ($this->dataService instanceof LocalDataService) {
            return $this->dataService->searchProvinces($term);
        }

        return $this->search($term, 'provinces', false)->toArray();
    }

    public function searchRegencies(string $term): array
    {
        if ($this->dataService instanceof LocalDataService) {
            return $this->dataService->searchRegencies($term);
        }

        return $this->search($term, 'regencies', false)->toArray();
    }

    public function searchDistricts(string $term): array
    {
        if ($this->dataService instanceof LocalDataService) {
            return $this->dataService->searchDistricts($term);
        }

        return $this->search($term, 'districts', false)->toArray();
    }

    public function searchVillages(string $term): array
    {
        if ($this->dataService instanceof LocalDataService) {
            return $this->dataService->searchVillages($term);
        }

        return $this->search($term, 'villages', false)->toArray();
    }

    public function getCurrentService(): AreaDataServiceInterface
    {
        return $this->dataService;
    }

    public function getCurrentMode(): string
    {
        return $this->getMode();
    }

    public function isServiceAvailable(): bool
    {
        try {
            if ($this->isApiMode()) {
                return $this->isApiAvailable();
            }

            // For local mode, check if we have data
            return $this->dataService instanceof LocalDataService;
        } catch (\Exception $e) {
            return false;
        }
    }
}
