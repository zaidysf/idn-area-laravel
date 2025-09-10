<?php

namespace zaidysf\IdnArea\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;

class RegencyFactory extends Factory
{
    protected $model = Regency::class;

    public function definition(): array
    {
        $provinceCode = $this->faker->numberBetween(10, 99);
        $regencyCode = $this->faker->numberBetween(1, 99);

        return [
            'code' => sprintf('%02d.%02d', $provinceCode, $regencyCode),
            'province_code' => sprintf('%02d', $provinceCode),
            'name' => strtoupper($this->faker->randomElement(['KABUPATEN', 'KOTA']).' '.$this->faker->unique()->words(2, true)),
        ];
    }

    /**
     * Create regency for specific province
     */
    public function forProvince(string $provinceCode): static
    {
        return $this->state(function () use ($provinceCode) {
            $regencyCode = $this->faker->numberBetween(1, 99);

            return [
                'code' => sprintf('%s.%02d', $provinceCode, $regencyCode),
                'province_code' => $provinceCode,
            ];
        });
    }

    /**
     * Create city (KOTA) type regency
     */
    public function city(): static
    {
        return $this->state([
            'name' => 'KOTA '.strtoupper($this->faker->unique()->city()),
        ]);
    }

    /**
     * Create regency (KABUPATEN) type
     */
    public function regency(): static
    {
        return $this->state([
            'name' => 'KABUPATEN '.strtoupper($this->faker->unique()->words(2, true)),
        ]);
    }

    /**
     * Specific regencies for testing
     */
    public function jakartaCentral(): static
    {
        return $this->state([
            'code' => '31.71',
            'province_code' => '31',
            'name' => 'KOTA ADMINISTRASI JAKARTA PUSAT',
        ]);
    }

    public function bandung(): static
    {
        return $this->state([
            'code' => '32.04',
            'province_code' => '32',
            'name' => 'KABUPATEN BANDUNG',
        ]);
    }
}
