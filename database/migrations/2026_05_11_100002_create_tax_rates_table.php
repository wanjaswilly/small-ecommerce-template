<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateTaxRatesTable
{
    public function up()
    {
        Capsule::schema()->create('tax_rates', function ($table) {
            $table->id();
            $table->string('name');
            $table->decimal('rate', 5, 2);
            $table->string('tax_class')->default('standard');
            $table->json('applicable_zones')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('tax_rates');
    }
}