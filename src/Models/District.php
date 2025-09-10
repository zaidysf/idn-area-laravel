<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Indonesian District Model - BPS structure by zaidysf
 *
 * @property string $code BPS district code (3173060, etc.)
 * @property string $regency_code Parent regency code
 * @property string $name District name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Regency $regency
 * @property-read Province $province
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Village> $villages
 * @property-read int|null $villages_count
 */
class District extends Model
{
    /** @use HasFactory<\zaidysf\IdnArea\Database\Factories\DistrictFactory> */
    use HasFactory;

    protected $table = 'idn_districts';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'regency_code',
        'name',
    ];

    protected $casts = [
        'code' => 'string',
        'regency_code' => 'string',
        'name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \zaidysf\IdnArea\Database\Factories\DistrictFactory
    {
        return \zaidysf\IdnArea\Database\Factories\DistrictFactory::new();
    }

    /**
     * Get the regency that owns the district.
     *
     * @return BelongsTo<Regency, $this>
     */
    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_code', 'code');
    }

    /**
     * Get the province through regency.
     *
     * @return HasOneThrough<Province, Regency, $this>
     */
    public function province(): HasOneThrough
    {
        return $this->hasOneThrough(
            Province::class,
            Regency::class,
            'code',          // Foreign key on regencies table
            'code',          // Foreign key on provinces table
            'regency_code',  // Local key on districts table
            'province_code'  // Local key on regencies table
        );
    }

    /**
     * Get all villages that belong to this district.
     *
     * @return HasMany<Village, $this>
     */
    public function villages(): HasMany
    {
        return $this->hasMany(Village::class, 'district_code', 'code')
            ->orderBy('name');
    }

    /**
     * Scope to search districts by name.
     *
     * @param  Builder<District>  $query
     * @return Builder<District>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    /**
     * Scope to filter by regency.
     *
     * @param  Builder<District>  $query
     * @return Builder<District>
     */
    public function scopeInRegency(Builder $query, string $regencyCode): Builder
    {
        return $query->where('regency_code', $regencyCode);
    }

    /**
     * Scope to filter by province through regency.
     *
     * @param  Builder<District>  $query
     * @return Builder<District>
     */
    public function scopeInProvince(Builder $query, string $provinceCode): Builder
    {
        return $query->whereHas('regency', function ($q) use ($provinceCode) {
            $q->where('province_code', $provinceCode);
        });
    }

    /**
     * Scope to get districts with village counts.
     *
     * @param  Builder<District>  $query
     * @return Builder<District>
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
     * Get full formatted name with regency and province.
     */
    public function getFullFormattedNameAttribute(): string
    {
        return "{$this->name}, {$this->regency->name}, {$this->regency->province->name}";
    }

    /**
     * Get districts by regency code (static method).
     */
    public static function byRegency(string $regencyCode)
    {
        return static::query()->inRegency($regencyCode)->get();
    }

    /**
     * Search districts by name (static method).
     */
    public static function search(string $term)
    {
        return static::query()->search($term)->get();
    }

    /**
     * Find district by code (static method).
     */
    public static function findByCode(string $code): ?self
    {
        return static::find($code);
    }

    /**
     * Check if district has any villages.
     */
    public function hasVillages(): bool
    {
        return $this->villages()->exists();
    }

    /**
     * Get total village count for this district.
     */
    public function getTotalVillagesAttribute(): int
    {
        return $this->villages()->count();
    }

    /**
     * Get the province code through regency.
     */
    public function getProvinceCodeAttribute(): string
    {
        return $this->regency->province_code;
    }

    /**
     * Get the province name through regency.
     */
    public function getProvinceNameAttribute(): string
    {
        return $this->regency->province->name;
    }

    /**
     * Check if this is a sub-district (kecamatan) or administrative village (kelurahan).
     * This is determined by context and usage patterns.
     */
    public function getTypeAttribute(): string
    {
        // This is a simplification - in reality, districts are always "kecamatan"
        // but this method provides extensibility for future enhancements
        return 'kecamatan';
    }
}
