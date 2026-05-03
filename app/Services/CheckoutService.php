<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Exceptions\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Database\Capsule\Manager as DB;

class CheckoutService
{
    private CartService $cartService;

    public function __construct()
    {
        $this->cartService = new CartService();
    }
    public function showCheckoutData(): array
    {
        # Get cart data from session
        $cartData = $this->cartService->getCart();

        # Redirect to cart if empty
        if (empty($cartData['items'])) {
            throw new ValidationException("Empty Cart", ['error' => 'You cannot proceed to checkout with an empty cart']);
        }

        $user = User::find($_SESSION['user_id']) ?? null;

        # Calculate delivery fee (placeholder - will be calculated based on location in stepper)
        $deliveryFee = 200; // Default fee
        $discountAmount = $_SESSION['cart_discount'] ?? 0;
        $finalTotal = $cartData['total_price'] + $deliveryFee - $discountAmount;

        return [
            'cart_items' => $cartData['items'],
            'cart_total' => $cartData['total_price'],
            'cart_total_quantity' => $cartData['total_quantity'],
            'delivery_fee' => $deliveryFee,
            'discount_amount' => $discountAmount,
            'final_total' => $finalTotal,
            'user' => $user,
        ];
    }

    public function confirmCheckout():array
    {
        return [];
    }

    public function processCheckout(ServerRequestInterface $request, ?array $checkoutData = null): mixed
    {
        $data = $checkoutData ?: $request->getParsedBody();

        # Validate required fields
        $requiredFields = ['full_name', 'phone', 'county', 'subcounty', 'specific_address', 'payment_method'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new ValidationException("Incomplete Form", ['error' => "Please fill in all required fields: " . $field]);
            }
        }

        # User should already be authenticated via middleware, but let's ensure they exist
        $user = User::find($_SESSION['user_id']);
        if (!$user) {
            throw new ValidationException("Authentication Required", ['error' => 'Please log in to complete your order']);
        }

        try {

            DB::beginTransaction();

            # Calculate delivery fee based on county
            $deliveryFee = $this->calculateDeliveryFee($data['county']);

            # Apply coupon discount if available
            $discountAmount = $_SESSION['cart_discount'] ?? 0;

            # Create order first
            $order = $this->createOrder($data, $deliveryFee, $discountAmount);

            if (!$order) {
                $_SESSION;
                throw new ValidationException("Order not Created", ['error' => "Failed to create order record. Kindly try again."]);
            }

            # Prepare payment data
            $paymentData = [
                'amount' => $order['total_amount'],
                'phone' => $this->formatPhoneNumber($data['phone']),
                'order_number' => $order['order_number'],
                'order_id' => $order['id'],
            ];

            # Get current payment method
            $defaultPaymentMethod = env('DEFAULT_PAYMENT_METHOD') ?? 'mpesa';

            # Process payment of mpesa
            if ($defaultPaymentMethod == 'mpesa') {
                $paymentService = PaymentFactory::create($defaultPaymentMethod);
                $paymentResult = $paymentService->initiatePayment($paymentData);

                if ($paymentResult['success']) {
                    # Clear cart after successful order creation
                    $_SESSION['cart'] = [];

                    return [
                        'success' => true,
                        'message' => $paymentResult['message'],
                        'order_id' => $order['id'],
                        'transaction_id' => $paymentResult['transaction_id'],
                        'payment_method' => $defaultPaymentMethod,
                        'requires_action' => $data['payment_method'] === 'mpesa' # M-Pesa requires user action
                    ];
                    DB::commit();
                } else {
                    DB::rollBack();
                    $error = $paymentResult['error'] ?? 'Payment initiation failed';
                    throw new ValidationException("Error During Checkout", [
                        'error' => "Some error occured during checkout process: " . json_encode($error),
                    ]);
                }
            } elseif ($defaultPaymentMethod == 'pesapal') {
                $paymentService = PaymentFactory::create($defaultPaymentMethod);
                $redirectUrl = $paymentService->initiatePayment($paymentData);

                # Redirect to PesaPal payment page
                if (empty($redirectUrl)) {
                    DB::rollBack();
                    throw new ValidationException("Error During Checkout", [
                        'error' => "Some error occured during checkout process: Error redirecting to payment page"
                    ]);
                }

                $_SESSION['cart'] = [];
                DB::commit();
                return $redirectUrl;
            } else {
                DB::rollBack();
                $error = 'Payment initiation failed';
                $_SESSION['error'] = "Some error on checkout: " . json_encode($error);
                throw new ValidationException("Error During Checkout", [
                    'error' => "Some error occured during checkout process: " . json_encode($error),
                ]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw new ValidationException("Error During Checkout", [
                'error' => "Some error occured during checkout process: " . $e->getMessage(),
            ]);
        }
    }
    private function formatPhoneNumber(string $phone): string
    {
        # Remove any non-digit characters
        $phone = preg_replace('/\D/', '', $phone);

        # Convert to 254 format if it's in local format
        if (strlen($phone) === 9 && (str_starts_with($phone, '7') || str_starts_with($phone, '1'))) {
            $phone = '254' . $phone;
        } elseif (strlen($phone) === 10 && (str_starts_with($phone, '07' || str_starts_with($phone, '01')))) {
            $phone = '254' . substr($phone, 1);
        }

        return $phone;
    }

    private function createOrder(array $data, float $deliveryFee, float $discountAmount = 0)
    {
        $cartData = $this->cartService->getCart();

        # Apply free delivery threshold
        $deliveryFee = $cartData['total_price'] >= 20000 ? 0 : $deliveryFee;
        $totalAmount = $cartData['total_price'] + $deliveryFee - $discountAmount;

        # Create order data
        $orderData = [
            'user_id' => $_SESSION['user_id'],
            'order_number' => Order::generateOrderNumber(),
            'customer_name' => $data['full_name'],
            'customer_phone' => $data['phone'],
            'customer_email' => $data['email'] ?? null,
            'delivery_location' => $data['county'] . ', ' . $data['subcounty'],
            'specific_address' => $data['specific_address'],
            'delivery_notes' => $data['delivery_notes'] ?? null,
            'payment_method' => $data['payment_method'],
            'subtotal' => $cartData['total_price'],
            'delivery_fee' => $deliveryFee,
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ];

        # Apply coupon if available
        if (isset($_SESSION['cart_coupon'])) {
            $couponService = new CouponService();
            $couponService->applyCoupon(Order::create($orderData), $_SESSION['cart_coupon']);
        }

        # Save order to database  
        $order = Order::create($orderData);
        if ($order) {
            try {
                # save order items
                foreach ($cartData['items'] as $item) {

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'quantity' => $item['quantity'],
                        'attributes' => json_encode($item['variant']),
                        'unit_price' => (float) $item['product']->price,
                        'total_price' => ((float) $item['product']->price) * $item['quantity']
                    ]);
                }
            } catch (Exception $e) {
                throw new ValidationException('Error Creating Order', ['error' => "Error occured saving order items:" . $e->getMessage()]);
            }
        }
        return $order;
    }

    private function calculateDeliveryFee(string $county): float
    {
        // Simple delivery fee calculation based on county
        $cbdCounties = ['Nairobi', 'Kiambu'];
        $nearbyCounties = ['Machakos', 'Kajiado', 'Murang\'a', 'Nyeri'];

        if (in_array($county, $cbdCounties)) {
            return 200;
        } elseif (in_array($county, $nearbyCounties)) {
            return 300;
        } else {
            return 500;
        }
    }

}
