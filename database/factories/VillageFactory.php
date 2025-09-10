<?php

namespace zaidysf\IdnArea\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use zaidysf\IdnArea\Models\Village;

class VillageFactory extends Factory
{
    protected $model = Village::class;

    public function definition(): array
    {
        $provinceCode = $this->faker->numberBetween(10, 99);
        $regencyCode = $this->faker->numberBetween(1, 99);
        $districtCode = $this->faker->numberBetween(1, 99);
        $villageCode = $this->faker->numberBetween(1000, 9999);

        return [
            'code' => sprintf('%02d.%02d.%02d.%04d', $provinceCode, $regencyCode, $districtCode, $villageCode),
            'district_code' => sprintf('%02d.%02d.%02d', $provinceCode, $regencyCode, $districtCode),
            'name' => strtoupper($this->faker->unique()->words(2, true)),
        ];
    }

    /**
     * Create village for specific district
     */
    public function forDistrict(string $districtCode): static
    {
        return $this->state(function () use ($districtCode) {
            $villageCode = $this->faker->numberBetween(1000, 9999);

            return [
                'code' => sprintf('%s.%04d', $districtCode, $villageCode),
                'district_code' => $districtCode,
            ];
        });
    }

    /**
     * Create village with common prefixes
     */
    public function withPrefix(string $prefix): static
    {
        return $this->state([
            'name' => strtoupper($prefix.' '.$this->faker->unique()->words(1, true)),
        ]);
    }

    /**
     * Specific villages for testing
     */
    public function sukamaju(): static
    {
        return $this->state([
            'code' => '32.04.01.2001',
            'district_code' => '32.04.01',
            'name' => 'SUKAMAJU',
        ]);
    }

    public function gondangdia(): static
    {
        return $this->state([
            'code' => '31.71.01.1001',
            'district_code' => '31.71.01',
            'name' => 'GONDANGDIA',
        ]);
    }
}
