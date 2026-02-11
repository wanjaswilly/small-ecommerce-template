<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateOrdersTable
{
    public function up()
    {

        Capsule::schema()->create('orders', function ($table) {
            $table->id();
                        
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('orders');
    }
}