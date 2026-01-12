<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            
            $table->string('payment_number', 50);
            $table->uuid('supplier_id');
            $table->uuid('bill_id')->nullable();
            
            $table->date('payment_date');
            $table->string('payment_method', 20);
            
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('SAR');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            
            $table->string('reference')->nullable();
            $table->uuid('bank_account_id')->nullable();
            $table->text('notes')->nullable();
            
            $table->uuid('journal_id')->nullable();
            
            $table->uuid('created_by');
            $table->timestamps();
            
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');
            
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers')
                ->onDelete('restrict');
            
            $table->foreign('bill_id')
                ->references('id')
                ->on('bills')
                ->onDelete('set null');
            
            $table->foreign('journal_id')
                ->references('id')
                ->on('journals')
                ->onDelete('set null');
            
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
            
            $table->unique(['company_id', 'payment_number']);
            $table->index(['company_id', 'supplier_id']);
            $table->index(['company_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
