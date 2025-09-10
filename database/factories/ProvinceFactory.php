<?php

namespace zaidysf\IdnArea\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use zaidysf\IdnArea\Models\Province;

class ProvinceFactory extends Factory
{
    protected $model = Province::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numberBetween(10, 99),
            'name' => strtoupper($this->faker->unique()->words(2, true)),
        ];
    }

    /**
     * Create specific provinces for testing
     */
    public function jakarta(): static
    {
        return $this->state([
            'code' => '31',
            'name' => 'DKI JAKARTA',
        ]);
    }

    public function westJava(): static
    {
        return $this->state([
            'code' => '32',
            'name' => 'JAWA BARAT',
        ]);
    }

    public function eastJava(): static
    {
        return $this->state([
            'code' => '35',
            'name' => 'JAWA TIMUR',
        ]);
    }

    public function bali(): static
    {
        return $this->state([
            'code' => '51',
            'name' => 'BALI',
        ]);
    }
}
