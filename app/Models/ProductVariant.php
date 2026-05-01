<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table = 'productvariants';

    protected $fillable = [
        'product_id',
        'sku',
        'combination',
        'price_adjustment',
        'stock_quantity',
        'image',
    ];

    protected $casts = [
        'combination' => 'array',
        'price_adjustment' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}