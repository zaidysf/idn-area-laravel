<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Indonesian Province Model - BPS official data by zaidysf
 *
 * @property string $code BPS province code (11, 12, etc.)
 * @property string $name Province name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Regency> $regencies
 * @property-read \Illuminate\Database\Eloquent\Collection<int, District> $districts
 * @property-read int|null $regencies_count
 * @property-read int|null $districts_count
 */
class Province extends Model
{
    /** @use HasFactory<\zaidysf\IdnArea\Database\Factories\ProvinceFactory> */
    use HasFactory;

    protected $table = 'idn_provinces';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
    ];

    protected $casts = [
        'code' => 'string',
        'name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \zaidysf\IdnArea\Database\Factories\ProvinceFactory
    {
        return \zaidysf\IdnArea\Database\Factories\ProvinceFactory::new();
    }

    /**
     * Get all regencies that belong to this province.
     *
     * @return HasMany<Regency, $this>
     */
    public function regencies(): HasMany
    {
        return $this->hasMany(Regency::class, 'province_code', 'code')
            ->orderBy('name');
    }

    /**
     * Get all districts that belong to this province through regencies.
     *
     * @return HasManyThrough<District, Regency, $this>
     */
    public function districts(): HasManyThrough
    {
        return $this->hasManyThrough(
            District::class,
            Regency::class,
            'province_code', // Foreign key on regencies table
            'regency_code',  // Foreign key on districts table
            'code',          // Local key on provinces table
            'code'           // Local key on regencies table
        )->orderBy('idn_districts.name');
    }

    /**
     * Get all villages that belong to this province through regencies and districts.
     * Note: This is a heavy query, use with caution and consider adding limits.
     *
     * @return Builder<Village>
     */
    public function villages(): Builder
    {
        return Village::whereHas('district.regency', function ($query) {
            $query->where('province_code', $this->code);
        })->orderBy('name');
    }

    /**
     * Get all islands that belong to this province through regencies.
     *
     * @return Builder<Island>
     */
    public function islands(): Builder
    {
        return Island::whereHas('regency', function ($query) {
            $query->where('province_code', $this->code);
        })->orderBy('name');
    }

    /**
     * Scope to search provinces by name.
     *
     * @param  Builder<Province>  $query
     * @return Builder<Province>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    /**
     * Scope to get provinces with regency counts.
     *
     * @param  Builder<Province>  $query
     * @return Builder<Province>
     */
    public function scopeWithRegencyCount(Builder $query): Builder
    {
        return $query->withCount('regencies');
    }

    /**
     * Scope to get provinces with district counts.
     *
     * @param  Builder<Province>  $query
     * @return Builder<Province>
     */
    public function scopeWithDistrictCount(Builder $query): Builder
    {
        return $query->withCount('districts');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * Get formatted name with code.
     */
    public function getFormattedNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Search provinces by name (static method).
     */
    public static function search(string $term)
    {
        return static::query()->search($term)->get();
    }

    /**
     * Find province by code (static method).
     */
    public static function findByCode(string $code): ?self
    {
        return static::find($code);
    }

    /**
     * Check if province has any regencies.
     */
    public function hasRegencies(): bool
    {
        return $this->regencies()->exists();
    }

    /**
     * Get total district count for this province.
     */
    public function getTotalDistrictsAttribute(): int
    {
        return $this->districts()->count();
    }

    /**
     * Get total village count for this province.
     */
    public function getTotalVillagesAttribute(): int
    {
        return $this->villages()->count();
    }
}
