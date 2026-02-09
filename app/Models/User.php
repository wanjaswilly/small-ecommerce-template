<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = [
        'email', 'password_hash', 'first_name', 'last_name', 
        'phone_number', 'address', 'city', 'role'
    ];

    protected $hidden = ['password_hash', 'remember_token'];

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function favourites():HasMany
    {
        return $this->hasMany(Favourite::class, 'user_id');
    }

    // Role Check Methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isRider(): bool
    {
        return $this->role === 'rider';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // Relationships
    public function staffProfile()
    {
        return $this->hasOne(Staff::class);
    }

    public function riderProfile()
    {
        return $this->hasOne(Rider::class);
    }

    public function assignedOrders()
    {
        return $this->hasMany(Order::class, 'assigned_staff_id');
    }

    public function dispatchedOrders()
    {
        return $this->hasMany(Order::class, 'rider_id');
    }
}