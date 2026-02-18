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
        return $this->render($request, $response, 'pages/checkout.twig', $this->checkoutService->showCheckoutData());
    }

    public function confirmDetailsBeforePayment(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'pages/checkout-confirm.twig', $this->checkoutService->confirmCheckout());
    }

    public function processCheckout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->redirect($response, $this->checkoutService->processCheckout($request));
    }
}