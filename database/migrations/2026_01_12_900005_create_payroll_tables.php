<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payroll Periods
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('pay_date')->nullable();
            
            $table->enum('status', ['open', 'processing', 'closed'])->default('open');
            
            $table->uuid('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('closed_by')->references('id')->on('users');
            
            $table->unique(['company_id', 'start_date', 'end_date']);
        });

        // Payslips
        Schema::create('payslips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('employee_id');
            $table->uuid('period_id');
            
            $table->string('payslip_number');
            
            // Earnings
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('housing_allowance', 10, 2)->default(0);
            $table->decimal('transport_allowance', 10, 2)->default(0);
            $table->decimal('other_allowance', 10, 2)->default(0);
            $table->decimal('overtime_amount', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->decimal('total_earnings', 12, 2)->default(0);
            
            // Deductions
            $table->decimal('gosi_employee', 10, 2)->default(0);  // 10%
            $table->decimal('gosi_company', 10, 2)->default(0);   // 12%
            $table->decimal('loan_deduction', 10, 2)->default(0);
            $table->decimal('other_deduction', 10, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            
            // Net
            $table->decimal('net_salary', 12, 2)->default(0);
            
            // Status
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('period_id')->references('id')->on('payroll_periods');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->unique(['company_id', 'payslip_number']);
            $table->unique(['employee_id', 'period_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_periods');
    }
};
