<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateBrandsTable
{
    public function up()
    {

        Capsule::schema()->create('brands', function ($table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('brands');
    }
}
