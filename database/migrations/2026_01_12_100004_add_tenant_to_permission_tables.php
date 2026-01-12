<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Extends Spatie Permission tables to support multi-tenant RBAC.
     * Roles can be scoped to companies, and role assignments can be scoped to branches.
     */
    public function up(): void
    {
        // Add company_id to roles table (roles are company-specific or system-wide)
        Schema::table('roles', function (Blueprint $table) {
            $table->uuid('company_id')->nullable()->after('guard_name');
            $table->boolean('is_system_role')->default(false)->after('company_id');
            
            $table->index('company_id');
            $table->index('is_system_role');
        });

        // Add tenant context to model_has_roles pivot
        // This allows assigning roles to users within specific company/branch context
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->uuid('company_id')->nullable();
            $table->uuid('branch_id')->nullable();
            
            $table->index(['company_id', 'branch_id'], 'model_has_roles_tenant_idx');
        });

        // Add company_id to model_has_permissions for direct permission assignments
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->uuid('company_id')->nullable();
            $table->uuid('branch_id')->nullable();
            
            $table->index(['company_id', 'branch_id'], 'model_has_permissions_tenant_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropIndex('model_has_permissions_tenant_idx');
            $table->dropColumn(['company_id', 'branch_id']);
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex('model_has_roles_tenant_idx');
            $table->dropColumn(['company_id', 'branch_id']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['is_system_role']);
            $table->dropColumn(['company_id', 'is_system_role']);
        });
    }
};
