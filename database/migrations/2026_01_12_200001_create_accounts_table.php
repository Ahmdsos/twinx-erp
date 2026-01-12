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
        // Step 1: Create the table without self-referencing FK
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('parent_id')->nullable();
            
            // Account identification
            $table->string('code', 20);
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('type', 20); // AccountType enum
            
            // Classification
            $table->boolean('is_group')->default(false);
            $table->boolean('is_system')->default(false);
            $table->integer('level')->default(1);
            
            // Behavior
            $table->string('normal_balance', 10); // 'debit' or 'credit'
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_direct_posting')->default(true);
            
            // Metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign Key for company
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            // Indexes
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'parent_id']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
        });

        // Step 2: Add self-referencing FK in separate statement
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
