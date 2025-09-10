<?php

namespace zaidysf\IdnArea\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Base factory class for the Indonesian Area package.
 *
 * Individual model factories should extend the Factory class directly
 * and be placed in this directory following Laravel conventions.
 *
 * Available factories:
 * - ProvinceFactory
 * - RegencyFactory
 * - DistrictFactory
 * - VillageFactory
 * - IslandFactory
 *
 * Usage example:
 *
 * Province::factory()->create();
 * Province::factory()->jakarta()->create();
 * Regency::factory()->forProvince('32')->create();
 * Island::factory()->populated()->create();
 */
class BaseFactory extends Factory
{
    // This is a base class for documentation purposes
    // Individual model factories should extend Factory directly
}
