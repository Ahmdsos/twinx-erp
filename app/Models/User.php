<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\TenantContext;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model
 * 
 * TWINX ERP User with multi-tenant support and branch-scoped RBAC.
 * 
 * @property string $id UUID
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property string|null $current_company_id
 * @property string|null $current_branch_id
 * @property bool $is_super_admin
 * @property string $preferred_language
 * @property string|null $preferred_timezone
 * @property string|null $phone
 * @property string|null $avatar_path
 * @property bool $is_active
 * @property string|null $last_login_at
 */
class User extends Authenticatable implements Auditable
{
    use HasFactory;
    use HasUuid;
    use Notifiable;
    use SoftDeletes;
    use HasRoles;
    use AuditableTrait;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_company_id',
        'current_branch_id',
        'is_super_admin',
        'preferred_language',
        'preferred_timezone',
        'phone',
        'avatar_path',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the current company.
     */
    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    /**
     * Get the current branch.
     */
    public function currentBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'current_branch_id');
    }

    /**
     * Get all branches assigned to this user.
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branches')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * Get user's default branch.
     */
    public function defaultBranch(): ?Branch
    {
        return $this->branches()->wherePivot('is_default', true)->first();
    }

    // =========================================================================
    // TENANT CONTEXT METHODS
    // =========================================================================

    /**
     * Switch to a different branch.
     */
    public function switchToBranch(Branch $branch): bool
    {
        // Verify user has access to this branch
        if (!$this->is_super_admin && !$this->hasAccessToBranch($branch)) {
            return false;
        }

        $this->update([
            'current_company_id' => $branch->company_id,
            'current_branch_id' => $branch->id,
        ]);

        return true;
    }

    /**
     * Check if user has access to a specific branch.
     */
    public function hasAccessToBranch(Branch $branch): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->branches()->where('branches.id', $branch->id)->exists();
    }

    /**
     * Get all companies the user has access to.
     */
    public function accessibleCompanies(): Collection
    {
        if ($this->is_super_admin) {
            return Company::where('is_active', true)->get();
        }

        return $this->branches()
            ->with('company')
            ->get()
            ->pluck('company')
            ->unique('id')
            ->values();
    }

    /**
     * Get all branches the user has access to in a specific company.
     */
    public function accessibleBranchesInCompany(string $companyId): Collection
    {
        if ($this->is_super_admin) {
            return Branch::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        return $this->branches()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    // =========================================================================
    // RBAC METHODS (Branch-Scoped)
    // =========================================================================

    /**
     * Check if user has a role in a specific branch.
     */
    public function hasRoleInBranch(string $roleName, ?string $branchId = null): bool
    {
        $branchId = $branchId ?? app(TenantContext::class)->branchId();

        return $this->roles()
            ->where('name', $roleName)
            ->wherePivot('branch_id', $branchId)
            ->exists();
    }

    /**
     * Assign a role to user in a specific branch.
     */
    public function assignRoleInBranch($role, string $branchId): void
    {
        $roleModel = is_string($role)
            ? Role::findByNameInContext($role)
            : $role;

        if (!$roleModel) {
            throw new \InvalidArgumentException("Role '{$role}' not found in current context.");
        }

        $branch = Branch::findOrFail($branchId);

        // Use syncWithoutDetaching to avoid duplicates
        $this->roles()->syncWithoutDetaching([
            $roleModel->id => [
                'company_id' => $branch->company_id,
                'branch_id' => $branchId,
            ],
        ]);
    }

    /**
     * Remove a role from user in a specific branch.
     */
    public function removeRoleInBranch(string $roleName, string $branchId): void
    {
        $role = Role::findByNameInContext($roleName);

        if ($role) {
            $this->roles()
                ->wherePivot('branch_id', $branchId)
                ->detach($role->id);
        }
    }

    /**
     * Get user's roles in current branch context.
     */
    public function rolesInCurrentBranch(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        $context = app(TenantContext::class);

        return $this->roles()
            ->wherePivot('company_id', $context->companyId())
            ->wherePivot('branch_id', $context->branchId());
    }

    /**
     * Get user's permissions in current context.
     */
    public function getPermissionsInContext(): Collection
    {
        return $this->rolesInCurrentBranch()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    /**
     * Check if user has a permission in current context.
     */
    public function hasPermissionInContext(string $permissionName): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->getPermissionsInContext()
            ->contains('name', $permissionName);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if user is super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin;
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Update last login timestamp.
     */
    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Get the effective timezone for this user.
     */
    public function getEffectiveTimezoneAttribute(): string
    {
        return $this->preferred_timezone
            ?? $this->currentBranch?->effective_timezone
            ?? config('app.timezone');
    }
}
