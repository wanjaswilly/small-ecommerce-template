<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreatePagesTable
{
    public function up()
    {
        Capsule::schema()->create('pages', function ($table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_homepage')->default(false);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('pages');
    }
}