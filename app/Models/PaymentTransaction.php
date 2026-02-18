<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $table = 'paymenttransactions';
    
    protected $fillable = [
        'order_id',
        'transaction_id',
        'payment_method',
        'amount',
        'phone',
        'mpesa_receipt_number',
        'status',
        'response_data',
        'error_message',
        'completed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'response_data' => 'array',
        'completed_at' => 'datetime'
    ];

    /**
     * Relationship with order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(array $data = []): bool
    {
        $this->status = 'completed';
        $this->completed_at = Carbon::now();
        
        if (!empty($data['mpesa_receipt_number'])) {
            $this->mpesa_receipt_number = $data['mpesa_receipt_number'];
        }
        
        if (!empty($data['response_data'])) {
            $this->response_data = $data['response_data'];
        }
        
        return $this->save();
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $errorMessage): bool
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;
        return $this->save();
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'KSh ' . number_format($this->amount, 2);
    }

    /**
     * Scope queries for different statuses
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Find transaction by transaction ID
     */
    public static function findByTransactionId(string $transactionId): ?PaymentTransaction
    {
        return static::where('transaction_id', $transactionId)->first();
    }
}