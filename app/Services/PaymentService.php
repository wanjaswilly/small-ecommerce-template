<?php

namespace App\Services;

use App\Exceptions\PaymentCallbackErrorException;
use App\Exceptions\ValidationException;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Models\User;
use Psr\Http\Message\ServerRequestInterface;

class PaymentService
{

    public function processPayment(ServerRequestInterface $request): string
    {
        $data = $request->getParsedBody();

        try {
            $paymentMethod = $data['payment_method'] ?? '';
            $orderData = $data['order_data'] ?? [];

            if (empty($paymentMethod) || empty($orderData)) {
                throw new \Exception('Payment method and order data are required');
            }

            $paymentService = PaymentFactory::create($paymentMethod);
            return $paymentService->initiatePayment($orderData);
        } catch (\Exception $e) {
            throw new ValidationException("Error initializing transaction", ['error' => "An error occured, error log: " . $e->getMessage()]);
        }

    }

    public function processCallback(ServerRequestInterface $request, string $methodName)
    {
        $data = $request->getParsedBody();
        try {
            #initialize the method to handle this callback
            $paymentService = PaymentFactory::create($methodName);

            # should receive a payment transaction instance
            $paymentTransaction = $paymentService->processCallback($data);# get order
            if ($order = Order::find($paymentTransaction->order_id)) {
                #redirect to the order page if logged in
                if (User::find($_SESSION['user_id'])) {
                    return '/user/account/orders/' . $order->id;
                } else {
                    # get user with the order email/phone number

                    $user = User::where('email', $order->email)->first() ?? User::where('phone_number', $order->phone)->first();

                    if ($user) {
                        # Auto-login the user after registration
                        $_SESSION['user_id'] = $user->id;
                        $_SESSION['user_email'] = $user->email;
                        $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
                        $_SESSION['user_role'] = $user->role;
                        $_SESSION['login_time'] = time();
                        $_SESSION['account'] = "checkout-created";

                        # redirect to the new account order page
                        return '/user/account/orders/' . $order->id;
                    }
                    throw new PaymentCallbackErrorException("Error Processing Callback", ['error' => $callbackResult['error'] ?? 'Payment failed']);
                }
            } else {
                throw new PaymentCallbackErrorException("Invalid Order ID", ['error' => 'There is no order with the above order ID']);
            }
        } catch (\Exception $e) {
            throw new PaymentCallbackErrorException("Error Processing Callback", ['error' => $e->getMessage()]);
        }
    }

    public function paymentMethods(): array
    {
        $availableMethods = PaymentFactory::getEnabledMethods();
        return [
            'success' => true,
            'payment_methods' => $availableMethods
        ];
    }

    public function updatePaymentSettings(ServerRequestInterface $request): array
    {
        $data = $request->getParsedBody();

        try {
            $method = $data['method'] ?? '';
            $config = $data['config'] ?? [];

            if (empty($method)) {
                throw new \Exception('Payment method is required');
            }

            $success = Setting::updatePaymentMethod($method, $config);

            return [
                'success' => $success,
                'message' => $success ? 'Payment settings updated successfully' : 'Failed to update payment settings'
            ];
        } catch (\Exception $e) {
            throw new ValidationException("Error Updating Payment Method", ['error' => $e->getMessage()]);
        }
    }

    public function updateOrderStatus(string $localReferenceId, string $transactionId, string $status): void
    {
        try {
            $order = Order::where('local_reference_id', $localReferenceId)->first();
            $order->transaction_id = $transactionId;
            $order->status = $status;
            $order->save();

            // Update order status in database
            error_log("Order Status Updated: {$transactionId} - {$status}");
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}