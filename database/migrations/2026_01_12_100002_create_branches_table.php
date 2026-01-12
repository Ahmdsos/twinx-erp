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
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            
            // Basic Info
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('code', 20); // e.g., "RYD-001", "JED-002"
            $table->enum('type', ['headquarters', 'branch', 'warehouse', 'pos'])->default('branch');
            
            // Contact
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Settings
            $table->string('timezone', 50)->nullable(); // Inherits from company if null
            $table->json('settings')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign Keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            // Indexes
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
            $table->index('code');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
