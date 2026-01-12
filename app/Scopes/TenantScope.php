<?php

declare(strict_types=1);

namespace App\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * TenantScope
 * 
 * Global scope that automatically filters queries by company_id and branch_id.
 * Applied to all models using the BelongsToTenant trait.
 * 
 * Can be bypassed by:
 * - Super Admin users
 * - Explicit bypass via TenantContext::bypassScopes()
 * - Using ->withoutTenantScope() on queries
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        // Skip if bypassing scopes (Super Admin or explicit bypass)
        if ($context->shouldBypassScopes()) {
            return;
        }

        // Skip if context is not set
        if (!$context->isSet()) {
            return;
        }

        $tenantColumns = $model->getTenantColumns();

        // Apply company filter
        if (in_array('company_id', $tenantColumns, true)) {
            $builder->where(
                $model->qualifyColumn('company_id'),
                $context->companyId()
            );
        }

        // Apply branch filter
        if (in_array('branch_id', $tenantColumns, true)) {
            $builder->where(
                $model->qualifyColumn('branch_id'),
                $context->branchId()
            );
        }
    }

    /**
     * Extend the builder with custom methods.
     */
    public function extend(Builder $builder): void
    {
        // Add withoutTenantScope() method to remove this scope
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope(TenantScope::class);
        });

        // Add allBranches() method for company-wide queries
        $builder->macro('allBranches', function (Builder $builder) {
            $context = app(TenantContext::class);
            $model = $builder->getModel();

            // Remove both company and branch filters
            $builder = $builder->withoutGlobalScope(TenantScope::class);

            // Re-apply only company filter
            if (in_array('company_id', $model->getTenantColumns(), true)) {
                $builder->where(
                    $model->qualifyColumn('company_id'),
                    $context->companyId()
                );
            }

            return $builder;
        });

        // Add forBranch() method to query specific branch
        $builder->macro('forBranch', function (Builder $builder, string $branchId) {
            $model = $builder->getModel();
            $context = app(TenantContext::class);

            // Remove default scope and apply specific branch
            $builder = $builder->withoutGlobalScope(TenantScope::class);

            if (in_array('company_id', $model->getTenantColumns(), true)) {
                $builder->where($model->qualifyColumn('company_id'), $context->companyId());
            }

            if (in_array('branch_id', $model->getTenantColumns(), true)) {
                $builder->where($model->qualifyColumn('branch_id'), $branchId);
            }

            return $builder;
        });

        // Add forCompany() method to query specific company (admin use)
        $builder->macro('forCompany', function (Builder $builder, string $companyId) {
            $model = $builder->getModel();

            $builder = $builder->withoutGlobalScope(TenantScope::class);

            if (in_array('company_id', $model->getTenantColumns(), true)) {
                $builder->where($model->qualifyColumn('company_id'), $companyId);
            }

            return $builder;
        });
    }
}
