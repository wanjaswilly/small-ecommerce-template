<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'products';
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'sku',
        'stock_quantity',
        'min_stock_level',
        'images',
        'attributes',
        'is_active',
        'brand_id',
        'category_id',
        'featured',
        'new_arrival',
        'special_offer',
        'product_views'
    ];

    protected $casts = [
        'images' => 'array',
        'attributes' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'featured' => 'boolean',
        'new_arrival' => 'boolean',
        'special_offer' => 'boolean'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->hasMany(ProductTag::class, 'product_id');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true)
            ->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    public function getSpecs(): array
    {
        $attributes = $this->getAttribute('attributes') ?? [];
        if (is_array($attributes)) {
            return $attributes;
        }

        return $attributes ? json_decode($attributes, true) : [];
    }

    // Removed electronics and carPart relationships - now using attributes field

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }


    // Get discount percentage
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->price > 0) {
            return (($this->price - $this->discount_price) / $this->price) * 100;
        }
        return 0;
    }
}
