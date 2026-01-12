<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            
            $table->string('code', 20);
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->boolean('is_default')->default(false);
            $table->decimal('markup_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            $table->unique(['company_id', 'code']);
        });

        // Add price_list_id FK to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('price_list_id')
                ->references('id')
                ->on('price_lists')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['price_list_id']);
        });
        Schema::dropIfExists('price_lists');
    }
};
