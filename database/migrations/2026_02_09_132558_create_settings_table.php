<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateSettingsTable
{
    public function up()
    {

        Capsule::schema()->create('settings', function ($table) {
            $table->id();

            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->timestamps();

            $table->index(['key', 'group']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('settings');
    }
}
