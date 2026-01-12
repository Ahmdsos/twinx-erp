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
        Schema::create('journals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('period_id');
            
            // Identification
            $table->string('reference', 50);
            $table->string('type', 20); // JournalType enum
            $table->date('transaction_date');
            $table->date('posting_date')->nullable();
            
            // Status
            $table->string('status', 20)->default('draft'); // JournalStatus enum
            
            // Amounts (for quick access without calculating lines)
            $table->decimal('total_debit', 20, 4)->default(0);
            $table->decimal('total_credit', 20, 4)->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            
            // Description
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            
            // Source document reference (polymorphic)
            $table->string('source_type')->nullable();
            $table->uuid('source_id')->nullable();
            
            // Reversal reference
            $table->uuid('reversed_by_id')->nullable();
            $table->uuid('reversal_of_id')->nullable();
            
            // Audit
            $table->uuid('created_by');
            $table->uuid('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->uuid('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign Keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');
            
            $table->foreign('period_id')
                ->references('id')
                ->on('accounting_periods')
                ->onDelete('restrict');
            
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
            
            $table->foreign('posted_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Indexes
            $table->unique(['company_id', 'reference']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['company_id', 'transaction_date']);
            $table->index(['company_id', 'period_id']);
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
