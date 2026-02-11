<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateProductTagsTable
{
    public function up()
    {

        Capsule::schema()->create('producttags', function ($table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('tag_name');
            $table->text('tag_value');
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('producttags');
    }
}
