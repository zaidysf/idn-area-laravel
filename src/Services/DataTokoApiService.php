<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use zaidysf\IdnArea\Contracts\AreaDataServiceInterface;
use zaidysf\IdnArea\Exceptions\DataTokoApiException;

final class DataTokoApiService implements AreaDataServiceInterface
{
    private string $baseUrl;

    private string $accessKey;

    private string $secretKey;

    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->baseUrl = $this->config['datatoko_api']['base_url'] ?? 'https://data.toko.center';
        $this->accessKey = $this->config['datatoko_api']['access_key'] ?? '';
        $this->secretKey = $this->config['datatoko_api']['secret_key'] ?? '';

        if (empty($this->accessKey) || empty($this->secretKey)) {
            throw new DataTokoApiException('DataToko API credentials are required. Please configure IDN_AREA_ACCESS_KEY and IDN_AREA_SECRET_KEY.');
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAllProvinces(): Collection
    {
        $response = $this->makeAuthenticatedRequest('/api/indonesia/provinces');

        return $this->transformProvinces($response);
    }

    public function getProvince(string $code): ?array
    {
        $response = $this->makeAuthenticatedRequest("/api/indonesia/provinces/{$code}");

        return $response ? $this->transformProvince($response) : null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getRegenciesByProvince(string $provinceCode): Collection
    {
        $response = $this->makeAuthenticatedRequest("/api/indonesia/provinces/{$provinceCode}/regencies");

        return $this->transformRegencies($response, $provinceCode);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAllRegencies(): Collection
    {
        $response = $this->makeAuthenticatedRequest('/api/indonesia/regencies');

        return $this->transformRegencies($response);
    }

    public function getRegency(string $code): ?array
    {
        $response = $this->makeAuthenticatedRequest("/api/indonesia/regencies/{$code}");

        return $response ? $this->transformRegency($response) : null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getDistrictsByRegency(string $regencyCode): Collection
    {
        $response = $this->makeAuthenticatedRequest("/api/indonesia/regencies/{$regencyCode}/districts");

        return $this->transformDistricts($response, $regencyCode);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAllDistricts(): Collection
    {
        $response = $this->makeAuthenticatedRequest('/api/indonesia/districts');

        return $this->transformDistricts($response);
    }

    public function getDistrict(string $code): ?array
    {
        $response = $this->makeAuthenticatedRequest("/api/indonesia/districts/{$code}");

        return $response ? $this->transformDistrict($response) : null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getVillagesByDistrict(string $districtCode): Collection
    {
        $response = $this->makeAuthenticatedRequest("/api/indonesia/districts/{$districtCode}/villages");

        return $this->transformVillages($response, $districtCode);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAllVillages(): Collection
    {
        $response = $this->makeAuthenticatedRequest('/api/indonesia/villages');

        return $this->transformVillages($response);
    }

    public function getVillage(string $code): ?array
    {
        $response = $this->makeAuthenticatedRequest("/api/indonesia/villages/{$code}");

        return $response ? $this->transformVillage($response) : null;
    }

    /**
     * @return Collection<string, mixed>
     */
    public function search(string $query): Collection
    {
        $response = $this->makeAuthenticatedRequest('/api/indonesia/search', [
            'query' => $query,
        ]);

        return collect([
            'provinces' => $this->transformProvinces($response['provinces'] ?? []),
            'regencies' => $this->transformRegencies($response['regencies'] ?? []),
            'districts' => $this->transformDistricts($response['districts'] ?? []),
            'villages' => $this->transformVillages($response['villages'] ?? []),
            'islands' => collect($response['islands'] ?? []),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchByName(string $query, string $type = 'all'): Collection
    {
        $response = $this->makeAuthenticatedRequest('/api/indonesia/search', [
            'query' => $query,
            'type' => $type,
        ]);

        if ($type === 'all') {
            return collect([
                'provinces' => $this->transformProvinces($response['provinces'] ?? []),
                'regencies' => $this->transformRegencies($response['regencies'] ?? []),
                'districts' => $this->transformDistricts($response['districts'] ?? []),
                'villages' => $this->transformVillages($response['villages'] ?? []),
                'islands' => collect($response['islands'] ?? []),
            ]);
        }

        // Return specific type search results
        return match ($type) {
            'provinces' => $this->transformProvinces($response['provinces'] ?? $response),
            'regencies' => $this->transformRegencies($response['regencies'] ?? $response),
            'districts' => $this->transformDistricts($response['districts'] ?? $response),
            'villages' => $this->transformVillages($response['villages'] ?? $response),
            default => collect($response)
        };
    }

    /**
     * Make an authenticated request to DataToko API
     */
    private function makeAuthenticatedRequest(string $endpoint, array $params = []): ?array
    {
        $token = $this->getAuthToken();

        if (! $token) {
            $token = $this->authenticate();
        }

        $url = rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->config['datatoko_api']['timeout'] ?? 30)
            ->get($url, $params);

        // If unauthorized, try to re-authenticate once
        if ($response->status() === 401) {
            $this->clearAuthToken();
            $token = $this->authenticate();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->timeout($this->config['datatoko_api']['timeout'] ?? 30)
                ->get($url, $params);
        }

        if (! $response->successful()) {
            throw new DataTokoApiException(
                "DataToko API request failed: {$response->status()} - {$response->body()}"
            );
        }

        $data = $response->json();

        return $data['data'] ?? $data;
    }

    /**
     * Authenticate with DataToko API using HMAC signing
     */
    private function authenticate(): string
    {
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));

        // Create HMAC signature
        $message = $this->accessKey.$timestamp.$nonce;
        $signature = hash_hmac('sha256', $message, $this->secretKey);

        $response = Http::timeout($this->config['datatoko_api']['timeout'] ?? 30)
            ->post($this->baseUrl.'/api/auth/login', [
                'access_key' => $this->accessKey,
                'timestamp' => $timestamp,
                'nonce' => $nonce,
                'signature' => $signature,
            ]);

        if (! $response->successful()) {
            throw new DataTokoApiException(
                "DataToko authentication failed: {$response->status()} - {$response->body()}"
            );
        }

        $data = $response->json();
        $token = $data['data']['token'] ?? $data['token'] ?? null;

        if (! $token) {
            throw new DataTokoApiException('No authentication token received from DataToko API');
        }

        $this->storeAuthToken($token);

        return $token;
    }

    /**
     * Get stored authentication token
     */
    private function getAuthToken(): ?string
    {
        $driver = $this->config['token_storage']['driver'];

        return match ($driver) {
            'redis' => $this->getTokenFromRedis(),
            'file' => $this->getTokenFromFile(),
            'cache' => $this->getTokenFromCache(),
            default => $this->getTokenFromCache()
        };
    }

    /**
     * Store authentication token
     */
    private function storeAuthToken(string $token): void
    {
        $driver = $this->config['token_storage']['driver'];
        $ttl = $this->config['token_storage']['ttl'];

        match ($driver) {
            'redis' => $this->storeTokenInRedis($token, $ttl),
            'file' => $this->storeTokenInFile($token, $ttl),
            'cache' => $this->storeTokenInCache($token, $ttl),
            default => $this->storeTokenInCache($token, $ttl)
        };
    }

    /**
     * Clear stored authentication token
     */
    private function clearAuthToken(): void
    {
        $driver = $this->config['token_storage']['driver'];

        match ($driver) {
            'redis' => $this->clearTokenFromRedis(),
            'file' => $this->clearTokenFromFile(),
            'cache' => $this->clearTokenFromCache(),
            default => $this->clearTokenFromCache()
        };
    }

    // Redis token storage methods
    private function getTokenFromRedis(): ?string
    {
        try {
            $data = Redis::get($this->config['token_storage']['redis_key']);
            if (! $data) {
                return null;
            }

            $tokenData = json_decode($data, true);
            if (time() > $tokenData['expires_at']) {
                $this->clearTokenFromRedis();

                return null;
            }

            return $tokenData['token'];
        } catch (\Exception) {
            return null;
        }
    }

    private function storeTokenInRedis(string $token, int $ttl): void
    {
        $data = json_encode([
            'token' => $token,
            'expires_at' => time() + $ttl,
        ]);
        Redis::setex($this->config['token_storage']['redis_key'], $ttl, $data);
    }

    private function clearTokenFromRedis(): void
    {
        Redis::del($this->config['token_storage']['redis_key']);
    }

    // File token storage methods
    private function getTokenFromFile(): ?string
    {
        try {
            $filePath = base_path($this->config['token_storage']['file_path']);
            if (! File::exists($filePath)) {
                return null;
            }

            $data = json_decode(File::get($filePath), true);
            if (time() > $data['expires_at']) {
                File::delete($filePath);

                return null;
            }

            return $data['token'];
        } catch (\Exception) {
            return null;
        }
    }

    private function storeTokenInFile(string $token, int $ttl): void
    {
        $filePath = base_path($this->config['token_storage']['file_path']);
        $dir = dirname($filePath);

        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $data = json_encode([
            'token' => $token,
            'expires_at' => time() + $ttl,
        ]);

        File::put($filePath, $data);
    }

    private function clearTokenFromFile(): void
    {
        $filePath = base_path($this->config['token_storage']['file_path']);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }

    // Cache token storage methods
    private function getTokenFromCache(): ?string
    {
        return Cache::get('idn_area_datatoko_token');
    }

    private function storeTokenInCache(string $token, int $ttl): void
    {
        Cache::put('idn_area_datatoko_token', $token, $ttl);
    }

    private function clearTokenFromCache(): void
    {
        Cache::forget('idn_area_datatoko_token');
    }

    // Data transformation methods
    /**
     * @param mixed $data
     * @return Collection<int, array<string, mixed>>
     */
    private function transformProvinces($data): Collection
    {
        return collect($data)->map(function ($item) {
            return [
                'code' => $item['code'] ?? $item['id'],
                'name' => $item['name'] ?? $item['nama'],
            ];
        });
    }

    /**
     * @param mixed $data
     * @return array<string, mixed>
     */
    private function transformProvince($data): array
    {
        return [
            'code' => $data['code'] ?? $data['id'],
            'name' => $data['name'] ?? $data['nama'],
        ];
    }

    /**
     * @param mixed $data
     * @return Collection<int, array<string, mixed>>
     */
    private function transformRegencies($data, ?string $provinceCode = null): Collection
    {
        return collect($data)->map(function ($item) use ($provinceCode) {
            return [
                'code' => $item['code'] ?? $item['id'],
                'province_code' => $provinceCode ?? $item['province_code'] ?? $item['province_id'],
                'name' => $item['name'] ?? $item['nama'],
            ];
        });
    }

    /**
     * @param mixed $data
     * @return array<string, mixed>
     */
    private function transformRegency($data): array
    {
        return [
            'code' => $data['code'] ?? $data['id'],
            'province_code' => $data['province_code'] ?? $data['province_id'],
            'name' => $data['name'] ?? $data['nama'],
        ];
    }

    /**
     * @param mixed $data
     * @return Collection<int, array<string, mixed>>
     */
    private function transformDistricts($data, ?string $regencyCode = null): Collection
    {
        return collect($data)->map(function ($item) use ($regencyCode) {
            return [
                'code' => $item['code'] ?? $item['id'],
                'regency_code' => $regencyCode ?? $item['regency_code'] ?? $item['regency_id'],
                'name' => $item['name'] ?? $item['nama'],
            ];
        });
    }

    /**
     * @param mixed $data
     * @return array<string, mixed>
     */
    private function transformDistrict($data): array
    {
        return [
            'code' => $data['code'] ?? $data['id'],
            'regency_code' => $data['regency_code'] ?? $data['regency_id'],
            'name' => $data['name'] ?? $data['nama'],
        ];
    }

    /**
     * @param mixed $data
     * @return Collection<int, array<string, mixed>>
     */
    private function transformVillages($data, ?string $districtCode = null): Collection
    {
        return collect($data)->map(function ($item) use ($districtCode) {
            return [
                'code' => $item['code'] ?? $item['id'],
                'district_code' => $districtCode ?? $item['district_code'] ?? $item['district_id'],
                'name' => $item['name'] ?? $item['nama'],
            ];
        });
    }

    /**
     * @param mixed $data
     * @return array<string, mixed>
     */
    private function transformVillage($data): array
    {
        return [
            'code' => $data['code'] ?? $data['id'],
            'district_code' => $data['district_code'] ?? $data['district_id'],
            'name' => $data['name'] ?? $data['nama'],
        ];
    }

    private function getDefaultConfig(): array
    {
        return config('idn-area', []);
    }
}
