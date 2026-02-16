<?php
# app/Models/ProductTag.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTag extends Model
{
    protected $table = 'producttags';
    protected $fillable = [
        'product_id', 'tag_name', 'tag_value', 'tag_type'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}