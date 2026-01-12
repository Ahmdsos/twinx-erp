<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            
            $table->string('invoice_number', 50);
            $table->string('type', 20)->default('sales');
            $table->uuid('customer_id');
            $table->uuid('sales_order_id')->nullable();
            
            // Dates
            $table->date('invoice_date');
            $table->date('due_date');
            
            // Status
            $table->string('status', 20)->default('draft');
            
            // Amounts
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            
            // Currency
            $table->string('currency', 3)->default('SAR');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            
            // Accounting
            $table->uuid('journal_id')->nullable();
            
            $table->text('notes')->nullable();
            
            // Audit
            $table->uuid('created_by');
            $table->uuid('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            
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
            
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('restrict');
            
            $table->foreign('sales_order_id')
                ->references('id')
                ->on('sales_orders')
                ->onDelete('set null');
            
            $table->foreign('journal_id')
                ->references('id')
                ->on('journals')
                ->onDelete('set null');
            
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
            
            $table->foreign('issued_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Indexes
            $table->unique(['company_id', 'invoice_number']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'invoice_date']);
            $table->index(['company_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
