<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateOrderItemsTable
{
    public function up()
    {

        Capsule::schema()->create('orderitems', function ($table) {
            $table->id();
                        
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('orderitems');
    }
}