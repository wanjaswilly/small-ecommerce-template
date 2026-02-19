<?php

namespace App\Controllers;


use App\Services\PaymentService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PaymentController extends BaseController
{

    private PaymentService $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
    }

    public function processPayment(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        
        return $this->redirect($response, $this->paymentService->processPayment($request));
    }

    public function paymentCallback(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        return $this->redirect($response, $this->paymentService->processCallback($request, $args['method']));
    }

    public function getPaymentMethods(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, $this->paymentService->paymentMethods());
    }

    public function updatePaymentSettings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, $this->paymentService->updatePaymentSettings($request));
    }
}
