<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateProductVariantsTable
{
    public function up()
    {

Capsule::schema()->create('productvariants', function ($table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('sku')->unique();
            $table->json('combination');
            $table->decimal('price_adjustment', 10, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('productvariants');
    }
}