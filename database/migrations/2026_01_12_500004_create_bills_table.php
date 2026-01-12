<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            
            $table->string('bill_number', 50);
            $table->string('supplier_ref')->nullable();
            $table->uuid('supplier_id');
            $table->uuid('purchase_order_id')->nullable();
            
            $table->date('bill_date');
            $table->date('due_date');
            
            $table->string('status', 20)->default('draft');
            
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            
            $table->string('currency', 3)->default('SAR');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            
            $table->uuid('journal_id')->nullable();
            $table->text('notes')->nullable();
            
            $table->uuid('created_by');
            $table->uuid('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
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
            
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->onDelete('set null');
            
            $table->foreign('journal_id')
                ->references('id')
                ->on('journals')
                ->onDelete('set null');
            
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
            
            $table->unique(['company_id', 'bill_number']);
            $table->index(['company_id', 'supplier_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
