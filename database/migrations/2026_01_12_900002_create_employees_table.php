<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('department_id')->nullable();
            $table->uuid('user_id')->nullable();
            
            // Employee Number
            $table->string('employee_number', 20);
            
            // Personal Info
            $table->string('first_name');
            $table->string('last_name');
            $table->string('first_name_ar')->nullable();
            $table->string('last_name_ar')->nullable();
            $table->string('national_id', 10);
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->string('nationality', 2)->default('SA');
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->default('single');
            
            // Contact
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            
            // Employment
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('job_title');
            $table->string('job_title_ar')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract'])->default('full_time');
            
            // Salary
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('housing_allowance', 10, 2)->default(0);
            $table->decimal('transport_allowance', 10, 2)->default(0);
            $table->decimal('other_allowance', 10, 2)->default(0);
            
            // GOSI (التأمينات الاجتماعية)
            $table->string('gosi_number', 20)->nullable();
            $table->boolean('gosi_enrolled')->default(false);
            
            // Bank
            $table->string('bank_name')->nullable();
            $table->string('iban', 34)->nullable();
            
            // Documents
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry')->nullable();
            $table->string('work_permit_number')->nullable();
            $table->date('work_permit_expiry')->nullable();
            
            // Leave Balance
            $table->decimal('annual_leave_balance', 5, 2)->default(0);
            $table->decimal('sick_leave_balance', 5, 2)->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('user_id')->references('id')->on('users');
            
            $table->unique(['company_id', 'employee_number']);
            $table->index(['company_id', 'is_active']);
            $table->index('national_id');
        });

        // Update departments manager_id FK after employees exists
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('employees');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });
        Schema::dropIfExists('employees');
    }
};
