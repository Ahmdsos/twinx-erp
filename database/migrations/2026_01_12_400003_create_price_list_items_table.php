<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('price_list_id');
            $table->uuid('product_id');
            $table->uuid('unit_id')->nullable();
            
            $table->decimal('price', 15, 4);
            $table->decimal('min_qty', 15, 4)->default(1);
            
            $table->timestamps();
            
            $table->foreign('price_list_id')
                ->references('id')
                ->on('price_lists')
                ->onDelete('cascade');
            
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('set null');
            
            $table->unique(['price_list_id', 'product_id', 'unit_id', 'min_qty'], 'price_list_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
