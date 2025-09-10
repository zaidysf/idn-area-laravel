<?php

namespace zaidysf\IdnArea\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use zaidysf\IdnArea\Models\District;

class DistrictFactory extends Factory
{
    protected $model = District::class;

    public function definition(): array
    {
        $provinceCode = $this->faker->numberBetween(10, 99);
        $regencyCode = $this->faker->numberBetween(1, 99);
        $districtCode = $this->faker->numberBetween(1, 99);

        return [
            'code' => sprintf('%02d.%02d.%02d', $provinceCode, $regencyCode, $districtCode),
            'regency_code' => sprintf('%02d.%02d', $provinceCode, $regencyCode),
            'name' => strtoupper($this->faker->unique()->words(2, true)),
        ];
    }

    /**
     * Create district for specific regency
     */
    public function forRegency(string $regencyCode): static
    {
        return $this->state(function () use ($regencyCode) {
            $districtCode = $this->faker->numberBetween(1, 99);

            return [
                'code' => sprintf('%s.%02d', $regencyCode, $districtCode),
                'regency_code' => $regencyCode,
            ];
        });
    }

    /**
     * Specific districts for testing
     */
    public function sukasari(): static
    {
        return $this->state([
            'code' => '32.04.01',
            'regency_code' => '32.04',
            'name' => 'SUKASARI',
        ]);
    }

    public function menteng(): static
    {
        return $this->state([
            'code' => '31.71.01',
            'regency_code' => '31.71',
            'name' => 'MENTENG',
        ]);
    }
}
