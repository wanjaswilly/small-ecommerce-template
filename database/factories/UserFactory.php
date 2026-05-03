<?php

namespace Database\Factories;

use Faker\Factory as Faker;

class UserFactory
{
    public static function make(): array
    {
        $faker = Faker::create();

        // Kenyan names and data
        $firstNames = [
            'Jomo', 'Wangari', 'Dedan', 'Mwai', 'Daniel', 'Uhuru', 'Raila', 'Kalonzo', 'Musalia', 'Martha',
            'Wanjiku', 'Njeri', 'Achieng', 'Njoroge', 'Kamau', 'Wanjiru', 'Muthoni', 'Karanja', 'Maina', 'Wairimu',
            'Oduya', 'Adhiambo', 'Atieno', 'Achieng', 'Akoth', 'Akwen', 'Chebet', 'Cherono', 'Chepkoech', 'Chepkemoi',
            'Jepkorir', 'Jeptoo', 'Kiprop', 'Kiprotich', 'Kirui', 'Koech', 'Koskei', 'Langat', 'Limo', 'Masai',
            'Ruto', 'Sang', 'Yego', 'Biwot', 'Biwott', 'Cheruiyot', 'Kipkoech', 'Kipyego', 'Korir', 'Tanui'
        ];

        $lastNames = [
            'Kenyatta', 'Maathai', 'Kiprop', 'Kibaki', 'Moi', 'Kenyatta', 'Odinga', 'Musyoka', 'Mudavadi', 'Karua',
            'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa',
            'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa',
            'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa',
            'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa', 'Wa'
        ];

        $roles = ['customer', 'customer', 'customer', 'customer', 'staff', 'admin']; // Mostly customers
        $cities = ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Malindi', 'Kitale', 'Garissa', 'Lamu'];

        $firstName = $faker->randomElement($firstNames);
        $lastName = $faker->randomElement($lastNames);
        $fullName = $firstName . ' ' . $lastName;

        // Generate Kenyan phone number (254XXXXXXXXX)
        $phone = '254' . $faker->numberBetween(700000000, 799999999);

        return [
            'email' => strtolower($firstName . '.' . $lastName . $faker->unique()->numberBetween(1, 999)) . '@example.com',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone_number' => $phone,
            'phone' => $phone, // Additional phone field
            'address' => $faker->streetAddress,
            'city' => $faker->randomElement($cities),
            'role' => $faker->randomElement($roles),
            'is_active' => $faker->boolean(95), // 95% active users
            'email_verified_at' => $faker->optional(0.8)->dateTimeBetween('-1 year', 'now'), // 80% verified
            'last_login_at' => $faker->optional(0.6)->dateTimeBetween('-6 months', 'now'), // 60% have logged in
            'created_at' => $faker->dateTimeBetween('-2 years', '-1 month'),
            'updated_at' => $faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}