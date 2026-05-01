<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateNewsletterSubscribersTable
{
    public function up()
    {

Capsule::schema()->create('newslettersubscribers', function ($table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('subscribed_at');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('newslettersubscribers');
    }
}