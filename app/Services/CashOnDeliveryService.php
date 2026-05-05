<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\Setting;

class CashOnDeliveryService implements PaymentInterface
{
    private $config;

    public function __construct()
    {
        $this->config = Setting::getPaymentConfig('cash_on_delivery');
    }

    /**
     * Creates a payment transaction with pending payment details as cash/mpesa is collected on delivery
     * Returns an array 
     * @param array $paymentData
     * @return string redirect page url
     */
    public function initiatePayment(array $paymentData): string
    {
        // return [
        //     'success' => true,
        //     'transaction_id' => 'COD_' . uniqid(),
        //     'message' => 'Order placed successfully. Pay on delivery.',
        //     'payment_method' => 'cash_on_delivery'
        // ];

        #todo: create a transaction
        # return cod+orderId url
        
        #dummy
        return "orders/cod/".$paymentData['orderId'];
    }

    public function processCallback(array $callbackData): PaymentTransaction
    {
        # todo: the callback data must have a cash reference or an mpesa transaction reference
        # todo: create an cash transaction model for the cash on delivery cash payment
        # todo: create an mpesa transaction model for the cash on delivery mpesa payment
        # todo: create a pesapal transaction model for the cash on delivery pesapal payment
        # todo: create a card transaction model for the cash on delivery card payment

        #dummy
        return new PaymentTransaction();
    }

    /**
     * checks the status of a transaction
     * @param string $transactionId unique transaction identifier
     * @return string status
     */
    public function checkPaymentStatus(string $transactionId): string
    {
        # todo : check status

        # dummy
        return "pending";
    }

    public function validatePaymentData(array $paymentData): bool | array
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