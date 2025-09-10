<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Indonesian Regency/City Model
 *
 * @property string $code BPS regency code (3173, 3201, etc.)
 * @property string $province_code Parent province code
 * @property string $name Regency name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Province $province
 * @property-read \Illuminate\Database\Eloquent\Collection<int, District> $districts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Village> $villages
 * @property-read int|null $districts_count
 * @property-read int|null $villages_count
 */
class Regency extends Model
{
    /** @use HasFactory<\zaidysf\IdnArea\Database\Factories\RegencyFactory> */
    use HasFactory;

    protected $table = 'idn_regencies';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'province_code',
        'name',
    ];

    protected $casts = [
        'code' => 'string',
        'province_code' => 'string',
        'name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \zaidysf\IdnArea\Database\Factories\RegencyFactory
    {
        return \zaidysf\IdnArea\Database\Factories\RegencyFactory::new();
    }

    /**
     * Get the province that owns the regency.
     *
     * @return BelongsTo<Province, $this>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    /**
     * Get all districts that belong to this regency.
     *
     * @return HasMany<District, $this>
     */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'regency_code', 'code')
            ->orderBy('name');
    }

    /**
     * Get all villages that belong to this regency through districts.
     *
     * @return HasManyThrough<Village, District, $this>
     */
    public function villages(): HasManyThrough
    {
        return $this->hasManyThrough(
            Village::class,
            District::class,
            'regency_code', // Foreign key on districts table
            'district_code', // Foreign key on villages table
            'code',         // Local key on regencies table
            'code'          // Local key on districts table
        )->orderBy('villages.name');
    }

    /**
     * Get all islands that belong to this regency.
     *
     * @return HasMany<Island, $this>
     */
    public function islands(): HasMany
    {
        return $this->hasMany(Island::class, 'regency_code', 'code')
            ->orderBy('name');
    }

    /**
     * Scope to search regencies by name.
     *
     * @param  Builder<Regency>  $query
     * @return Builder<Regency>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    /**
     * Scope to filter by province.
     *
     * @param  Builder<Regency>  $query
     * @return Builder<Regency>
     */
    public function scopeInProvince(Builder $query, string $provinceCode): Builder
    {
        return $query->where('province_code', $provinceCode);
    }

    /**
     * Scope to get regencies with district counts.
     *
     * @param  Builder<Regency>  $query
     * @return Builder<Regency>
     */
    public function scopeWithDistrictCount(Builder $query): Builder
    {
        return $query->withCount('districts');
    }

    /**
     * Scope to get regencies with village counts.
     *
     * @param  Builder<Regency>  $query
     * @return Builder<Regency>
     */
    public function scopeWithVillageCount(Builder $query): Builder
    {
        return $query->withCount('villages');
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
     * Get full formatted name with province.
     */
    public function getFullFormattedNameAttribute(): string
    {
        return "{$this->name}, {$this->province->name}";
    }

    /**
     * Get regencies by province code (static method).
     */
    public static function byProvince(string $provinceCode)
    {
        return static::query()->inProvince($provinceCode)->get();
    }

    /**
     * Search regencies by name (static method).
     */
    public static function search(string $term)
    {
        return static::query()->search($term)->get();
    }

    /**
     * Find regency by code (static method).
     */
    public static function findByCode(string $code): ?self
    {
        return static::find($code);
    }

    /**
     * Check if regency has any districts.
     */
    public function hasDistricts(): bool
    {
        return $this->districts()->exists();
    }

    /**
     * Check if this is a city (kota) or regency (kabupaten).
     * Cities typically have codes ending with 71-99
     * BPS coding system by zaidysf
     */
    public function getIsCityAttribute(): bool
    {
        $lastPart = (int) substr($this->code, -2);

        return $lastPart >= 71;
    }

    /**
     * Get total village count for this regency.
     */
    public function getTotalVillagesAttribute(): int
    {
        return $this->villages()->count();
    }
}
