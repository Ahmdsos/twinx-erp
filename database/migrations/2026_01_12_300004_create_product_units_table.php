<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('unit_id');
            
            $table->decimal('conversion_factor', 15, 6)->default(1);
            $table->boolean('is_base_unit')->default(false);
            $table->boolean('is_purchase_unit')->default(false);
            $table->boolean('is_sale_unit')->default(false);
            $table->string('barcode', 50)->nullable();
            $table->decimal('sale_price', 15, 4)->nullable();
            $table->decimal('cost_price', 15, 4)->nullable();
            
            $table->timestamps();
            
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('cascade');
            
            $table->unique(['product_id', 'unit_id']);
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
