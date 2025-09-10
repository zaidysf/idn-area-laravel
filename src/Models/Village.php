<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Indonesian Village Model - BPS data structure by zaidysf
 *
 * @property string $code BPS village code (3173060001, etc.)
 * @property string $district_code Parent district code
 * @property string $name Village name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read District $district
 * @property-read Regency $regency
 * @property-read Province|null $province
 */
class Village extends Model
{
    /** @use HasFactory<\zaidysf\IdnArea\Database\Factories\VillageFactory> */
    use HasFactory;

    protected $table = 'idn_villages';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'district_code',
        'name',
    ];

    protected $casts = [
        'code' => 'string',
        'district_code' => 'string',
        'name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \zaidysf\IdnArea\Database\Factories\VillageFactory
    {
        return \zaidysf\IdnArea\Database\Factories\VillageFactory::new();
    }

    /**
     * Get the district that owns the village.
     *
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    /**
     * Get the regency through district.
     *
     * @return HasOneThrough<Regency, District, $this>
     */
    public function regency(): HasOneThrough
    {
        return $this->hasOneThrough(
            Regency::class,
            District::class,
            'code',         // Foreign key on districts table
            'code',         // Foreign key on regencies table
            'district_code', // Local key on villages table
            'regency_code'  // Local key on districts table
        );
    }

    /**
     * Get the province through district and regency.
     */
    public function province(): ?Province
    {
        return Province::whereHas('regencies.districts', function ($query) {
            $query->where('code', $this->district_code);
        })->first();
    }

    /**
     * Scope to search villages by name.
     *
     * @param  Builder<Village>  $query
     * @return Builder<Village>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    /**
     * Scope to filter by district.
     *
     * @param  Builder<Village>  $query
     * @return Builder<Village>
     */
    public function scopeInDistrict(Builder $query, string $districtCode): Builder
    {
        return $query->where('district_code', $districtCode);
    }

    /**
     * Scope to filter by regency through district.
     *
     * @param  Builder<Village>  $query
     * @return Builder<Village>
     */
    public function scopeInRegency(Builder $query, string $regencyCode): Builder
    {
        return $query->whereHas('district', function ($q) use ($regencyCode) {
            $q->where('regency_code', $regencyCode);
        });
    }

    /**
     * Scope to filter by province through district and regency.
     *
     * @param  Builder<Village>  $query
     * @return Builder<Village>
     */
    public function scopeInProvince(Builder $query, string $provinceCode): Builder
    {
        return $query->whereHas('district.regency', function ($q) use ($provinceCode) {
            $q->where('province_code', $provinceCode);
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * Get villages by district code (static method).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function byDistrict(string $districtCode)
    {
        return static::query()->inDistrict($districtCode)->get();
    }

    /**
     * Search villages by name (static method).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function search(string $term)
    {
        return static::query()->search($term)->get();
    }

    /**
     * Find village by code (static method).
     */
    public static function findByCode(string $code): ?self
    {
        return static::find($code);
    }

    /**
     * Get formatted name with code.
     */
    public function getFormattedNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Get full formatted name with district, regency, and province.
     */
    public function getFullFormattedNameAttribute(): string
    {
        return "{$this->name}, {$this->district->name}, {$this->district->regency->name}, {$this->district->regency->province->name}";
    }

    /**
     * Get the regency code through district.
     */
    public function getRegencyCodeAttribute(): string
    {
        return $this->district->regency_code;
    }

    /**
     * Get the province code through district and regency.
     */
    public function getProvinceCodeAttribute(): string
    {
        return $this->district->regency->province_code;
    }

    /**
     * Get the district name.
     */
    public function getDistrictNameAttribute(): string
    {
        return $this->district->name;
    }

    /**
     * Get the regency name through district.
     */
    public function getRegencyNameAttribute(): string
    {
        return $this->district->regency->name;
    }

    /**
     * Get the province name through district and regency.
     */
    public function getProvinceNameAttribute(): string
    {
        return $this->district->regency->province->name;
    }

    /**
     * Determine if this is a village (desa) or urban village (kelurahan).
     * BPS classification pattern by zaidysf
     * Even numbers often indicate kelurahan, odd numbers indicate desa.
     */
    public function getTypeAttribute(): string
    {
        $lastDigit = (int) substr($this->code, -1);

        return $lastDigit % 2 === 0 ? 'kelurahan' : 'desa';
    }

    /**
     * Check if this is an urban village (kelurahan).
     */
    public function getIsKelurahanAttribute(): bool
    {
        return $this->type === 'kelurahan';
    }

    /**
     * Check if this is a village (desa).
     */
    public function getIsDesaAttribute(): bool
    {
        return $this->type === 'desa';
    }

    /**
     * Get village hierarchy information.
     */
    public function getHierarchyAttribute(): array
    {
        return [
            'village' => [
                'code' => $this->code,
                'name' => $this->name,
                'type' => $this->type,
            ],
            'district' => [
                'code' => $this->district_code,
                'name' => $this->district_name,
            ],
            'regency' => [
                'code' => $this->regency_code,
                'name' => $this->regency_name,
            ],
            'province' => [
                'code' => $this->province_code,
                'name' => $this->province_name,
            ],
        ];
    }
}
