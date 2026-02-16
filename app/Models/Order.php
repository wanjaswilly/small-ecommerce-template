<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'order_number',
        'subtotal',
        'delivery_fee',
        'total_amount',
        'delivery_location',
        'specific_address',
        'delivery_notes',
        'payment_method',
        'transaction_id',
        'mpesa_receipt_number',
        'payment_status',
        'status',
        'paid_at',
        'assigned_staff_id',
        'rider_id',
        'delivered_at',
        'processing_started_at',
        'processed_at',
        'dispatched_at',
        'out_for_delivery_at',
        'cancelled_at',
        'tracking_number',
        'vehicle_type',
        'dispatch_notes',
        'internal_notes',
        'package_weight',
        'package_dimensions',
        'packaging_notes',
        'delivery_attempts',
        'delivery_failed_reason',
        'cod_collected',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'delivered_at' => 'datetime'
    ];

    /**
     * Relationship with order items
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relationship with payment transactions
     */
    public function paymentTransactions(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class);
    }

    /**
     * Relationship with user 
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        return $prefix . $date . $random;
    }

    /**
     * Create order from cart data and customer information
     */
    public static function createFromCheckout(array $checkoutData, array $cartData): Order
    {
        # Calculate delivery fee based on location and total
        $deliveryFee = self::calculateDeliveryFee(
            $cartData['total_price'],
            $checkoutData['location']
        );

        $totalAmount = $cartData['total_price'] + $deliveryFee;

        # Create order
        $order = self::create([
            'user_id' => $_SESSION['id'] ? $_SESSION['id'] : null,
            'customer_name' => $checkoutData['full_name'],
            'customer_phone' => $checkoutData['phone'],
            'customer_email' => $checkoutData['email'] ?? null,
            'order_number' => self::generateOrderNumber(),
            'subtotal' => $cartData['total_price'],
            'delivery_fee' => $deliveryFee,
            'total_amount' => $totalAmount,
            'delivery_location' => $checkoutData['location'],
            'specific_address' => $checkoutData['specific_address'] ?? null,
            'delivery_notes' => $checkoutData['delivery_notes'] ?? null,
            'payment_method' => $checkoutData['payment_method'],
            'payment_status' => 'pending',
            'status' => 'pending'
        ]);

        # Create order items
        foreach ($cartData['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product']['id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['product']['price'],
                'total_price' => $item['product']['price'] * $item['quantity']
            ]);
        }

        return $order;
    }

    /**
     * Calculate delivery fee based on location and order total
     */
    public static function calculateDeliveryFee(float $subtotal, string $location): float
    {
        $settings = Setting::getByGroup('shipping');

        # Free delivery for orders above threshold
        $freeDeliveryThreshold = (float) ($settings['free_delivery_threshold'] ?? 20000);
        if ($subtotal >= $freeDeliveryThreshold) {
            return 0;
        }

        # Check if location is in CBD areas
        $cbdAreas = ['Nairobi CBD', 'Westlands', 'Kilimani', 'Kileleshwa', 'Lavington', 'Karen', 'Langata'];
        $isCBD = in_array($location, $cbdAreas);

        if ($isCBD) {
            return (float) ($settings['cbd_delivery_fee'] ?? 200);
        } else {
            return (float) ($settings['outside_cbd_delivery_fee'] ?? 300);
        }
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(string $status, array $paymentData = []): bool
    {
        $this->payment_status = $status;

        if ($status === 'paid') {
            $this->paid_at = Carbon::now();
            $this->status = 'confirmed'; # Move to confirmed when paid

            if (!empty($paymentData['mpesa_receipt_number'])) {
                $this->mpesa_receipt_number = $paymentData['mpesa_receipt_number'];
            }

            if (!empty($paymentData['transaction_id'])) {
                $this->transaction_id = $paymentData['transaction_id'];
            }
        }

        return $this->save();
    }

    /**
     * Update order status with optional notes
     */
    public function updateStatus(string $status, string $notes = null): bool
    {
        $this->status = $status;

        if ($status === 'delivered') {
            $this->delivered_at = Carbon::now();
        }

        # You could log status changes here
        return $this->save();
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * Get formatted order total
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'KSh ' . number_format($this->total_amount, 2);
    }

    /**
     * Get formatted delivery fee
     */
    public function getFormattedDeliveryFeeAttribute(): string
    {
        if ($this->delivery_fee == 0) {
            return 'FREE';
        }
        return 'KSh ' . number_format($this->delivery_fee, 2);
    }

    /**
     * Scope queries for different statuses
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Find order by order number
     */
    public static function findByOrderNumber(string $orderNumber): ?Order
    {
        return static::where('order_number', $orderNumber)->first();
    }

    /**
     * Get orders for a specific phone number (for guest order lookup)
     */
    public static function getByPhone(string $phone): array
    {
        return static::where('customer_phone', $phone)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}
