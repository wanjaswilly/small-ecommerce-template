<?php

namespace App\Services;

use App\Models\Setting;

class CashOnDeliveryService implements PaymentInterface
{
    private $config;

    public function __construct()
    {
        $this->config = Setting::getPaymentConfig('cash_on_delivery');
    }

    public function initiatePayment(array $paymentData): array
    {
        return [
            'success' => true,
            'transaction_id' => 'COD_' . uniqid(),
            'message' => 'Order placed successfully. Pay on delivery.',
            'payment_method' => 'cash_on_delivery'
        ];
    }

    public function processCallback(array $callbackData): array
    {
        return [
            'success' => true,
            'message' => 'Cash on delivery order confirmed'
        ];
    }

    public function checkPaymentStatus(string $transactionId): array
    {
        return [
            'success' => true,
            'status' => 'pending',
            'transaction_id' => $transactionId,
            'message' => 'Payment will be collected on delivery'
        ];
    }

    public function validatePaymentData(array $paymentData): bool
    {
        return !empty($paymentData['order_number']) && 
               !empty($paymentData['amount']) && 
               is_numeric($paymentData['amount']);
    }

    public function getMethodName(): string
    {
        return 'cash_on_delivery';
    }

    public function isAvailable(): bool
    {
        return !empty($this->config['enabled']);
    }

    public function isAvailableForCounty(string $county): bool
    {
        $allowedCounties = Setting::where('key', 'cod_counties')->value('value');
        
        if (!$allowedCounties) {
            // If no restriction set, allow all counties
            return true;
        }

        $allowed = json_decode($allowedCounties, true);
        
        if (!is_array($allowed)) {
            return true;
        }

        return in_array($county, $allowed);
    }
}