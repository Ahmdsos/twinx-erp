<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\TenantContext;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Role Model
 * 
 * Extends Spatie's Role to support company-scoped roles.
 * Roles can be:
 * - System roles (is_system_role = true): Available to all companies
 * - Company roles (company_id set): Only available within that company
 * 
 * @property string $id
 * @property string $name
 * @property string $guard_name
 * @property string|null $company_id
 * @property bool $is_system_role
 */
class Role extends SpatieRole
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'guard_name',
        'company_id',
        'is_system_role',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_system_role' => 'boolean',
    ];

    /**
     * Get the company that owns this role.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope to get roles available in current tenant context.
     * Returns company-specific roles + system roles.
     */
    public function scopeForCurrentCompany($query)
    {
        $context = app(TenantContext::class);
        
        return $query->where(function ($q) use ($context) {
            $q->where('company_id', $context->companyId())
              ->orWhere('is_system_role', true);
        });
    }

    /**
     * Scope to get only system roles.
     */
    public function scopeSystemRoles($query)
    {
        return $query->where('is_system_role', true);
    }

    /**
     * Scope to get only company-specific roles.
     */
    public function scopeCompanyRoles($query, ?string $companyId = null)
    {
        $companyId = $companyId ?? app(TenantContext::class)->companyId();
        
        return $query->where('company_id', $companyId);
    }

    /**
     * Check if this is a system role.
     */
    public function isSystemRole(): bool
    {
        return $this->is_system_role;
    }

    /**
     * Check if this role belongs to a specific company.
     */
    public function belongsToCompany(?string $companyId = null): bool
    {
        $companyId = $companyId ?? app(TenantContext::class)->companyId();
        
        return $this->company_id === $companyId;
    }

    /**
     * Find a role by name within current company context.
     */
    public static function findByNameInContext(string $name, ?string $guardName = null): ?static
    {
        $guardName = $guardName ?? config('auth.defaults.guard');
        $context = app(TenantContext::class);
        
        return static::query()
            ->where('name', $name)
            ->where('guard_name', $guardName)
            ->where(function ($query) use ($context) {
                $query->where('company_id', $context->companyId())
                      ->orWhere('is_system_role', true);
            })
            ->first();
    }
}
