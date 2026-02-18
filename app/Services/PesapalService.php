<?php

namespace App\Services;

use App\Controllers\PesaPalController;
use App\Models\PaymentTransaction;
use App\Models\PesapalQueries;
use Exception;

class PesapalService implements PaymentInterface
{
    protected $pesapal;

    public function __construct()
    {
        $this->pesapal = new PesaPalController();
    }

    /**
     * Initialize a payment request
     */
    public function initiatePayment(array $paymentData): string
    {
        // if (!$this->validatePaymentData($paymentData)) {
        //     return ['error' => 'Invalid payment data'];
        // }

        $_ENV['APP_ENVIRONMENT']!='DEV' ? $amount=(float) $paymentData['amount'] : $amount=1;

        $paymentRequestResponse = $this->pesapal->handleThisPayment(
            localReferenceId: 'PPP-' . date('YmdHis') . '-' . rand(100, 999),
            currency: 'KES',
            amount: $amount,
            paymentDescription: 'Payment for ' . $paymentData['order_number'],
            email: $paymentData['customer_email'] ?? null,
            phoneNumber: $paymentData['phone'],
            countryCode: 'KE',
            firstName: $paymentData['first_name'],
            lastName: $paymentData['last_name'],
            orderReferenceId: $paymentData['order_number']
        );

        return $paymentRequestResponse['redirect_url'];
    }

    /**
     * Process payment callback/response
     */
    public function processCallback(array $callbackData): array
    {
        $data = $callbackData; // GET parameters for IPN

        $orderTrackingId = $data['OrderTrackingId'] ?? null;
        $orderMerchantReference = $data['OrderMerchantReference'] ?? null;

        if (!$orderTrackingId) {
            $payload = ['localPaymentReference' => $orderMerchantReference, 'status' => 'error', 'message' => 'Missing OrderTrackingId'];
            return $payload;
        }
        // Find the PesaPal query
        $pesapalQuery = PesapalQueries::with('order')->where('pesapal_order_tracking', $orderTrackingId)->first();
        // var_dump($pesapalQuery->order);
        if (!$pesapalQuery) {
            $payload = ['localPaymentReference' => $orderMerchantReference, 'status' => 'error', 'message' => 'Transaction not found'];
            return $payload;
        }

        # check if the payment has been processed
        if (PaymentTransaction::where('transaction_id', $pesapalQuery->local_payment_reference_id)->first()) {
            $payload = ['localPaymentReference' => $orderMerchantReference, 'status' => 'processed', 'message' => 'Transaction already processed'];
            return $payload;
        }

        try {

            $accessToken = $this->pesapal->accessToken;

            // Get transaction status from PesaPal
            $transacrionResponse = $this->pesapal::getPesapalTransactionStatus(
                $accessToken,
                $orderTrackingId
            );

            if ($paymentStatus = $transacrionResponse['status'] && $transacrionResponse['status'] == '200') {
                $paymentStatus = 'completed';
            }


            // Update PesaPal query
            $pesapalQuery->update([
                'ipn_notification_data' => $data,
                'payment_status' => $paymentStatus ?? 'unknown'
            ]);

            // Update order status to paid
            if ($pesapalQuery->order) {
                $pesapalQuery->order->update([
                    'payment_status' => $this->mapPaymentStatus($paymentStatus),
                    'payment_method' => 'mpesa',
                    'transaction_id' => $transacrionResponse['confirmation_code'] ?? null,
                ]);
            }

            # create payment record is status is paid
            if ($paymentStatus == 'completed') {
                PaymentTransaction::create([
                    'order_id' => $pesapalQuery->order->id,
                    'transaction_id' => $pesapalQuery->local_payment_reference_id,
                    'payment_method' => 'mpesa',
                    'amount' => $transacrionResponse['amount'] ?? $pesapalQuery->order_total,
                    'phone' => $pesapalQuery->order->customer_phone,
                    'mpesa_receipt_number'  => $transacrionResponse['confirmation_code'] ?? null,
                    'status' => $paymentStatus,
                    'response_data' => $transacrionResponse,
                ]);
            }

            $payload = ['localPaymentReference' => $pesapalQuery->local_payment_reference_id, 'status' => 'success', 'message' => 'IPN processed successfully'];
            return $payload;
        } catch (\Exception $e) {
            $payload = ['localPaymentReference' => null, 'status' => 'error', 'message' => 'Failed to process IPN :' . $e->getMessage()];

            var_dump($payload);

            return $payload;
        }
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus(string $transactionId): array
    {
        return PesaPalController::getPesapalTransactionStatus(
            $this->pesapal->accessToken,
            $transactionId
        );
    }

    /**
     * Validate payment parameters
     */
    public function validatePaymentData(array $paymentData): bool
    {
        $required = [
            'local_reference_id',
            'amount',
            'email',
            'phone',
            'first_name',
            'last_name',
        ];

        foreach ($required as $field) {
            if (empty($paymentData[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get payment method name
     */
    public function getMethodName(): string
    {
        return 'pesapal';
    }

    /**
     * Map PesaPal status to donation status
     */
    private function mapPaymentStatus(string $pesapalStatus): string
    {
        $statusMap = [
            'COMPLETED' => 'paid',
            'PENDING' => 'pending',
            'FAILED' => 'failed',
            'INVALID' => 'failed'
        ];

        return $statusMap[strtoupper($pesapalStatus)] ?? 'pending';
    }

    /**
     * Check if payment method is available
     */
    public function isAvailable(): bool
    {
        return !empty($_ENV['PESAPAL_CONSUMER_KEY'])
            && !empty($_ENV['PESAPAL_CONSUMER_SECRET']);
    }
}
