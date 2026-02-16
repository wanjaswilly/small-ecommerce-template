<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateCartsTable
{
    public function up()
    {

        Capsule::schema()->create('carts', function ($table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable(); # For guest users
            $table->timestamps();

            $table->unique(['user_id', 'session_id']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('carts');
    }
}
