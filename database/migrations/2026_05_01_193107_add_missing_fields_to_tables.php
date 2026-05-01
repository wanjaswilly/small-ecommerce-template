<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class AddMissingFieldsToTables
{
    public function up()
    {
        // Add low_stock_threshold to products (already has min_stock_level)
        Capsule::schema()->table('products', function ($table) {
            if (!Capsule::schema()->hasColumn('products', 'low_stock_threshold')) {
                $table->integer('low_stock_threshold')->default(5)->after('min_stock_level');
            }
        });

        // Add payment_method and coupon_id to orders
        Capsule::schema()->table('orders', function ($table) {
            if (!Capsule::schema()->hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('payment_status');
            }
            if (!Capsule::schema()->hasColumn('orders', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->constrained('coupons')->onDelete('set null')->after('payment_method');
            }
        });
    }

    public function down()
    {
        Capsule::schema()->table('products', function ($table) {
            if (Capsule::schema()->hasColumn('products', 'low_stock_threshold')) {
                $table->dropColumn('low_stock_threshold');
            }
        });

        Capsule::schema()->table('orders', function ($table) {
            if (Capsule::schema()->hasColumn('orders', 'coupon_id')) {
                $table->dropForeign(['coupon_id']);
                $table->dropColumn(['payment_method', 'coupon_id']);
            }
        });
    }
}