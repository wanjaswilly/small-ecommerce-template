<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateOrderItemsTable
{
    public function up()
    {

        Capsule::schema()->create('order_items', function ($table) {
            $table->id();
                        
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
                        
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('order_items');
    }
}