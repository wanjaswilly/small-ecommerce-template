<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateReviewsTable
{
    public function up()
    {

Capsule::schema()->create('reviews', function ($table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('content');
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('reviews');
    }
}