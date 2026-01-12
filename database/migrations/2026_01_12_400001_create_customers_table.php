<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            
            // Identification
            $table->string('code', 20);
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            
            // Tax
            $table->string('vat_number', 15)->nullable();
            $table->string('cr_number', 20)->nullable();
            
            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->default('SA');
            
            // Financial
            $table->uuid('price_list_id')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('payment_terms')->default(0);
            $table->uuid('receivable_account_id')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            $table->foreign('receivable_account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('set null');
            
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
            $table->index('vat_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
