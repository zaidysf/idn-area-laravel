<?php

declare(strict_types=1);

namespace zaidysf\IdnArea\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Indonesian Island Model
 *
 * @property int $id Auto-increment ID
 * @property string|null $code Island code (optional)
 * @property string|null $coordinate Geographic coordinates (optional)
 * @property string $name Island name
 * @property bool $is_outermost_small Boolean flag for outermost small islands
 * @property bool $is_populated Boolean flag for populated status
 * @property string|null $regency_code Reference to regency (optional)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Regency|null $regency
 * @property-read Province|null $province
 */
class Island extends Model
{
    /** @use HasFactory<\zaidysf\IdnArea\Database\Factories\IslandFactory> */
    use HasFactory;

    protected $table = 'idn_islands';

    protected $fillable = [
        'code',
        'coordinate',
        'name',
        'is_outermost_small',
        'is_populated',
        'regency_code',
    ];

    protected $casts = [
        'code' => 'string',
        'coordinate' => 'string',
        'name' => 'string',
        'is_outermost_small' => 'boolean',
        'is_populated' => 'boolean',
        'regency_code' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \zaidysf\IdnArea\Database\Factories\IslandFactory
    {
        return \zaidysf\IdnArea\Database\Factories\IslandFactory::new();
    }

    /**
     * Get the regency that owns the island.
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
            'regency_code',  // Local key on islands table
            'province_code'  // Local key on regencies table
        );
    }

    /**
     * Scope to search islands by name.
     *
     * @param  Builder<Island>  $query
     * @return Builder<Island>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    /**
     * Scope to filter by regency.
     *
     * @param  Builder<Island>  $query
     * @return Builder<Island>
     */
    public function scopeInRegency(Builder $query, string $regencyCode): Builder
    {
        return $query->where('regency_code', $regencyCode);
    }

    /**
     * Scope to filter by province through regency.
     *
     * @param  Builder<Island>  $query
     * @return Builder<Island>
     */
    public function scopeInProvince(Builder $query, string $provinceCode): Builder
    {
        return $query->whereHas('regency', function ($q) use ($provinceCode) {
            $q->where('province_code', $provinceCode);
        });
    }

    /**
     * Scope a query to only include outermost small islands.
     *
     * @param  Builder<Island>  $query
     * @return Builder<Island>
     */
    public function scopeOutermostSmall(Builder $query): Builder
    {
        return $query->where('is_outermost_small', true);
    }

    /**
     * Scope a query to only include populated islands.
     *
     * @param  Builder<Island>  $query
     * @return Builder<Island>
     */
    public function scopePopulated(Builder $query): Builder
    {
        return $query->where('is_populated', true);
    }

    /**
     * Scope a query to only include unpopulated islands.
     *
     * @param  Builder<Island>  $query
     * @return Builder<Island>
     */
    public function scopeUnpopulated(Builder $query): Builder
    {
        return $query->where('is_populated', false);
    }

    /**
     * Scope to get islands with coordinates.
     *
     * @param  Builder<Island>  $query
     * @return Builder<Island>
     */
    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->whereNotNull('coordinate');
    }

    /**
     * Scope to get islands without coordinates.
     *
     * @param  Builder<Island>  $query
     * @return Builder<Island>
     */
    public function scopeWithoutCoordinates(Builder $query): Builder
    {
        return $query->whereNull('coordinate');
    }

    /**
     * Scope to get islands with codes.
     *
     * @param  Builder<Island>  $query
     * @return Builder<Island>
     */
    public function scopeWithCode(Builder $query): Builder
    {
        return $query->whereNotNull('code');
    }

    /**
     * Scope to get islands without codes.
     *
     * @param  Builder<Island>  $query
     * @return Builder<Island>
     */
    public function scopeWithoutCode(Builder $query): Builder
    {
        return $query->whereNull('code');
    }

    /**
     * Scope to get islands that belong to a regency.
     *
     * @param  Builder<Island>  $query
     * @return Builder<Island>
     */
    public function scopeWithRegency(Builder $query): Builder
    {
        return $query->whereNotNull('regency_code');
    }

    /**
     * Scope to get islands that don't belong to any regency.
     *
     * @param  Builder<Island>  $query
     * @return Builder<Island>
     */
    public function scopeWithoutRegency(Builder $query): Builder
    {
        return $query->whereNull('regency_code');
    }

    /**
     * Get formatted name with status indicators.
     */
    public function getFormattedNameAttribute(): string
    {
        $indicators = [];

        if ($this->is_outermost_small) {
            $indicators[] = 'Outermost Small';
        }

        if ($this->is_populated) {
            $indicators[] = 'Populated';
        }

        $name = $this->name;

        if (! empty($indicators)) {
            $name .= ' ('.implode(', ', $indicators).')';
        }

        return $name;
    }

    /**
     * Get full formatted name with regency and province.
     */
    public function getFullFormattedNameAttribute(): string
    {
        $parts = [$this->formatted_name];

        if ($this->regency !== null) {
            $parts[] = $this->regency->name;

            if ($this->regency->province !== null) {
                $parts[] = $this->regency->province->name;
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Get coordinate array from coordinate string.
     */
    public function getCoordinateArrayAttribute(): ?array
    {
        if (! $this->coordinate) {
            return null;
        }

        // Assume coordinate is in "lat,lng" format
        $parts = explode(',', $this->coordinate);

        if (count($parts) !== 2) {
            return null;
        }

        return [
            'latitude' => (float) trim($parts[0]),
            'longitude' => (float) trim($parts[1]),
        ];
    }

    /**
     * Get status information about the island.
     */
    public function getStatusAttribute(): array
    {
        return [
            'has_code' => ! is_null($this->code),
            'has_coordinates' => ! is_null($this->coordinate),
            'has_regency' => ! is_null($this->regency_code),
            'is_outermost_small' => $this->is_outermost_small,
            'is_populated' => $this->is_populated,
        ];
    }

    /**
     * Get the regency name if exists.
     */
    public function getRegencyNameAttribute(): ?string
    {
        return $this->regency?->name;
    }

    /**
     * Get the province name if exists.
     */
    public function getProvinceNameAttribute(): ?string
    {
        return $this->regency?->province?->name;
    }

    /**
     * Check if island is strategically important (outermost small islands are typically strategic).
     */
    public function getIsStrategicAttribute(): bool
    {
        return $this->is_outermost_small;
    }

    /**
     * Get island type based on characteristics.
     */
    public function getTypeAttribute(): string
    {
        if ($this->is_outermost_small && $this->is_populated) {
            return 'Populated Outermost Small Island';
        }

        if ($this->is_outermost_small) {
            return 'Outermost Small Island';
        }

        if ($this->is_populated) {
            return 'Populated Island';
        }

        return 'Island';
    }
}
