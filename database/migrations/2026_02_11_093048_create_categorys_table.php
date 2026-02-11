<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateCategorysTable
{
    public function up()
    {

        Capsule::schema()->create('categories', function ($table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('categories');
    }
}
