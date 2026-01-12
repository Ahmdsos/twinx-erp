<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * TenantContext Service
 * 
 * Singleton service that holds the current tenant context (Company + Branch).
 * Used by TenantScope and BelongsToTenant trait to filter queries.
 * 
 * Context can be set from:
 * - API Headers (X-Company-ID, X-Branch-ID)
 * - Session (for web users)
 * - User's current_company_id/current_branch_id
 */
class TenantContext
{
    private ?Company $company = null;
    private ?Branch $branch = null;
    private ?User $user = null;
    private bool $bypassTenantScopes = false;

    /**
     * Set the current tenant context from Company and Branch.
     */
    public function set(Company $company, Branch $branch): void
    {
        $this->company = $company;
        $this->branch = $branch;
    }

    /**
     * Set context from IDs (lazy loads models).
     */
    public function setFromIds(?string $companyId, ?string $branchId): bool
    {
        if (!$companyId || !$branchId) {
            return false;
        }

        $company = Company::find($companyId);
        $branch = Branch::find($branchId);

        if (!$company || !$branch) {
            return false;
        }

        // Validate branch belongs to company
        if ($branch->company_id !== $company->id) {
            return false;
        }

        $this->set($company, $branch);
        return true;
    }

    /**
     * Set context from authenticated user.
     */
    public function setFromUser(Authenticatable $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        $this->user = $user;

        if ($user->current_company_id && $user->current_branch_id) {
            return $this->setFromIds($user->current_company_id, $user->current_branch_id);
        }

        // Try to use user's default branch
        $defaultBranch = $user->defaultBranch();
        if ($defaultBranch) {
            $this->set($defaultBranch->company, $defaultBranch);
            return true;
        }

        return false;
    }

    /**
     * Get current company.
     */
    public function company(): ?Company
    {
        return $this->company;
    }

    /**
     * Get current company ID.
     */
    public function companyId(): ?string
    {
        return $this->company?->id;
    }

    /**
     * Get current branch.
     */
    public function branch(): ?Branch
    {
        return $this->branch;
    }

    /**
     * Get current branch ID.
     */
    public function branchId(): ?string
    {
        return $this->branch?->id;
    }

    /**
     * Get current user.
     */
    public function user(): ?User
    {
        return $this->user;
    }

    /**
     * Check if tenant context is fully set.
     */
    public function isSet(): bool
    {
        return $this->company !== null && $this->branch !== null;
    }

    /**
     * Check if user is Super Admin (bypasses all tenant scopes).
     */
    public function isSuperAdmin(): bool
    {
        return $this->user?->is_super_admin ?? false;
    }

    /**
     * Temporarily bypass tenant scopes for admin operations.
     * 
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function bypassScopes(callable $callback): mixed
    {
        $previousState = $this->bypassTenantScopes;
        $this->bypassTenantScopes = true;

        try {
            return $callback();
        } finally {
            $this->bypassTenantScopes = $previousState;
        }
    }

    /**
     * Check if scopes should be bypassed.
     */
    public function shouldBypassScopes(): bool
    {
        return $this->bypassTenantScopes || $this->isSuperAdmin();
    }

    /**
     * Clear the context.
     */
    public function clear(): void
    {
        $this->company = null;
        $this->branch = null;
        $this->user = null;
        $this->bypassTenantScopes = false;
    }

    /**
     * Get context as array (for debugging/logging).
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId(),
            'company_name' => $this->company?->name,
            'branch_id' => $this->branchId(),
            'branch_name' => $this->branch?->name,
            'user_id' => $this->user?->id,
            'is_super_admin' => $this->isSuperAdmin(),
            'bypass_scopes' => $this->bypassTenantScopes,
        ];
    }

    /**
     * Validate that the user has access to the current context.
     */
    public function validateUserAccess(): bool
    {
        if (!$this->user || !$this->isSet()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->user->hasAccessToBranch($this->branch);
    }
}
