<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quotations
        Schema::create('quotations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('customer_id');
            
            $table->string('quotation_number');
            $table->date('quotation_date');
            $table->date('valid_until');
            
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted'])->default('draft');
            
            $table->text('subject')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            
            $table->string('currency_code', 3)->default('SAR');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            
            $table->uuid('converted_to_order_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('converted_to_order_id')->references('id')->on('sales_orders');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->unique(['company_id', 'quotation_number']);
            $table->index(['company_id', 'status']);
        });

        // Quotation Lines
        Schema::create('quotation_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('quotation_id');
            $table->uuid('product_id')->nullable();
            
            $table->text('description');
            $table->decimal('quantity', 12, 3);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(15);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('quotation_id')->references('id')->on('quotations')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_lines');
        Schema::dropIfExists('quotations');
    }
};
