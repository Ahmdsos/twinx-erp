<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('category_id')->nullable();
            
            // Identification
            $table->string('sku', 50);
            $table->string('barcode', 50)->nullable();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            
            // Type
            $table->string('type', 20)->default('product');
            $table->boolean('is_trackable')->default(true);
            $table->boolean('is_purchasable')->default(true);
            $table->boolean('is_sellable')->default(true);
            
            // Pricing
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->decimal('sale_price', 15, 4)->default(0);
            $table->decimal('min_sale_price', 15, 4)->default(0);
            
            // Tax
            $table->decimal('tax_rate', 5, 2)->default(15);
            $table->boolean('is_tax_inclusive')->default(false);
            
            // Inventory
            $table->decimal('min_stock_level', 15, 4)->default(0);
            $table->decimal('max_stock_level', 15, 4)->nullable();
            $table->decimal('reorder_point', 15, 4)->default(0);
            $table->decimal('reorder_qty', 15, 4)->default(1);
            
            // Valuation method
            $table->string('valuation_method', 20)->default('weighted_average');
            
            // Accounting Integration
            $table->uuid('inventory_account_id')->nullable();
            $table->uuid('cogs_account_id')->nullable();
            $table->uuid('revenue_account_id')->nullable();
            
            // Base unit
            $table->uuid('base_unit_id')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign Keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            $table->foreign('category_id')
                ->references('id')
                ->on('product_categories')
                ->onDelete('set null');
            
            $table->foreign('base_unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('set null');
            
            $table->foreign('inventory_account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('set null');
            
            $table->foreign('cogs_account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('set null');
            
            $table->foreign('revenue_account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('set null');
            
            // Indexes
            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'barcode']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
