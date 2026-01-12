<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('employee_id');
            
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            
            // Calculated
            $table->decimal('worked_hours', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            
            // Status
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'on_leave', 'holiday'])->default('present');
            
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->unique(['employee_id', 'date']);
            $table->index(['company_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
