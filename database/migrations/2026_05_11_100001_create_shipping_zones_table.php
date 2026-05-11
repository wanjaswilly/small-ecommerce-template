<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateShippingZonesTable
{
    public function up()
    {
        Capsule::schema()->create('shipping_zones', function ($table) {
            $table->id();
            $table->string('name');
            $table->json('locations')->nullable();
            $table->decimal('base_fee', 10, 2)->default(0);
            $table->decimal('per_kg_fee', 10, 2)->default(0);
            $table->decimal('free_shipping_threshold', 10, 2)->default(0);
            $table->integer('estimated_days')->default(3);
            $table->string('shipping_method')->default('Standard');
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('shipping_zones');
    }
}