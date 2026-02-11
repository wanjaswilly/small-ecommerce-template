<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'addresss';
    
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'area',
        'street',
        'delivery_instructions',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}