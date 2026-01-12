<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

/**
 * Branch Model
 * 
 * Represents a branch/location within a company.
 * Branches are the primary unit for data isolation in TWINX ERP.
 * 
 * @property string $id UUID
 * @property string $company_id
 * @property string $name
 * @property string|null $name_ar
 * @property string $code
 * @property string $type headquarters|branch|warehouse|pos
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $city
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $timezone
 * @property array|null $settings
 * @property bool $is_active
 * @property int $sort_order
 */
class Branch extends Model implements Auditable
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use AuditableTrait;

    /**
     * Branch types
     */
    public const TYPE_HEADQUARTERS = 'headquarters';
    public const TYPE_BRANCH = 'branch';
    public const TYPE_WAREHOUSE = 'warehouse';
    public const TYPE_POS = 'pos';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'name_ar',
        'code',
        'type',
        'phone',
        'address',
        'city',
        'latitude',
        'longitude',
        'timezone',
        'settings',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the company that owns this branch.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all users assigned to this branch.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branches')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * Get display name (Arabic if available, otherwise English).
     */
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        
        if ($locale === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }
        
        return $this->name;
    }

    /**
     * Get the effective timezone (branch or company fallback).
     */
    public function getEffectiveTimezoneAttribute(): string
    {
        return $this->timezone ?? $this->company->timezone ?? 'Asia/Riyadh';
    }

    /**
     * Check if this is the headquarters.
     */
    public function isHeadquarters(): bool
    {
        return $this->type === self::TYPE_HEADQUARTERS;
    }

    /**
     * Check if branch is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->company->is_active;
    }

    /**
     * Get a specific setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        // First check branch settings, then fall back to company settings
        $branchValue = data_get($this->settings, $key);
        
        if ($branchValue !== null) {
            return $branchValue;
        }
        
        return $this->company->getSetting($key, $default);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter active branches.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
