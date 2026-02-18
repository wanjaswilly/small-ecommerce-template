<?php

namespace App\Services;

use Exception;
use App\Models\Setting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class MpesaService implements PaymentInterface
{
    private array $config;
    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->config = Setting::getPaymentConfig('mpesa');
        $this->client = $client ?? new Client(['verify' => true, 'timeout' => 15]);
    }

    public function initiatePayment(array $paymentData): array
    {
        try {
            if (!$this->validatePaymentData($paymentData)) {
                throw new Exception('Invalid payment data');
            }

            $accessToken = $this->getAccessToken();
            if (empty($accessToken)) {
                throw new Exception('Failed to get access token from M-Pesa API');
            }

            $timestamp = date('YmdHis');
            $password = base64_encode($this->config['shortcode'] . $this->config['passkey'] . $timestamp);

            $payload = [
                'BusinessShortCode' => $this->config['shortcode'],
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int)$paymentData['amount'],
                // 'Amount' => 1,
                'PartyA' => $paymentData['phone'],
                'PartyB' => $this->config['shortcode'],
                'PhoneNumber' => $paymentData['phone'],
                'CallBackURL' => $this->config['callback_url'],
                'AccountReference' => $paymentData['order_number'],
                'TransactionDesc' => 'Payment for order ' . $paymentData['order_number'],
            ];

            $response = $this->makeRequest(
                $this->getStkPushUrl(),
                $payload,
                $accessToken
            );

            if (isset($response['ResponseCode']) && $response['ResponseCode'] === '0') {
                return [
                    'success' => true,
                    'transaction_id' => $response['CheckoutRequestID'] ?? '',
                    'message' => 'STK Push initiated successfully',
                    'response' => $response,
                ];
            }

            throw new Exception($response['ResponseDescription'] ?? 'STK Push failed');
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function processCallback(array $callbackData): array
    {
        try {
            $callback = $callbackData['Body']['stkCallback'] ?? [];
            $resultCode = $callback['ResultCode'] ?? null;
            $resultDesc = $callback['ResultDesc'] ?? '';
            $checkoutRequestId = $callback['CheckoutRequestID'] ?? '';

            if ($resultCode === 0) {
                $items = $callback['CallbackMetadata']['Item'] ?? [];
                $paymentData = [];

                foreach ($items as $item) {
                    if (isset($item['Name'])) {
                        $paymentData[$item['Name']] = $item['Value'] ?? '';
                    }
                }

                return [
                    'success' => true,
                    'transaction_id' => $checkoutRequestId,
                    'mpesa_receipt_number' => $paymentData['MpesaReceiptNumber'] ?? '',
                    'amount' => $paymentData['Amount'] ?? 0,
                    'phone' => $paymentData['PhoneNumber'] ?? '',
                    'transaction_date' => $paymentData['TransactionDate'] ?? '',
                    'message' => 'Payment completed successfully',
                ];
            }

            return [
                'success' => false,
                'transaction_id' => $checkoutRequestId,
                'error' => $resultDesc,
                'result_code' => $resultCode,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function checkPaymentStatus(string $transactionId): array
    {
        // Stub for extension — M-Pesa doesn’t provide direct query for STKPush in sandbox
        return [
            'success' => true,
            'status' => 'pending',
            'transaction_id' => $transactionId,
        ];
    }

    public function validatePaymentData(array $paymentData): bool
    {
        $required = ['amount', 'phone', 'order_number'];
        foreach ($required as $field) {
            if (empty($paymentData[$field])) {
                return false;
            }
        }

        $phone = preg_replace('/\D/', '', $paymentData['phone']);
        if (strlen($phone) !== 12 || !str_starts_with($phone, '254')) {
            return false;
        }

        return is_numeric($paymentData['amount']) && $paymentData['amount'] > 0;
    }

    public function getMethodName(): string
    {
        return 'mpesa';
    }

    public function isAvailable(): bool
    {
        return !empty($this->config['enabled'])
            && !empty($this->config['consumer_key'])
            && !empty($this->config['consumer_secret'])
            && !empty($this->config['shortcode'])
            && !empty($this->config['passkey']);
    }

    private function getAccessToken(): string
    {
        $url = $this->getAuthUrl();
        $credentials = base64_encode($this->config['consumer_key'] . ':' . $this->config['consumer_secret']);

        try {
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Basic ' . $credentials,
                    'Cache-Control' => 'no-cache',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['access_token'] ?? '';
        } catch (RequestException $e) {
            throw new Exception('Failed to get M-Pesa access token: ' . $e->getMessage());
        }
    }

    private function getAuthUrl(): string
    {
        $env = $this->config['environment'] ?? 'sandbox';
        return $env === 'production'
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    }

    private function getStkPushUrl(): string
    {
        $env = $this->config['environment'] ?? 'sandbox';
        return $env === 'production'
            ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    }

    private function makeRequest(string $url, array $data = [], string $accessToken = ''): array
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];

            if ($accessToken) {
                $headers['Authorization'] = 'Bearer ' . $accessToken;
            }

            $options = ['headers' => $headers];
            if (!empty($data)) {
                $options['json'] = $data;
            }

            $response = $this->client->post($url, $options);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (RequestException $e) {
            $errorBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            throw new Exception('M-Pesa API Request failed: ' . $errorBody);
        }
    }
}
