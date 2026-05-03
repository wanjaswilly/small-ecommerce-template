<?php

namespace Database\Factories;

use Faker\Factory as Faker;

class ReviewFactory
{
    public static function make(): array
    {
        $faker = Faker::create();

        $titles = [
            'Excellent product!', 'Great quality', 'Fast delivery', 'Highly recommended',
            'Good value for money', 'Satisfied with purchase', 'Amazing service',
            'Perfect for my needs', 'Better than expected', 'Will buy again',
            'Authentic Kenyan product', 'Traditional craftsmanship', 'Beautiful design',
            'Excellent customer service', 'Quick response time'
        ];

        $positiveComments = [
            'This product exceeded my expectations. The quality is outstanding and the delivery was fast.',
            'Very happy with my purchase. The item arrived exactly as described and works perfectly.',
            'Great Kenyan product! Supporting local artisans is important, and this item is beautiful.',
            'Excellent service from Duka. The ordering process was smooth and delivery was on time.',
            'Good value for money. The quality matches the price and I\'m very satisfied.',
            'Traditional Kenyan craftsmanship at its best. Proud to own this authentic piece.',
            'Fast delivery and excellent packaging. The product arrived in perfect condition.',
            'Highly recommend this seller. Professional service and genuine products.',
            'Beautiful design that captures Kenyan heritage. Will definitely purchase again.',
            'The customer support team was very helpful and responsive to my questions.'
        ];

        $ratings = [5, 5, 5, 4, 4, 4, 4, 3, 3, 5, 5, 5]; // Mostly positive ratings

        return [
            'product_id' => $faker->numberBetween(1, 25), // Will be set properly by seeder
            'user_id' => $faker->numberBetween(1, 5), // Will be set properly by seeder
            'rating' => $faker->randomElement($ratings),
            'title' => $faker->randomElement($titles),
            'content' => $faker->randomElement($positiveComments),
            'is_approved' => $faker->boolean(90), // 90% approved
            'created_at' => $faker->dateTimeBetween('-1 year', '-1 week'),
            'updated_at' => $faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}