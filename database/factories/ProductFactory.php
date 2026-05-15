<?php

namespace Database\Factories;

use Faker\Factory as Faker;

class ProductFactory
{
    public static function make(): array
    {
        $faker = Faker::create();

        // Kenyan-themed product data
        $products = [
            // Electronics
            ['name' => 'Samsung Galaxy A54 Smartphone', 'price' => 45000, 'category' => 'electronics', 'description' => 'Latest Samsung smartphone with excellent camera and battery life'],
            ['name' => 'Apple iPhone 15 Pro Max', 'price' => 180000, 'category' => 'electronics', 'description' => 'Premium iPhone with titanium design and advanced camera system'],
            ['name' => 'Dell Latitude 5420 Laptop', 'price' => 85000, 'category' => 'electronics', 'description' => 'Business laptop perfect for work and studies in Kenya'],
            ['name' => 'HP Pavilion 14 Gaming Laptop', 'price' => 95000, 'category' => 'electronics', 'description' => 'Gaming laptop with dedicated graphics for gaming enthusiasts'],
            ['name' => 'JBL Go 3 Portable Speaker', 'price' => 5500, 'category' => 'electronics', 'description' => 'Waterproof portable speaker for music lovers in Kenya'],
            ['name' => 'Sony WH-1000XM4 Headphones', 'price' => 35000, 'category' => 'electronics', 'description' => 'Industry-leading noise cancelling wireless headphones'],
            ['name' => 'Apple Watch Series 9', 'price' => 65000, 'category' => 'electronics', 'description' => 'Smartwatch with health monitoring and fitness tracking'],
            ['name' => 'Samsung 43" Smart TV', 'price' => 55000, 'category' => 'electronics', 'description' => '4K UHD Smart TV perfect for Kenyan homes'],

            // Fashion & Clothing
            ['name' => 'Nairobi Tailored Suit', 'price' => 25000, 'category' => 'fashion', 'description' => 'Hand-tailored wool suit made in Nairobi with modern Kenyan design'],
            ['name' => 'Kikoy Traditional Wrap', 'price' => 3500, 'category' => 'fashion', 'description' => 'Authentic Kenyan kikoy fabric perfect for coastal wear'],
            ['name' => 'Safari Adventure Jacket', 'price' => 12000, 'category' => 'fashion', 'description' => 'Water-resistant jacket ideal for Kenyan safaris and outdoor activities'],
            ['name' => 'Maasai Beaded Jewelry Set', 'price' => 8500, 'category' => 'fashion', 'description' => 'Handcrafted beaded jewelry from Maasai artisans'],
            ['name' => 'Nubian Heritage Scarf', 'price' => 2200, 'category' => 'fashion', 'description' => 'Beautiful printed scarf celebrating Nubian heritage'],
            ['name' => 'Kenyan Coffee T-Shirt', 'price' => 1800, 'category' => 'fashion', 'description' => 'Cotton t-shirt featuring famous Kenyan coffee regions'],

            // Home & Kitchen
            ['name' => 'Kenyan Acacia Wood Salad Bowl', 'price' => 4200, 'category' => 'home', 'description' => 'Hand-carved acacia wood bowl from Kenyan artisans'],
            ['name' => 'Safari Pattern Throw Blanket', 'price' => 6500, 'category' => 'home', 'description' => 'Cozy blanket with authentic safari patterns'],
            ['name' => 'Nairobi Ceramic Dinner Set', 'price' => 18500, 'category' => 'home', 'description' => 'Complete 24-piece dinner set made in Nairobi'],
            ['name' => 'Kenyan Tea Infuser Set', 'price' => 2800, 'category' => 'home', 'description' => 'Traditional Kenyan tea infuser with local herbs'],
            ['name' => 'Makuti Palm Basket', 'price' => 3200, 'category' => 'home', 'description' => 'Handwoven basket using traditional Kenyan makuti palm'],
            ['name' => 'Rift Valley Pottery Vase', 'price' => 5500, 'category' => 'home', 'description' => 'Beautiful ceramic vase from Rift Valley potters'],

            // Books & Education
            ['name' => 'Things Fall Apart - Chinua Achebe', 'price' => 1200, 'category' => 'books', 'description' => 'Classic African literature by Kenyan-born Nobel laureate'],
            ['name' => 'The River Between - Ngũgĩ wa Thiong\'o', 'price' => 1100, 'category' => 'books', 'description' => 'Kenyan classic exploring cultural conflict'],
            ['name' => 'Kenyan Cookbook', 'price' => 2500, 'category' => 'books', 'description' => 'Traditional Kenyan recipes and cooking methods'],
            ['name' => 'Swahili Language Guide', 'price' => 1800, 'category' => 'books', 'description' => 'Essential guide for learning Swahili language'],
            ['name' => 'Kenyan History Textbook', 'price' => 3200, 'category' => 'books', 'description' => 'Comprehensive history of Kenya for students'],

            // Sports & Outdoors
            ['name' => 'Rugby World Cup Kenya Jersey', 'price' => 4500, 'category' => 'sports', 'description' => 'Official Kenyan rugby team jersey'],
            ['name' => 'Safari Hiking Boots', 'price' => 8500, 'category' => 'sports', 'description' => 'Durable boots perfect for Kenyan terrain and safaris'],
            ['name' => 'Maasai Leather Football', 'price' => 2200, 'category' => 'sports', 'description' => 'Handcrafted leather football from Maasai artisans'],
            ['name' => 'Nairobi Marathon Running Shoes', 'price' => 6500, 'category' => 'sports', 'description' => 'Comfortable running shoes for Kenyan athletes'],
            ['name' => 'Kenyan Athletics Training Kit', 'price' => 3800, 'category' => 'sports', 'description' => 'Complete training set for Kenyan athletes'],

            // Health & Beauty
            ['name' => 'Kenyan Shea Butter Cream', 'price' => 1800, 'category' => 'beauty', 'description' => 'Natural shea butter moisturizer from Kenyan cooperatives'],
            ['name' => 'Safari Sun Protection Lotion', 'price' => 2200, 'category' => 'beauty', 'description' => 'SPF 50 sunscreen ideal for Kenyan outdoor activities'],
            ['name' => 'Mombasa Sea Salt Scrub', 'price' => 1600, 'category' => 'beauty', 'description' => 'Natural sea salt body scrub from Mombasa coast'],
            ['name' => 'Nairobi Botanical Face Mask', 'price' => 1200, 'category' => 'beauty', 'description' => 'Natural face mask using Kenyan herbs and botanicals'],
        ];

        $product = $faker->randomElement($products);

        // Generate slug from name
        $slug = strtolower(str_replace([' ', '\'', '"', '.', ','], ['-', '', '', '', ''], $product['name']));

        // Generate Unsplash image URL based on category
        $unsplashCategories = [
            'electronics' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=500',
            'fashion' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=500',
            'home' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=500',
            'books' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=500',
            'sports' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=500',
            'beauty' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=500',
        ];

        $imageUrl = $unsplashCategories[$product['category']] ?? 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=500';

        return [
            'name' => $product['name'],
            'slug' => $slug . '-' . $faker->unique()->numberBetween(10000, 99999),
            'description' => $product['description'],
            'price' => $product['price'],
            'discount_price' => $faker->optional(0.3)->numberBetween($product['price'] * 0.7, $product['price'] * 0.9),
            'sku' => 'KEN-' . strtoupper($faker->unique()->lexify('????')) . $faker->numberBetween(1000, 9999),
            'stock_quantity' => $faker->numberBetween(5, 100),
            'min_stock_level' => $faker->numberBetween(3, 10),
            'images' => json_encode([$imageUrl]),
            '_image_url' => $imageUrl,   // raw remote URL used by the seeder to download & save the file locally
            'attributes' => json_encode([
                'brand' => $faker->randomElement(['Kenyan Made', 'Local Artisan', 'Safari Collection', 'Nairobi Design', 'Coastal Crafts']),
                'origin' => $faker->randomElement(['Kenya', 'Nairobi', 'Mombasa', 'Kisumu', 'Eldoret']),
                'material' => $faker->randomElement(['Cotton', 'Wood', 'Ceramic', 'Leather', 'Metal', 'Plastic']),
            ]),
            'is_active' => true,
            'featured' => $faker->boolean(20),
            'new_arrival' => $faker->boolean(30),
            'special_offer' => $faker->boolean(15),
            'category_id' => $faker->numberBetween(1, 5), // Will be set properly by seeder
            'product_views' => $faker->numberBetween(0, 1000),
            'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}