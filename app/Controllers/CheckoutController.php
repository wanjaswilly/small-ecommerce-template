<?php

namespace App\Controllers;

use App\Models\OrderItem;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\PaymentFactory;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Slim\Views\Twig;

class CheckoutController extends BaseController
{
    private CheckoutService $checkoutService;

    public function __construct()
    {
        $this->checkoutService = new CheckoutService();
    }

    public function showCheckout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->checkoutService->showCheckoutData();

        // Add county data for the stepper
        $countyService = new \App\Services\CountyDataService();
        $data['counties'] = $countyService->getCounties();

        // Initialize checkout session if not exists
        if (!isset($_SESSION['checkout'])) {
            $_SESSION['checkout'] = [
                'step' => 1,
                'address' => [],
                'payment' => []
            ];
        }

        $data['current_step'] = $_SESSION['checkout']['step'];
        $data['checkout_data'] = $_SESSION['checkout'];

        return $this->render($request, $response, 'pages/checkout.twig', $data);
    }

    public function processStep(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $step = $data['step'] ?? 1;

        if (!isset($_SESSION['checkout'])) {
            $_SESSION['checkout'] = ['step' => 1, 'address' => [], 'payment' => []];
        }

        switch ($step) {
            case 1: // Address step
                $_SESSION['checkout']['address'] = [
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'county' => $data['county'],
                    'subcounty' => $data['subcounty'],
                    'specific_address' => $data['specific_address'],
                    'delivery_notes' => $data['delivery_notes'] ?? null,
                ];
                $_SESSION['checkout']['step'] = 2;
                break;

            case 2: // Payment step
                // Validate COD availability for county
                $county = $_SESSION['checkout']['address']['county'];
                $codService = new \App\Services\CashOnDeliveryService();
                $codAvailable = $codService->isAvailableForCounty($county);

                if ($data['payment_method'] === 'cash_on_delivery' && !$codAvailable) {
                    $_SESSION['error'] = ['Cash on Delivery is not available for your county'];
                    return $this->redirect($response, '/checkout');
                }

                $_SESSION['checkout']['payment'] = [
                    'payment_method' => $data['payment_method'],
                ];
                $_SESSION['checkout']['step'] = 3;
                break;

            case 3: // Confirm step - process order
                return $this->processCheckout($request, $response);
        }

        return $this->redirect($response, '/checkout');
    }

    public function previousStep(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (isset($_SESSION['checkout']['step']) && $_SESSION['checkout']['step'] > 1) {
            $_SESSION['checkout']['step']--;
        }
        return $this->redirect($response, '/checkout');
    }

    public function confirmDetailsBeforePayment(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'pages/checkout-confirm.twig', $this->checkoutService->confirmCheckout());
    }

    public function processCheckout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Combine checkout data with cart data
        $checkoutData = $_SESSION['checkout']['address'];
        $checkoutData['payment_method'] = $_SESSION['checkout']['payment']['payment_method'];

        return $this->redirect($response, $this->checkoutService->processCheckout($request, $checkoutData));
    }
}