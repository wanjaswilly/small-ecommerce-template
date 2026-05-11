<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $table = 'shipping_zones';

    protected $fillable = [
        'name',
        'locations',
        'base_fee',
        'per_kg_fee',
        'free_shipping_threshold',
        'estimated_days',
        'shipping_method',
    ];

    protected $casts = [
        'locations' => 'array',
        'base_fee' => 'decimal:2',
        'per_kg_fee' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'estimated_days' => 'integer',
    ];

    public static function getDefaultZones(): array
    {
        return [
            [
                'name' => 'Nairobi CBD',
                'locations' => ['Nairobi CBD', 'Westlands', 'Kilimani', 'Kileleshwa', 'Lavington', 'Karen', 'Langata'],
                'base_fee' => 200,
                'per_kg_fee' => 50,
                'free_shipping_threshold' => 20000,
                'estimated_days' => 1,
                'shipping_method' => 'Express',
            ],
            [
                'name' => 'Nairobi Outer',
                'locations' => ['Embakasi', 'Kasarani', 'Roysambu', 'Mathare', 'Dandora', 'Kahawa', 'Ruiru'],
                'base_fee' => 300,
                'per_kg_fee' => 60,
                'free_shipping_threshold' => 25000,
                'estimated_days' => 2,
                'shipping_method' => 'Standard',
            ],
            [
                'name' => 'Kenya Major Towns',
                'locations' => ['Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Nyeri', 'Machakos'],
                'base_fee' => 500,
                'per_kg_fee' => 100,
                'free_shipping_threshold' => 30000,
                'estimated_days' => 3,
                'shipping_method' => 'Standard',
            ],
            [
                'name' => 'Kenya Rural',
                'locations' => ['all'],
                'base_fee' => 800,
                'per_kg_fee' => 150,
                'free_shipping_threshold' => 40000,
                'estimated_days' => 5,
                'shipping_method' => 'Economy',
            ],
        ];
    }
}