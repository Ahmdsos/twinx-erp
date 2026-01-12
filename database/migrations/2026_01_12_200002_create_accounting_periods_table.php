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
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            
            $table->string('name'); // e.g., "January 2026"
            $table->string('name_ar')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('fiscal_year');
            $table->integer('period_number'); // 1-12 for monthly
            
            $table->string('status', 20)->default('open'); // PeriodStatus enum
            $table->timestamp('closed_at')->nullable();
            $table->uuid('closed_by')->nullable();
            
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            $table->foreign('closed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Indexes
            $table->unique(['company_id', 'fiscal_year', 'period_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
