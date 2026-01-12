<?php

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
        Schema::table('invoices', function (Blueprint $table) {
            // ZATCA Invoice Category
            $table->enum('invoice_category', ['standard', 'simplified'])
                ->default('simplified')
                ->after('invoice_type')
                ->comment('فاتورة ضريبية أو مبسطة');

            // ZATCA UUID (Unique Universal Identifier)
            $table->uuid('zatca_uuid')->nullable()->after('invoice_category');

            // Invoice hash (SHA256)
            $table->string('zatca_hash', 64)->nullable()->after('zatca_uuid');

            // QR Code data (Base64 TLV encoded)
            $table->text('zatca_qr_code')->nullable()->after('zatca_hash');

            // XML invoice content
            $table->longText('zatca_xml')->nullable()->after('zatca_qr_code');

            // Clearance status
            $table->timestamp('zatca_cleared_at')->nullable()->after('zatca_xml');
            $table->timestamp('zatca_reported_at')->nullable()->after('zatca_cleared_at');

            // ZATCA response
            $table->json('zatca_response')->nullable()->after('zatca_reported_at');

            // Add index
            $table->index('zatca_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['zatca_uuid']);
            $table->dropColumn([
                'invoice_category',
                'zatca_uuid',
                'zatca_hash',
                'zatca_qr_code',
                'zatca_xml',
                'zatca_cleared_at',
                'zatca_reported_at',
                'zatca_response',
            ]);
        });
    }
};
