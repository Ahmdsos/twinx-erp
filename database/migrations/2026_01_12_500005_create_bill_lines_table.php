<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bill_id');
            $table->uuid('product_id')->nullable();
            $table->uuid('unit_id')->nullable();
            $table->uuid('purchase_order_line_id')->nullable();
            
            $table->integer('line_number');
            $table->text('description');
            
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(15);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            
            $table->timestamps();
            
            $table->foreign('bill_id')
                ->references('id')
                ->on('bills')
                ->onDelete('cascade');
            
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('set null');
            
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('set null');
            
            $table->foreign('purchase_order_line_id')
                ->references('id')
                ->on('purchase_order_lines')
                ->onDelete('set null');
            
            $table->index('bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_lines');
    }
};
