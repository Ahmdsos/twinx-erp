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
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Basic Info
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('commercial_register', 50)->nullable();
            
            // Settings
            $table->string('base_currency', 3)->default('SAR');
            $table->string('fiscal_year_start', 5)->default('01-01'); // MM-DD format
            $table->string('timezone', 50)->default('Asia/Riyadh');
            $table->string('default_language', 5)->default('ar');
            
            // Contact
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 2)->default('SA'); // ISO 3166-1 alpha-2
            
            // Branding
            $table->string('logo_path')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Flexible settings JSON
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('is_active');
            $table->index('tax_number');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
