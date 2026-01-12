<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Currencies
        Schema::create('currencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            
            $table->string('code', 3); // ISO 4217
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('symbol', 10);
            $table->integer('decimal_places')->default(2);
            
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->unique(['company_id', 'code']);
        });

        // Exchange Rates
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 12, 6);
            $table->date('effective_date');
            
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->unique(['company_id', 'from_currency', 'to_currency', 'effective_date'], 'exchange_rates_unique');
            $table->index(['company_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
