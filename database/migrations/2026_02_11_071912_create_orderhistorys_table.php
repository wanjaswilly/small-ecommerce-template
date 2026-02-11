<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateOrderHistorysTable
{
    public function up()
    {

        Capsule::schema()->create('orderhistorys', function ($table) {
            $table->id();
                        
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('orderhistorys');
    }
}