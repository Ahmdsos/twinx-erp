<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // UUID Primary Key
            $table->uuid('id')->primary();
            
            // Basic Info
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            
            // TWINX ERP Tenant Fields
            $table->uuid('current_company_id')->nullable();
            $table->uuid('current_branch_id')->nullable();
            
            // Super Admin Flag (bypasses all tenant scopes)
            $table->boolean('is_super_admin')->default(false);
            
            // Preferences
            $table->string('preferred_language', 5)->default('ar');
            $table->string('preferred_timezone', 50)->nullable();
            
            // Profile
            $table->string('phone', 20)->nullable();
            $table->string('avatar_path')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('is_active');
            $table->index('is_super_admin');
            $table->index(['current_company_id', 'current_branch_id']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
