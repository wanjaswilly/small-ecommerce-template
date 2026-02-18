<?php

namespace App\Services;

use App\Controllers\PesaPalController;
use App\Exceptions\PaymentCallbackErrorException;
use App\Models\PaymentTransaction;
use App\Models\PesapalQueries;
use App\Models\Setting;
use Exception;
use GuzzleHttp\Client;

class PesapalService implements PaymentInterface
{

    protected $consumerKey;
    protected $consumerSecret;
    protected $callbackUrl;
    protected $ipnUrl;
    public $accessToken;
    protected $baseUrl;
    protected $http;

    public function __construct()
    {
        //  Get pesapal settings
        $pesapalConfigs = Setting::getPaymentConfig('pesapal');

        // set credentials
        $this->ipnUrl = $pesapalConfigs['callback_url'];
        $this->consumerKey = $pesapalConfigs['consumer_key'];
        $this->consumerSecret = $pesapalConfigs['consumer_secret'];
        $this->callbackUrl = $pesapalConfigs['callback_url'];
        $this->baseUrl = $pesapalConfigs['environment'] === 'dev' ?
            "https://pay.pesapal.com/v3/api/" :
            "https://cybqa.pesapal.com/pesapalv3/api/";

        // var_dump([$this->consumerSecret, $this->consumerKey]);

        // get the access token
        $this->http = new Client(['base_uri' => $this->baseUrl]);
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Initialize a payment request
     */
    public function initiatePayment(array $paymentData): string
    {
        // if (!$this->validatePaymentData($paymentData)) {
        //     return ['error' => 'Invalid payment data'];
        // }

        $_ENV['APP_ENVIRONMENT'] != 'DEV' ? $amount = (float) $paymentData['amount'] : $amount = 1;

        $paymentRequestResponse = $this->handleThisPayment(
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
    public function processCallback(array $callbackData): ?PaymentTransaction
    {
        $data = $callbackData; // GET parameters for IPN

        $orderTrackingId = $data['OrderTrackingId'] ?? null;
        $orderMerchantReference = $data['OrderMerchantReference'] ?? null;

        if (!$orderTrackingId) {
            $payload = ['localPaymentReference' => $orderMerchantReference, 'status' => 'error', 'message' => 'Missing OrderTrackingId'];

            throw new PaymentCallbackErrorException("Invalid Order Tracking ID", $payload);
        }
        // Find the PesaPal query
        $pesapalQuery = PesapalQueries::with('order')->where('pesapal_order_tracking', $orderTrackingId)->first();
        // var_dump($pesapalQuery->order);
        if (!$pesapalQuery) {
            $payload = ['localPaymentReference' => $orderMerchantReference, 'status' => 'error', 'message' => 'initial transaction not found with the provided local reference'];

            throw new PaymentCallbackErrorException("Missing Local Reference ", $payload);
        }

        # check if the payment has been processed
        if ($transaction = PaymentTransaction::where('transaction_id', $pesapalQuery->local_payment_reference_id)->first()) {
            $payload = ['localPaymentReference' => $orderMerchantReference, 'status' => 'processed', 'message' => 'Transaction already processed'];
            return $transaction;
        }

        try {

            $accessToken = $this->accessToken;

            // Get transaction status from PesaPal
            $transactionResponse = $this->getPesapalTransactionStatus($accessToken, $orderTrackingId);

            if ($paymentStatus = $transactionResponse['status'] && $transactionResponse['status'] == '200') {
                $paymentStatus = 'completed';
            }


            // Update PesaPal query
            $pesapalQuery->update(['ipn_notification_data' => $data,'payment_status' => $paymentStatus ?? 'unknown']);

            // Update order status to paid
            if ($pesapalQuery->order) {
                $pesapalQuery->order->update([
                    'payment_status' => $this->mapPaymentStatus($paymentStatus),
                    'payment_method' => 'mpesa',
                    'transaction_id' => $transactionResponse['confirmation_code'] ?? null,
                ]);
            }

            # create payment record is status is paid
            if ($paymentStatus == 'completed') {
                return PaymentTransaction::create([
                    'order_id' => $pesapalQuery->order->id,
                    'transaction_id' => $pesapalQuery->local_payment_reference_id,
                    'payment_method' => 'mpesa',
                    'amount' => $transactionResponse['amount'] ?? $pesapalQuery->order_total,
                    'phone' => $pesapalQuery->order->customer_phone,
                    'mpesa_receipt_number' => $transactionResponse['confirmation_code'] ?? null,
                    'status' => $paymentStatus,
                    'response_data' => $transactionResponse,
                ]);
            }
        } catch (Exception $e) {
            $payload = ['localPaymentReference' => null, 'status' => 'error', 'message' => 'Failed to process IPN :' . $e->getMessage()];

            throw new PaymentCallbackErrorException("Error processing callback", $payload);
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


    #################################################################
    ###################### PAYMENT HELPER METHODS ###################
    #################################################################

    private function getAccessToken(): string
    {
        $response = $this->http->post('Auth/RequestToken', [
            'json' => [
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret,
            ],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['token'] ?? throw new Exception('Access token not returned' . var_dump($data));
    }

    private function registerPesapalIPN(string $accessToken)
    {
        try {
            $response = $this->http->post('URLSetup/RegisterIPN', [
                'json' => [
                    'url' => $this->ipnUrl,
                    'ipn_notification_type' => 'GET',
                ],
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['ipn_id'] ?? throw new Exception('IPN registration failed' . json_encode($data['error']));
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getPesapalRegisteredIPNs()
    {
        try {
            $response = $this->http->get('URLSetup/GetIpnList', [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Accept' => 'application/json',
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function submitPesapalOrderRequest(
        string $localReferenceId,
        string $ipnID,
        string $currency = 'KES',
        float $amount = 1000.0,
        string $paymentDescription = 'Test Payment',
        string $email = 'john.doe@example.com',
        string $phoneNumber = '0723123456',
        string $countryCode = 'KE',
        string $firstName = 'John',
        string $lastName = 'Doe'
    ) {
        try {
            $response = $this->http->post('Transactions/SubmitOrderRequest', [
                'json' => [
                    'id' => $localReferenceId,
                    'currency' => $currency,
                    'amount' => $amount,
                    'description' => $paymentDescription,
                    'callback_url' => $this->callbackUrl,
                    'notification_id' => $ipnID,
                    'billing_address' => [
                        'email_address' => $email,
                        'phone_number' => $phoneNumber,
                        'country_code' => $countryCode,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                    ],
                ],
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private static function getPesapalTransactionStatus(string $accessToken, string $orderTrackingId)
    {
        $client = new Client(['base_uri' => 'https://cybqa.pesapal.com/pesapalv3/api/']);

        try {
            $response = $client->get("Transactions/GetTransactionStatus?orderTrackingId={$orderTrackingId}", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Accept' => 'application/json',
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function handleThisPayment(
        string $localReferenceId,
        string $currency = 'USD',
        float $amount = 1.0,
        string $paymentDescription = 'Test Payment',
        string $email = 'john.doe@example.com',
        string $phoneNumber = '0723123456',
        string $countryCode = 'KE',
        string $firstName = 'John',
        string $lastName = 'Doe',
        string $orderReferenceId
    ): array {
        $ipnID = $this->registerPesapalIPN($this->accessToken);

        if (isset($ipnID['error'])) {
            return ['error' => 'IPN Registration failed: ' . $ipnID['error']];
        }

        $paymentResponse = $this->submitPesapalOrderRequest(
            $localReferenceId,
            $ipnID,
            $currency,
            $amount,
            $paymentDescription,
            $email,
            $phoneNumber,
            $countryCode,
            $firstName,
            $lastName
        );


        if (isset($paymentResponse['error'])) {
            $payload = [
                'status' => 'error',
                'message' => 'Payment initiation failed in pesapal' . json_encode($paymentResponse),
                'error' => $paymentResponse
            ];



            return ($payload);
        }

        # record the payment cor callback processing
        PesapalQueries::create([
            'local_payment_reference_id' => $localReferenceId,
            'pesapal_order_tracking' => $paymentResponse['order_tracking_id'],
            'order_number' => $orderReferenceId,
            'order_total' => $amount,
        ]);

        return $paymentResponse;
    }

}
