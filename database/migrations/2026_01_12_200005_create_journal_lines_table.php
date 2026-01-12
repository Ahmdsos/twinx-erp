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
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('journal_id');
            $table->uuid('account_id');
            $table->uuid('cost_center_id')->nullable();
            
            // Amounts in base currency
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            
            // Amounts in foreign currency (if applicable)
            $table->decimal('debit_fc', 20, 4)->default(0);
            $table->decimal('credit_fc', 20, 4)->default(0);
            $table->string('currency', 3)->nullable();
            $table->decimal('exchange_rate', 12, 6)->default(1);
            
            $table->string('description')->nullable();
            $table->integer('line_number')->default(0);
            
            // Sub-ledger references (Customer, Vendor, Employee, etc.)
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            
            // Additional tracking
            $table->date('due_date')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('journal_id')
                ->references('id')
                ->on('journals')
                ->onDelete('cascade');
            
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('restrict');
            
            $table->foreign('cost_center_id')
                ->references('id')
                ->on('cost_centers')
                ->onDelete('set null');
            
            // Indexes
            $table->index('journal_id');
            $table->index('account_id');
            $table->index(['reference_type', 'reference_id']);
            $table->index('cost_center_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
