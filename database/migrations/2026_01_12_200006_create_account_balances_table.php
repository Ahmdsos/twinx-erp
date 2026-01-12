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
        Schema::create('account_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('period_id');
            $table->uuid('branch_id')->nullable();
            
            // Opening balance (carried forward from previous period)
            $table->decimal('opening_debit', 20, 4)->default(0);
            $table->decimal('opening_credit', 20, 4)->default(0);
            
            // Period movements (sum of posted journal lines)
            $table->decimal('period_debit', 20, 4)->default(0);
            $table->decimal('period_credit', 20, 4)->default(0);
            
            // Closing balance (calculated: opening + period movements)
            $table->decimal('closing_debit', 20, 4)->default(0);
            $table->decimal('closing_credit', 20, 4)->default(0);
            
            // YTD (Year to Date) balance
            $table->decimal('ytd_debit', 20, 4)->default(0);
            $table->decimal('ytd_credit', 20, 4)->default(0);
            
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('cascade');
            
            $table->foreign('period_id')
                ->references('id')
                ->on('accounting_periods')
                ->onDelete('cascade');
            
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');
            
            // Unique constraint - one balance record per account/period/branch
            $table->unique(['account_id', 'period_id', 'branch_id'], 'account_balances_unique');
            
            // Indexes
            $table->index('period_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_balances');
    }
};
