<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreatePaymentTransactionsTable
{
    public function up()
    {

        Capsule::schema()->create('paymenttransactions', function ($table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique();
            $table->enum('payment_method', ['mpesa', 'cash_on_delivery', 'card', 'bank_transfer']);
            $table->decimal('amount', 10, 2);
            $table->string('phone')->nullable(); // For M-Pesa
            $table->string('mpesa_receipt_number')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('response_data')->nullable(); // Raw response from payment gateway
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['transaction_id']);
            $table->index(['order_id']);
            $table->index(['status']);
            $table->index(['payment_method']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('paymenttransactions');
    }
}
