<?php

namespace Database\Factories;

use Faker\Factory as Faker;

class OrderFactory
{
    public static function make(): array
    {
        $faker = Faker::create();

        // Kenyan counties for delivery
        $counties = ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Malindi', 'Kitale', 'Garissa', 'Lamu'];
        $subcounties = [
            'Nairobi' => ['Westlands', 'Kilimani', 'Karen', 'Langata'],
            'Mombasa' => ['Mvita', 'Changamwe', 'Kisauni', 'Nyali'],
            'Kisumu' => ['Kisumu Central', 'Nyando', 'Muhoroni'],
            'Nakuru' => ['Nakuru Town', 'Naivasha', 'Gilgil'],
            'Eldoret' => ['Soy', 'Turbo', 'Moiben'],
        ];

        $county = $faker->randomElement($counties);
        $subcounty = isset($subcounties[$county]) ? $faker->randomElement($subcounties[$county]) : $county . ' Subcounty';

        $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
        $statusWeights = [15, 20, 25, 20, 15, 5]; // Weighted distribution
        $status = $faker->randomElement($statuses, $statusWeights);

        $paymentMethods = ['mpesa', 'cash_on_delivery', 'card'];
        $paymentWeights = [70, 25, 5]; // M-Pesa most popular
        $paymentMethod = $faker->randomElement($paymentMethods, $paymentWeights);

        // Calculate realistic amounts (in KES)
        $subtotal = $faker->numberBetween(500, 50000);
        $deliveryFee = $faker->numberBetween(100, 800);
        $totalAmount = $subtotal + $deliveryFee;

        // Create order number
        $orderNumber = 'ORD' . date('Ymd') . $faker->unique()->numberBetween(10000, 99999);

        // Kenyan phone numbers
        $phone = '254' . $faker->numberBetween(700000000, 799999999);

        $createdAt = $faker->dateTimeBetween('-1 year', '-1 day');

        return [
            'user_id' => $faker->numberBetween(1, 5), // Will be set properly by seeder
            'order_number' => $orderNumber,
            'customer_name' => $faker->name,
            'customer_phone' => $phone,
            'customer_email' => $faker->email,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $totalAmount,
            'delivery_location' => $county . ', ' . $subcounty,
            'specific_address' => $faker->streetAddress,
            'delivery_notes' => $faker->optional(0.3)->sentence,
            'payment_method' => $paymentMethod,
            'payment_status' => $status === 'delivered' ? 'paid' : ($faker->boolean(80) ? 'paid' : 'pending'),
            'status' => $status,
            'coupon_id' => $faker->optional(0.2)->numberBetween(1, 3), // 20% of orders have coupons
            'paid_at' => $status === 'delivered' ? $faker->dateTimeBetween($createdAt, 'now') : null,
            'delivered_at' => $status === 'delivered' ? $faker->dateTimeBetween($createdAt, 'now') : null,
            'created_at' => $createdAt,
            'updated_at' => $faker->dateTimeBetween($createdAt, 'now'),
        ];
    }
}