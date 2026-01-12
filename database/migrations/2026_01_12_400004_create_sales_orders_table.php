<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            
            $table->string('order_number', 50);
            $table->uuid('customer_id');
            $table->uuid('price_list_id')->nullable();
            
            // Dates
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->date('expiry_date')->nullable();
            
            // Status
            $table->string('status', 20)->default('draft');
            
            // Amounts
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            
            // Currency
            $table->string('currency', 3)->default('SAR');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            
            // Delivery
            $table->uuid('warehouse_id')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            
            // Audit
            $table->uuid('created_by');
            $table->uuid('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            
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
            
            $table->foreign('price_list_id')
                ->references('id')
                ->on('price_lists')
                ->onDelete('set null');
            
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('set null');
            
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
            
            $table->foreign('confirmed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Indexes
            $table->unique(['company_id', 'order_number']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
