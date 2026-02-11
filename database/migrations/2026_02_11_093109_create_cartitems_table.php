<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateCartItemsTable
{
    public function up()
    {

        Capsule::schema()->create('cartitems', function ($table) {
            $table->id();

            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['cart_id', 'product_id']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('cartitems');
    }
}
