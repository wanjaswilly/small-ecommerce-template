<?php

namespace App\Services;

interface PaymentInterface
{
    /**
     * Initialize a payment request
     */
    public function initiatePayment(array $paymentData): mixed;

    /**
     * Process payment callback/response
     */
    public function processCallback(array $callbackData): array;

    /**
     * Check payment status
     */
    public function checkPaymentStatus(string $transactionId): array;

    /**
     * Validate payment parameters
     */
    public function validatePaymentData(array $paymentData): bool;

    /**
     * Get payment method name
     */
    public function getMethodName(): string;

    /**
     * Check if payment method is available
     */
    public function isAvailable(): bool;
}