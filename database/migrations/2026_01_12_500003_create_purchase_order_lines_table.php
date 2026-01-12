<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id');
            $table->uuid('product_id');
            $table->uuid('unit_id');
            
            $table->integer('line_number');
            $table->text('description')->nullable();
            
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(15);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            
            $table->decimal('received_qty', 15, 4)->default(0);
            $table->decimal('billed_qty', 15, 4)->default(0);
            
            $table->timestamps();
            
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->onDelete('cascade');
            
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('restrict');
            
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('restrict');
            
            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
