<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateContactMessagesTable
{
    public function up()
    {

        Capsule::schema()->create('contactmessages', function ($table) {
            $table->id();
            $table->string('fullname');          
            $table->string('phonenumber');          
            $table->string('emailaddress')->nullable();          
            $table->string('message');          
            $table->string('reply')->nullable();   
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');   
            $table->timestamp('repliedat')->nullable(); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('contactmessages');
    }
}