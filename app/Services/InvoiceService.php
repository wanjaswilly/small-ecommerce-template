<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Slim\Views\Twig;

class InvoiceService
{
    protected Twig $twig;

    public function __construct(Twig $twig)
    {
        $this->twig = $twig;
    }

    public function generateHtml(Order $order): string
    {
        $kraPin = Setting::where('key', 'kra_pin')->value('value') ?? '_______________';

        $data = [
            'order' => $order,
            'kra_pin' => $kraPin,
            'vat_rate' => 16.0,
        ];

        $response = new \Slim\Psr7\Response();
        return $this->twig->render($response, 'admin/invoice.twig', $data)->getBody()->getContents();
    }
}