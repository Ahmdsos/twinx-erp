<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Branch;
use App\Models\Company;
use App\Scopes\TenantScope;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsToTenant Trait
 * 
 * Adds multi-tenant behavior to Eloquent models:
 * - Automatically filters queries by company_id and branch_id
 * - Automatically sets tenant IDs when creating records
 * - Provides relationships to Company and Branch
 * 
 * Usage:
 *   class Invoice extends Model {
 *       use BelongsToTenant;
 *   }
 * 
 * For company-only models (no branch filtering):
 *   class Customer extends Model {
 *       use BelongsToTenant;
 *       protected array $tenantColumns = ['company_id'];
 *   }
 */
trait BelongsToTenant
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToTenant(): void
    {
        // Add global scope for automatic filtering
        static::addGlobalScope(new TenantScope());

        // Automatically set tenant IDs when creating
        static::creating(function ($model) {
            $context = app(TenantContext::class);

            if (!$context->isSet()) {
                return;
            }

            // Set company_id if the column exists and is not already set
            if ($model->hasTenantColumn('company_id') && empty($model->company_id)) {
                $model->company_id = $context->companyId();
            }

            // Set branch_id if the column exists and is not already set
            if ($model->hasTenantColumn('branch_id') && empty($model->branch_id)) {
                $model->branch_id = $context->branchId();
            }
        });
    }

    /**
     * Initialize the trait for an instance.
     */
    public function initializeBelongsToTenant(): void
    {
        // Ensure tenant columns are in fillable
        $tenantColumns = $this->getTenantColumns();
        
        foreach ($tenantColumns as $column) {
            if (!in_array($column, $this->fillable ?? [], true)) {
                $this->fillable[] = $column;
            }
        }
    }

    /**
     * Check if model has a specific tenant column.
     */
    public function hasTenantColumn(string $column): bool
    {
        return in_array($column, $this->getTenantColumns(), true);
    }

    /**
     * Get tenant columns for this model.
     * Override in model to customize (e.g., company-only models).
     * 
     * @return array<string>
     */
    public function getTenantColumns(): array
    {
        return $this->tenantColumns ?? ['company_id', 'branch_id'];
    }

    /**
     * Relationship to Company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship to Branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope to filter by specific company (bypasses global scope).
     */
    public function scopeOfCompany($query, string $companyId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where($this->qualifyColumn('company_id'), $companyId);
    }

    /**
     * Scope to filter by specific branch (bypasses global scope).
     */
    public function scopeOfBranch($query, string $branchId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where($this->qualifyColumn('branch_id'), $branchId);
    }

    /**
     * Check if this record belongs to the current tenant context.
     */
    public function belongsToCurrentTenant(): bool
    {
        $context = app(TenantContext::class);

        if (!$context->isSet()) {
            return false;
        }

        $tenantColumns = $this->getTenantColumns();

        if (in_array('company_id', $tenantColumns, true)) {
            if ($this->company_id !== $context->companyId()) {
                return false;
            }
        }

        if (in_array('branch_id', $tenantColumns, true)) {
            if ($this->branch_id !== $context->branchId()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if this record belongs to the user's accessible tenants.
     */
    public function isAccessibleByUser($user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        if ($this->hasTenantColumn('branch_id')) {
            return $user->hasAccessToBranch($this->branch);
        }

        // For company-only models, check if user has any branch in this company
        return $user->branches()
            ->where('company_id', $this->company_id)
            ->exists();
    }
}
