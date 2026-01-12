<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Serial Numbers
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('product_id');
            $table->uuid('warehouse_id')->nullable();
            
            $table->string('serial_number');
            $table->enum('status', ['available', 'reserved', 'sold', 'returned', 'damaged'])->default('available');
            
            $table->uuid('purchase_line_id')->nullable();
            $table->uuid('sale_line_id')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            
            $table->unique(['company_id', 'product_id', 'serial_number'], 'serial_unique');
            $table->index(['company_id', 'status']);
        });

        // Batches / Lots
        Schema::create('batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('product_id');
            $table->uuid('warehouse_id');
            
            $table->string('batch_number');
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            
            $table->decimal('initial_quantity', 12, 3)->default(0);
            $table->decimal('current_quantity', 12, 3)->default(0);
            $table->decimal('cost_per_unit', 12, 2)->nullable();
            
            $table->boolean('is_expired')->default(false);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            
            $table->unique(['company_id', 'product_id', 'batch_number'], 'batch_unique');
            $table->index(['company_id', 'expiry_date']);
        });

        // Reorder Rules
        Schema::create('reorder_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('product_id');
            $table->uuid('warehouse_id')->nullable();
            
            $table->decimal('min_quantity', 12, 3);
            $table->decimal('reorder_quantity', 12, 3);
            $table->decimal('max_quantity', 12, 3)->nullable();
            
            $table->uuid('preferred_supplier_id')->nullable();
            $table->integer('lead_time_days')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('preferred_supplier_id')->references('id')->on('suppliers');
            
            $table->unique(['company_id', 'product_id', 'warehouse_id'], 'reorder_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_rules');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('serial_numbers');
    }
};
