<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            
            $table->string('reference', 50);
            $table->string('type', 20);
            
            $table->uuid('product_id');
            $table->uuid('warehouse_id');
            $table->uuid('unit_id');
            
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            
            $table->date('movement_date');
            $table->text('notes')->nullable();
            
            // Source document (polymorphic)
            $table->string('source_type')->nullable();
            $table->uuid('source_id')->nullable();
            
            // Transfer reference
            $table->uuid('transfer_warehouse_id')->nullable();
            $table->uuid('related_movement_id')->nullable();
            
            // Accounting
            $table->uuid('journal_id')->nullable();
            
            $table->uuid('created_by');
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');
            
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('cascade');
            
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('cascade');
            
            $table->foreign('transfer_warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('set null');
            
            $table->foreign('journal_id')
                ->references('id')
                ->on('journals')
                ->onDelete('set null');
            
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
            
            // Indexes
            $table->unique(['company_id', 'reference']);
            $table->index(['company_id', 'product_id']);
            $table->index(['company_id', 'warehouse_id']);
            $table->index(['company_id', 'movement_date']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
