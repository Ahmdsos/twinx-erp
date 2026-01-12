<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Credit Notes (Sales Returns)
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('customer_id');
            $table->uuid('invoice_id')->nullable();
            
            $table->string('credit_note_number');
            $table->date('issue_date');
            $table->text('reason');
            
            $table->enum('status', ['draft', 'issued', 'applied'])->default('draft');
            
            $table->string('currency_code', 3)->default('SAR');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            
            $table->decimal('applied_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            
            $table->uuid('journal_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('invoice_id')->references('id')->on('invoices');
            $table->foreign('journal_id')->references('id')->on('journals');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->unique(['company_id', 'credit_note_number']);
        });

        // Credit Note Lines
        Schema::create('credit_note_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('credit_note_id');
            $table->uuid('product_id')->nullable();
            $table->uuid('invoice_line_id')->nullable();
            
            $table->text('description');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(15);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            
            $table->timestamps();

            $table->foreign('credit_note_id')->references('id')->on('credit_notes')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
        });

        // Debit Notes (Purchase Returns)
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            $table->uuid('supplier_id');
            $table->uuid('bill_id')->nullable();
            
            $table->string('debit_note_number');
            $table->date('issue_date');
            $table->text('reason');
            
            $table->enum('status', ['draft', 'issued', 'applied'])->default('draft');
            
            $table->string('currency_code', 3)->default('SAR');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            
            $table->uuid('journal_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('bill_id')->references('id')->on('bills');
            $table->foreign('journal_id')->references('id')->on('journals');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->unique(['company_id', 'debit_note_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_notes');
        Schema::dropIfExists('credit_note_lines');
        Schema::dropIfExists('credit_notes');
    }
};
