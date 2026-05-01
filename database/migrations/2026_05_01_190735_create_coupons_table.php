<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateCouponsTable
{
    public function up()
    {

Capsule::schema()->create('coupons', function ($table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['percentage', 'fixed']);
            $table->decimal('value', 10, 2);
            $table->decimal('min_spend', 10, 2)->nullable();
            $table->integer('max_uses_total')->nullable();
            $table->integer('used_total')->default(0);
            $table->integer('max_uses_per_user')->nullable();
            $table->datetime('start_date')->nullable();
            $table->datetime('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('coupons');
    }
}