<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

/**
 * Company Model
 * 
 * Represents a company/organization in the TWINX ERP system.
 * Each company can have multiple branches and its own set of users, roles, and data.
 * 
 * @property string $id UUID
 * @property string $name
 * @property string|null $name_ar
 * @property string|null $legal_name
 * @property string|null $tax_number
 * @property string|null $commercial_register
 * @property string $base_currency
 * @property string $fiscal_year_start
 * @property string $timezone
 * @property string $default_language
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $city
 * @property string $country
 * @property string|null $logo_path
 * @property bool $is_active
 * @property array|null $settings
 */
class Company extends Model implements Auditable
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use AuditableTrait;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'name_ar',
        'legal_name',
        'tax_number',
        'commercial_register',
        'base_currency',
        'fiscal_year_start',
        'timezone',
        'default_language',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'logo_path',
        'is_active',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * Get all branches for this company.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get active branches for this company.
     */
    public function activeBranches(): HasMany
    {
        return $this->branches()->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get the headquarters branch.
     */
    public function headquarters(): ?Branch
    {
        return $this->branches()->where('type', 'headquarters')->first();
    }

    /**
     * Get all roles specific to this company.
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
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
     * Check if company is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get a specific setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a specific setting value.
     */
    public function setSetting(string $key, mixed $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        
        return $this;
    }
}
