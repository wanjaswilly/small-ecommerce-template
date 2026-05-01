<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class AddMissingFieldsToTables
{
    public function up()
    {
        // Add phone to users (already has phone_number, this is additional)
        Capsule::schema()->table('users', function ($table) {
            $table->string('phone')->nullable()->after('phone_number');
        });

        // Add low_stock_threshold to products
        Capsule::schema()->table('products', function ($table) {
            $table->integer('low_stock_threshold')->default(5)->after('min_stock_level');
        });

        // Add payment_method and coupon_id to orders
        Capsule::schema()->table('orders', function ($table) {
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->onDelete('set null')->after('payment_method');
        });
    }

    public function down()
    {
        Capsule::schema()->table('users', function ($table) {
            $table->dropColumn('phone');
        });

        Capsule::schema()->table('products', function ($table) {
            $table->dropColumn('low_stock_threshold');
        });

        Capsule::schema()->table('orders', function ($table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['payment_method', 'coupon_id']);
        });
    }
}