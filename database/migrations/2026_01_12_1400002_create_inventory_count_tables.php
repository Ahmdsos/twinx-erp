<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Inventory Counts (Physical Count Sessions)
        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('warehouse_id');
            
            $table->string('count_number');
            $table->date('count_date');
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');
            
            $table->text('notes')->nullable();
            
            $table->uuid('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('completed_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->unique(['company_id', 'count_number']);
        });

        // Inventory Count Lines
        Schema::create('inventory_count_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_count_id');
            $table->uuid('product_id');
            $table->uuid('batch_id')->nullable();
            
            $table->decimal('system_quantity', 12, 3);
            $table->decimal('counted_quantity', 12, 3)->nullable();
            $table->decimal('variance', 12, 3)->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('inventory_count_id')->references('id')->on('inventory_counts')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('batch_id')->references('id')->on('batches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_count_lines');
        Schema::dropIfExists('inventory_counts');
    }
};
