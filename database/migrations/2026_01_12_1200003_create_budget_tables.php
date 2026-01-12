<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Budgets
        Schema::create('budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            
            $table->string('name');
            $table->integer('fiscal_year');
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->enum('status', ['draft', 'approved', 'closed'])->default('draft');
            
            $table->decimal('total_amount', 15, 2)->default(0);
            
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->unique(['company_id', 'name', 'fiscal_year']);
        });

        // Budget Lines
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('budget_id');
            $table->uuid('account_id');
            
            $table->integer('period'); // 1-12 for monthly, 1-4 for quarterly, 1 for yearly
            $table->decimal('budgeted_amount', 15, 2)->default(0);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->decimal('variance', 15, 2)->default(0);
            
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts');
            
            $table->unique(['budget_id', 'account_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
    }
};
