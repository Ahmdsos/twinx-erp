<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            
            $table->string('order_number', 50);
            $table->uuid('supplier_id');
            
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            
            $table->string('status', 20)->default('draft');
            
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            
            $table->string('currency', 3)->default('SAR');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            
            $table->uuid('warehouse_id')->nullable();
            $table->text('notes')->nullable();
            
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
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
            
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('set null');
            
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
            
            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            $table->unique(['company_id', 'order_number']);
            $table->index(['company_id', 'supplier_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
