<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Delivery Zones
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            
            $table->string('code', 20);
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->integer('estimated_minutes')->default(60);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->unique(['company_id', 'code']);
        });

        // Delivery Orders
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('branch_id');
            
            $table->string('delivery_number');
            $table->uuid('sales_order_id')->nullable();
            $table->uuid('invoice_id')->nullable();
            
            // Customer
            $table->uuid('customer_id')->nullable();
            $table->string('customer_name');
            $table->text('delivery_address');
            $table->string('contact_phone', 20);
            
            // Assignment
            $table->uuid('driver_id')->nullable();
            $table->uuid('vehicle_id')->nullable();
            $table->uuid('zone_id')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'failed'])->default('pending');
            $table->text('failure_reason')->nullable();
            
            // Timing
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // Proof of Delivery
            $table->string('receiver_name')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('photo_path')->nullable();
            
            // Financials
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('cod_amount', 12, 2)->default(0); // Cash on Delivery
            $table->boolean('cod_collected')->default(false);
            
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('sales_order_id')->references('id')->on('sales_orders');
            $table->foreign('invoice_id')->references('id')->on('invoices');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('driver_id')->references('id')->on('drivers');
            $table->foreign('vehicle_id')->references('id')->on('vehicles');
            $table->foreign('zone_id')->references('id')->on('delivery_zones');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->unique(['company_id', 'delivery_number']);
            $table->index(['company_id', 'status']);
            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('delivery_zones');
    }
};
