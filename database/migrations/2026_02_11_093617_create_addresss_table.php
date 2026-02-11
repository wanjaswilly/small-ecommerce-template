<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateAddresssTable
{
    public function up()
    {

        Capsule::schema()->create('addresss', function ($table) {
            $table->id();
            // User relationship
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Address identification
            $table->string('label')->default('Delivery Address'); // Home, Work, etc.

            // Recipient information
            $table->string('recipient_name');
            $table->string('phone');

            // Location details
            $table->string('area'); // Nairobi CBD, Westlands, etc.
            $table->text('street'); // Full street address
            $table->string('city')->default('Nairobi');
            $table->string('country')->default('Kenya');

            // Additional delivery information
            $table->text('delivery_instructions')->nullable(); // Gate code, landmarks, etc.

            // Address preferences
            $table->boolean('is_default')->default(false);

            // Timestamps
            $table->timestamps();

            // Indexes for better performance
            $table->index(['user_id']);
            $table->index(['user_id', 'is_default']);
            $table->index(['area']);
            $table->index(['is_default']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('addresss');
    }
}
