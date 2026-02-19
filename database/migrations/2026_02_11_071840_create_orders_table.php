<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateOrdersTable
{
    public function up()
    {

        Capsule::schema()->create('orders', function ($table) {
            $table->id();

            # Customer Information (for guest checkout)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();

            # Order Details
            $table->string('order_number')->unique();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('total_amount', 10, 2);

            # Delivery Information
            $table->string('delivery_location');
            $table->text('specific_address')->nullable();
            $table->text('delivery_notes')->nullable();

            # Payment Information
            $table->enum('payment_method', ['mpesa', 'cash_on_delivery', 'card', 'pesapal'])->default('mpesa');
            $table->string('transaction_id')->nullable()->unique();
            $table->string('local_reference_id')->nullable()->unique();
            $table->string('mpesa_receipt_number')->nullable();
            $table->enum('payment_status', ['pending', 'processing', 'paid', 'failed', 'refunded', 'dispatched'])->default('pending');

            # Order Status
            $table->enum('status', [
                'pending',
                'processing',
                'ready',
                'dispatched',
                'delivered',
                'cancelled'
            ])->default('pending');

            # Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            # packaging & delivery
            $table->foreignId('assigned_staff_id')->nullable()->constrained('users');
            $table->foreignId('rider_id')->nullable()->constrained('users');
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->text('dispatch_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->decimal('package_weight', 8, 2)->nullable();
            $table->string('package_dimensions')->nullable();
            $table->text('packaging_notes')->nullable();
            $table->integer('delivery_attempts')->default(0);
            $table->text('delivery_failed_reason')->nullable();
            $table->boolean('cod_collected')->default(false);


            # Indexes for better performance
            $table->index(['order_number']);
            $table->index(['customer_phone']);
            $table->index(['status']);
            $table->index(['payment_status']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('orders');
    }
}
