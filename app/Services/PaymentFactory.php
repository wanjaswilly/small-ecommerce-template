<?php

namespace App\Services;

use Exception;
use App\Models\Setting;

class PaymentFactory
{
    public static function create(string $paymentMethod): PaymentInterface
    {
        $paymentMethod = strtolower($paymentMethod);
        
        // Check if method is enabled in settings
        $availableMethods = Setting::getPaymentMethods();
        
        if (!isset($availableMethods[$paymentMethod])) {
            throw new Exception("Payment method '{$paymentMethod}' is not enabled");
        }

        $serviceClass = 'App\\Services\\' . self::getServiceClassName($paymentMethod);
        
        if (!class_exists($serviceClass)) {
            throw new Exception("Payment service '{$serviceClass}' not found");
        }

        $service = new $serviceClass();

        if (!$service instanceof PaymentInterface) {
            throw new Exception("Payment service must implement PaymentInterface");
        }

        if (!$service->isAvailable()) {
            throw new Exception("Payment method '{$paymentMethod}' is not properly configured");
        }

        return $service;
    }

    public static function getAvailableMethods(): array
    {
        return Setting::getPaymentMethods();
    }

    public static function getEnabledMethods(): array
    {
        $methods = Setting::getPaymentMethods();
        $enabled = [];

        foreach ($methods as $method => $config) {
            if (!empty($config['enabled'])) {
                $enabled[$method] = $config;
            }
        }

        return $enabled;
    }

    private static function getServiceClassName(string $paymentMethod): string
    {
        $mapping = [
            'mpesa' => 'MpesaService',
            'cash_on_delivery' => 'CashOnDeliveryService',
            'card' => 'CardService',
        ];

        return $mapping[$paymentMethod] ?? ucfirst($paymentMethod) . 'Service';
    }
}