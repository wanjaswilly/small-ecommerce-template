<?php

namespace Database\Factories;

use Faker\Factory as Faker;

class CouponFactory
{
    public static function make(): array
    {
        $faker = Faker::create();

        $types = ['percentage', 'fixed'];
        $type = $faker->randomElement($types);

        // Generate coupon code
        $words = ['KENYA', 'SAFARI', 'DUKA', 'LOCAL', 'AFRICA', 'HERITAGE', 'TRADITION', 'CRAFT', 'ARTISAN', 'QUALITY'];
        $code = $faker->randomElement($words) . $faker->numberBetween(10, 99);

        // Value based on type
        $value = $type === 'percentage'
            ? $faker->numberBetween(5, 30) // 5-30% discount
            : $faker->numberBetween(500, 5000); // KES 500-5000 fixed discount

        $startDate = $faker->optional(0.7)->dateTimeBetween('-1 month', '+1 month');
        $endDate = $startDate ? $faker->dateTimeBetween($startDate, '+3 months') : null;

        return [
            'code' => $code,
            'type' => $type,
            'value' => $value,
            'min_spend' => $faker->optional(0.6)->numberBetween(1000, 10000), // 60% have minimum spend
            'max_uses_total' => $faker->optional(0.8)->numberBetween(10, 1000), // 80% have usage limits
            'max_uses_per_user' => $faker->optional(0.5)->numberBetween(1, 5), // 50% have per-user limits
            'used_total' => $faker->numberBetween(0, 50),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => $faker->boolean(85), // 85% active
            'created_at' => $faker->dateTimeBetween('-6 months', '-1 month'),
            'updated_at' => $faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}