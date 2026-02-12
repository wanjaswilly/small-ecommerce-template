<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateFavouritesTable
{
    public function up()
    {

        Capsule::schema()->create('favourites', function ($table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');                        
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');  
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('favourites');
    }
}