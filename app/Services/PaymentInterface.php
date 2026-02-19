<?php

namespace App\Services;

use App\Models\PaymentTransaction;

interface PaymentInterface
{
    /**
     * Initialize a payment request, save response to paymentTransaction and method model.
     * Both the paymentTransaction and method model will be fully filled by the processCallback method
     * @param array $paymentData all apyment data required by a given payment service
     * @return string redirect url for the processing/waiting of transaction
     */
    public function initiatePayment(array $paymentData): mixed;

    /**
     * Process payment callback/response, update the order, fill method model and paymentTransaction
     * @param array $callbackData payment response with all payment info
     * @return PaymentTransaction instance of the payment transaction.
     * 
     */
    public function processCallback(array $callbackData): ?PaymentTransaction;

    /**
     * Check payment status
     * @param string $transactionId the id to check
     * @return string status of the payment
     */
    public function checkPaymentStatus(string $transactionId): string;

    /**
     * Validate payment parameters
     * @param array $paymentData all apyment data required by a given payment service
     * @return bool validation status: pass or fail
     */
    public function validatePaymentData(array $paymentData): bool;

    /**
     * Get payment method name
     * @return string method name
     */
    public function getMethodName(): string;

    /**
     * Check if payment method is available
     */
    public function isAvailable(): bool;
}