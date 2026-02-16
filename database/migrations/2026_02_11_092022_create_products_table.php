<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateProductsTable
{
    public function up()
    {

        Capsule::schema()->create('products', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->string('sku')->unique();
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_level')->default(5);
            $table->json('images')->nullable(); # Array of image URLs
            $table->boolean('is_active')->default(true);
            $table->boolean('featured')->default(false);
            $table->boolean('new_arrival')->default(false);
            $table->boolean('special_offer')->default(false);
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->timestamps();
            $table->text('attributes')->nullable();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('cascade');
            $table->decimal('offer_price', 10, 2)->nullable();
            $table->integer('product_views')->default(0);

            $table->index(['is_active', 'category_id']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('products');
    }
}
