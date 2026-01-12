<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bank Accounts
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id')->nullable();
            $table->uuid('account_id'); // GL Account link
            
            $table->string('code', 20);
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_name')->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('swift_code', 11)->nullable();
            $table->string('currency_code', 3)->default('SAR');
            
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->date('last_reconciled_date')->nullable();
            $table->decimal('last_reconciled_balance', 15, 2)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('account_id')->references('id')->on('accounts');
            
            $table->unique(['company_id', 'code']);
        });

        // Bank Transactions
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('bank_account_id');
            $table->uuid('journal_id')->nullable();
            
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->text('description');
            
            $table->enum('type', ['deposit', 'withdrawal', 'transfer', 'fee', 'interest']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->nullable();
            
            // Reconciliation
            $table->boolean('is_reconciled')->default(false);
            $table->uuid('reconciliation_id')->nullable();
            $table->date('reconciled_date')->nullable();
            
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts');
            $table->foreign('journal_id')->references('id')->on('journals');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->index(['bank_account_id', 'transaction_date']);
            $table->index(['bank_account_id', 'is_reconciled']);
        });

        // Bank Reconciliations
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('bank_account_id');
            
            $table->date('statement_date');
            $table->decimal('statement_balance', 15, 2);
            $table->decimal('book_balance', 15, 2);
            $table->decimal('difference', 15, 2)->default(0);
            
            $table->enum('status', ['draft', 'completed'])->default('draft');
            
            $table->uuid('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts');
            $table->foreign('completed_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
        });

        // Add FK to bank_transactions
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->foreign('reconciliation_id')->references('id')->on('bank_reconciliations');
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropForeign(['reconciliation_id']);
        });
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');
    }
};
