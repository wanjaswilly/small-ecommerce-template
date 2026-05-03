<?php

namespace Database\Factories;

use Faker\Factory as Faker;

class CategoryFactory
{
    public static function make(): array
    {
        $faker = Faker::create();

        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Smartphones, laptops, TVs and electronic gadgets'],
            ['name' => 'Fashion & Clothing', 'slug' => 'fashion', 'description' => 'Traditional and modern Kenyan fashion'],
            ['name' => 'Home & Kitchen', 'slug' => 'home', 'description' => 'Home decor and kitchen essentials'],
            ['name' => 'Books & Education', 'slug' => 'books', 'description' => 'Books, textbooks and educational materials'],
            ['name' => 'Sports & Outdoors', 'slug' => 'sports', 'description' => 'Sports equipment and outdoor gear'],
            ['name' => 'Health & Beauty', 'slug' => 'beauty', 'description' => 'Health products and beauty essentials'],
            ['name' => 'Food & Beverages', 'slug' => 'food', 'description' => 'Kenyan foods and traditional beverages'],
            ['name' => 'Art & Crafts', 'slug' => 'art', 'description' => 'Kenyan art, crafts and handmade items'],
        ];

        $category = $faker->randomElement($categories);

        return [
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'],
            'is_active' => true,
            'parent_id' => null, // Will be set by seeder for subcategories
            'created_at' => $faker->dateTimeBetween('-2 years', '-6 months'),
            'updated_at' => $faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}