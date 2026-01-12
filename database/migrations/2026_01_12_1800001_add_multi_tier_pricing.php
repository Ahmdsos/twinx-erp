<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add multi-tier pricing to products table (check if columns exist first)
        Schema::table('products', function (Blueprint $table) {
            // Multi-tier pricing
            if (!Schema::hasColumn('products', 'semi_wholesale_price')) {
                $table->decimal('semi_wholesale_price', 18, 4)->nullable();
            }
            if (!Schema::hasColumn('products', 'quarter_wholesale_price')) {
                $table->decimal('quarter_wholesale_price', 18, 4)->nullable();
            }
            if (!Schema::hasColumn('products', 'wholesale_price')) {
                $table->decimal('wholesale_price', 18, 4)->nullable();
            }
            if (!Schema::hasColumn('products', 'distributor_price')) {
                $table->decimal('distributor_price', 18, 4)->nullable();
            }
            
            // Minimum quantities for each tier
            if (!Schema::hasColumn('products', 'min_retail_qty')) {
                $table->integer('min_retail_qty')->default(1);
            }
            if (!Schema::hasColumn('products', 'min_semi_wholesale_qty')) {
                $table->integer('min_semi_wholesale_qty')->default(12);
            }
            if (!Schema::hasColumn('products', 'min_quarter_wholesale_qty')) {
                $table->integer('min_quarter_wholesale_qty')->default(24);
            }
            if (!Schema::hasColumn('products', 'min_wholesale_qty')) {
                $table->integer('min_wholesale_qty')->default(48);
            }
            if (!Schema::hasColumn('products', 'min_distributor_qty')) {
                $table->integer('min_distributor_qty')->default(100);
            }
            
            // Additional product fields
            if (!Schema::hasColumn('products', 'name_en')) {
                $table->string('name_en')->nullable();
            }
            if (!Schema::hasColumn('products', 'secondary_barcode')) {
                $table->string('secondary_barcode')->nullable();
            }
            if (!Schema::hasColumn('products', 'item_code')) {
                $table->string('item_code')->nullable();
            }
            if (!Schema::hasColumn('products', 'model')) {
                $table->string('model')->nullable();
            }
            if (!Schema::hasColumn('products', 'warranty_months')) {
                $table->integer('warranty_months')->default(0);
            }
            if (!Schema::hasColumn('products', 'color')) {
                $table->string('color')->nullable();
            }
            if (!Schema::hasColumn('products', 'size')) {
                $table->string('size')->nullable();
            }
            if (!Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 10, 3)->nullable();
            }
            if (!Schema::hasColumn('products', 'dimensions')) {
                $table->string('dimensions')->nullable();
            }
            if (!Schema::hasColumn('products', 'custom_attributes')) {
                $table->json('custom_attributes')->nullable();
            }
            if (!Schema::hasColumn('products', 'shelf_location')) {
                $table->string('shelf_location')->nullable();
            }
            if (!Schema::hasColumn('products', 'is_service')) {
                $table->boolean('is_service')->default(false);
            }
            if (!Schema::hasColumn('products', 'is_bundle')) {
                $table->boolean('is_bundle')->default(false);
            }
        });
        
        // Customer types and price lists
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'customer_type')) {
                $table->string('customer_type')->default('retail');
            }
            if (!Schema::hasColumn('customers', 'credit_limit')) {
                $table->decimal('credit_limit', 18, 4)->default(0);
            }
            if (!Schema::hasColumn('customers', 'payment_terms_days')) {
                $table->integer('payment_terms_days')->default(0);
            }
            if (!Schema::hasColumn('customers', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $productColumns = [
            'semi_wholesale_price', 'quarter_wholesale_price', 'wholesale_price', 'distributor_price',
            'min_retail_qty', 'min_semi_wholesale_qty', 'min_quarter_wholesale_qty', 'min_wholesale_qty', 'min_distributor_qty',
            'name_en', 'secondary_barcode', 'item_code', 'model', 'warranty_months',
            'color', 'size', 'weight', 'dimensions', 'custom_attributes', 'shelf_location',
            'is_service', 'is_bundle'
        ];
        
        Schema::table('products', function (Blueprint $table) use ($productColumns) {
            foreach ($productColumns as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        
        $customerColumns = ['customer_type', 'credit_limit', 'payment_terms_days', 'discount_percentage'];
        
        Schema::table('customers', function (Blueprint $table) use ($customerColumns) {
            foreach ($customerColumns as $col) {
                if (Schema::hasColumn('customers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
