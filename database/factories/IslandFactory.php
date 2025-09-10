<?php

namespace zaidysf\IdnArea\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use zaidysf\IdnArea\Models\Island;

class IslandFactory extends Factory
{
    protected $model = Island::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{3}'),
            'coordinate' => $this->faker->optional()->latitude(-11, 6).','.$this->faker->optional()->longitude(95, 141),
            'name' => 'PULAU '.strtoupper($this->faker->unique()->words(1, true)),
            'is_outermost_small' => $this->faker->boolean(10), // 10% chance
            'is_populated' => $this->faker->boolean(30), // 30% chance
            'regency_code' => null, // Will be set by relationship
        ];
    }

    /**
     * Create island for specific regency
     */
    public function forRegency(string $regencyCode): static
    {
        return $this->state([
            'regency_code' => $regencyCode,
        ]);
    }

    /**
     * Create populated island
     */
    public function populated(): static
    {
        return $this->state([
            'is_populated' => true,
            'is_outermost_small' => false,
        ]);
    }

    /**
     * Create outermost small island
     */
    public function outermostSmall(): static
    {
        return $this->state([
            'is_outermost_small' => true,
            'is_populated' => false,
        ]);
    }

    /**
     * Create uninhabited island
     */
    public function uninhabited(): static
    {
        return $this->state([
            'is_populated' => false,
            'is_outermost_small' => false,
        ]);
    }

    /**
     * Create island with coordinates
     */
    public function withCoordinates(float $latitude, float $longitude): static
    {
        return $this->state([
            'coordinate' => "{$latitude},{$longitude}",
        ]);
    }

    /**
     * Specific islands for testing
     */
    public function bidadari(): static
    {
        return $this->state([
            'name' => 'PULAU BIDADARI',
            'is_populated' => true,
            'coordinate' => '-6.030,106.732',
            'regency_code' => '31.01', // Kepulauan Seribu
        ]);
    }

    public function bali(): static
    {
        return $this->state([
            'name' => 'PULAU BALI',
            'is_populated' => true,
            'coordinate' => '-8.340,115.092',
            'regency_code' => '51.01', // Example regency in Bali
        ]);
    }

    public function sabang(): static
    {
        return $this->state([
            'name' => 'PULAU WEH',
            'is_outermost_small' => true,
            'is_populated' => true,
            'coordinate' => '5.874,95.320',
            'regency_code' => '11.72', // Kota Sabang
        ]);
    }
}
