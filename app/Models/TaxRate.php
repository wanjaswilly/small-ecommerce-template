<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $table = 'tax_rates';

    protected $fillable = [
        'name',
        'rate',
        'tax_class',
        'applicable_zones',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
        'applicable_zones' => 'array',
    ];

    public static function getDefaultRates(): array
    {
        return [
            [
                'name' => 'VAT Standard',
                'rate' => 16.00,
                'tax_class' => 'standard',
                'applicable_zones' => ['all'],
                'is_active' => true,
            ],
            [
                'name' => 'VAT Reduced',
                'rate' => 8.00,
                'tax_class' => 'reduced',
                'applicable_zones' => ['all'],
                'is_active' => true,
            ],
            [
                'name' => 'Zero Rate',
                'rate' => 0.00,
                'tax_class' => 'zero',
                'applicable_zones' => ['all'],
                'is_active' => true,
            ],
        ];
    }
}