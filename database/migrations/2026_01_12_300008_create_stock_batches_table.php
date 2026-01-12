<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('warehouse_id');
            
            $table->string('batch_number', 50)->nullable();
            $table->date('received_date');
            $table->date('expiry_date')->nullable();
            
            $table->decimal('initial_qty', 15, 4);
            $table->decimal('remaining_qty', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            
            $table->uuid('movement_id');
            
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('cascade');
            
            $table->foreign('movement_id')
                ->references('id')
                ->on('stock_movements')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['product_id', 'warehouse_id', 'remaining_qty']);
            $table->index(['product_id', 'warehouse_id', 'received_date']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
