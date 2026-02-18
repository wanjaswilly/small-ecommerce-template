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
            throw new ValidationException("Empty Cart", ['error' => 'You canot proceed to checkout with an empty cart']);
        }

        $user = User::find($_SESSION['user_id']) ?? null;


        return [
            'cart_items' => $cartData['items'],
            'cart_total' => $cartData['total_price'],
            'cart_total_quantity' => $cartData['total_quantity'],
            'user' => $user,
        ];
    }

    public function confirmCheckout():array
    {
        return [];
    }

    public function processCheckout(ServerRequestInterface $request): mixed
    {
        $data = $request->getParsedBody();

        # Validate required fields
        $requiredFields = ['first_name', 'first_name', 'email', 'phone', 'location'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new ValidationException("Incomplete Form", ['error' => "Please fill in all required fields: " . $field]);
            }
        }

        # if the user has no account, create account for them with the data
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            # check if user exists with the email or phone number
            if (!User::where('email', $data['email'])->exists() || !User::where('phone_number', $data['phone'])->exists()) {
                # Create new user
                $user = User::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone_number' => $data['phone'],
                    'password_hash' => password_hash('password1234X', PASSWORD_DEFAULT),
                    'role' => 'customer',
                    'is_active' => true,
                    'email_verified_at' => null, # Will need verification
                ]);

                if ($user) {
                    # Auto-login the user after registration
                    $_SESSION['user_id'] = $user->id;
                    $_SESSION['user_email'] = $user->email;
                    $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
                    $_SESSION['user_role'] = $user->role;
                    $_SESSION['login_time'] = time();
                    $_SESSION['account'] = "checkout-created";
                }
            } else {
                $user = User::where('email', $data['email'])->first() ?? User::where('phone_number', $data['phone'])->first();
                $_SESSION['user_id'] = $user->id;
            }
        }

        try {

            DB::beginTransaction();

            $deliveryFees = [
                'CBD' => 150,
                'Upper Hill' => 200,
                'Parklands' => 250,
                'Westlands' => 300,
                'Eastleigh' => 300,
                'Kilimani' => 350,
                'Roysambu' => 400,
                'Kileleshwa' => 400,
                'Langata' => 400,
                'Uthiru' => 500,
                'Kasarani' => 500,
                'Ruaka' => 500,
                'Kahawa' => 500,
                'Ruiru' => 600,
                'Kikuyu' => 600,
                'Karen' => 650,
                'Syokimau' => 800,
                'Rongai' => 900,
                'Kitengela' => 1000,
                'Utawala' => 1000,
                'Thika' => 1300,
                'Others' => 1500,
                'Outside' => 500,
                'Express' => 1500,
                'Pickup' => 50,
            ];

            # Create order first
            $order = $this->createOrder($data, $deliveryFees[$data['location']]);

            if (!$order) {
                $_SESSION;
                throw new ValidationException("Order not Created", ['error' => "Failed to create order record. Kindly try again."]);
            }

            # Prepare payment data
            $paymentData = [
                'amount' => $order['total_amount'],
                'phone' => $this->formatPhoneNumber($data['phone']),
                'order_number' => $order['order_number'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'customer_email' => $data['email'] ?? null
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

    private function createOrder(array $data, float $deliveryFee)
    {
        $cartData = $this->cartService->getCart();

        # Calculate delivery fee
        $deliveryFee = $cartData['total_price'] >= 20000 ? 0 : $deliveryFee;
        $totalAmount = $cartData['total_price'] + $deliveryFee;

        # Create order data
        $orderData = [
            'user_id' => $_SESSION['user_id'],
            'order_number' => 'ORD' . date('YmdHis') . rand(100, 999),
            'customer_name' => $data['first_name'] . ' ' . $data['last_name'],
            'customer_phone' => $data['phone'],
            'customer_email' => $data['email'] ?? null,
            'delivery_location' => $data['location'],
            'specific_address' => $data['specific_address'] ?? null,
            'delivery_notes' => $data['delivery_notes'] ?? null,
            'payment_method' => env('DEFAULT_PAYMENT_METHOD'),
            'subtotal' => $cartData['total_price'],
            'delivery_fee' => $deliveryFee,
            'total_amount' => $totalAmount,
            # 'items' => $cartData['items'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];

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

    public function checkoutSuccess(ServerRequestInterface $request): array
    {
        $orderId = $request->getQueryParams()['order'] ?? null;
        return ['order_id' => $orderId];
    }
}
