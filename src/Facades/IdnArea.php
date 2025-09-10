<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Facades;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;
use zaidysf\IdnArea\Models\District;
use zaidysf\IdnArea\Models\Province;
use zaidysf\IdnArea\Models\Regency;
use zaidysf\IdnArea\Models\Village;

/**
 * Enhanced Indonesian Area Facade
 *
 * @method static Collection provinces(bool $cached = true)
 * @method static Province|null province(string $code, bool $cached = true)
 * @method static Collection regenciesByProvince(string $provinceCode, array $with = [], bool $cached = true)
 * @method static Regency|null regency(string $code, bool $cached = true)
 * @method static Collection districtsByRegency(string $regencyCode, int $limit = 0, bool $cached = true)
 * @method static District|null district(string $code, bool $cached = true)
 * @method static Collection villagesByDistrict(string $districtCode, int $limit = 100, bool $cached = true)
 * @method static Village|null village(string $code, bool $cached = true)
 * @method static Collection islandsByRegency(string $regencyCode, bool $cached = true)
 * @method static Collection outermostSmallIslands(bool $cached = true)
 * @method static Collection populatedIslands(bool $cached = true)
 * @method static array search(string $query, string $type = 'all', int $limit = 100, bool $exactMatch = false, bool $cached = true)
 * @method static array hierarchy(string $provinceCode, bool $cached = true)
 * @method static array statistics(bool $cached = true)
 * @method static Collection getMultiple(array $codes, string $type = 'auto')
 * @method static bool clearCache()
 *
 * @see \zaidysf\IdnArea\IdnArea
 */
class IdnArea extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \zaidysf\IdnArea\IdnArea::class;
    }
}
