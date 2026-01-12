<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alert Rules
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            
            $table->string('name');
            $table->string('type'); // AlertType enum
            $table->json('conditions')->nullable();
            
            $table->boolean('email_enabled')->default(true);
            $table->boolean('database_enabled')->default(true);
            $table->json('recipients')->nullable(); // Array of user IDs or emails
            
            $table->integer('threshold')->nullable(); // e.g., min stock level
            $table->integer('days_before')->nullable(); // e.g., days before expiry
            
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->index(['company_id', 'type', 'is_active']);
        });

        // Alert Logs
        Schema::create('alert_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('alert_rule_id')->nullable();
            $table->uuid('user_id')->nullable(); // Recipient
            
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            
            $table->string('reference_type')->nullable(); // e.g., 'invoice', 'product'
            $table->uuid('reference_id')->nullable();
            
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('alert_rule_id')->references('id')->on('alert_rules')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
            
            $table->index(['user_id', 'is_read']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_logs');
        Schema::dropIfExists('alert_rules');
    }
};
