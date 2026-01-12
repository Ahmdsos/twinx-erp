<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drivers
        Schema::create('drivers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('employee_id')->nullable();
            
            $table->string('driver_number', 20);
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('phone', 20);
            $table->string('email')->nullable();
            
            $table->string('license_number')->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('id_number', 10)->nullable();
            
            $table->enum('status', ['available', 'on_delivery', 'off_duty'])->default('available');
            $table->decimal('commission_rate', 5, 2)->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('employee_id')->references('id')->on('employees');
            
            $table->unique(['company_id', 'driver_number']);
            $table->index(['company_id', 'status', 'is_active']);
        });

        // Vehicles
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            
            $table->string('plate_number', 20);
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->integer('year')->nullable();
            $table->string('color')->nullable();
            $table->enum('type', ['car', 'van', 'truck', 'motorcycle'])->default('car');
            
            $table->uuid('assigned_driver_id')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('assigned_driver_id')->references('id')->on('drivers');
            
            $table->unique(['company_id', 'plate_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('drivers');
    }
};
