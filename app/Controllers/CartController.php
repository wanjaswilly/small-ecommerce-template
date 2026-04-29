<?php

namespace App\Controllers;

use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Services\CartService;
use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class CartController extends BaseController
{
    private CartService $cartService;

    public function __construct()
    {
        $this->cartService = new CartService();
    }

    public function showCart(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'pages/mycart.twig', );
    }

    // Cart data endpoint
    public function getCartData(Request $request, Response $response): Response
    {
        return $this->json($response, $this->cartService->getCartData());
    }

    // Add to cart JSON endpoint
    public function addToCart(Request $request, Response $response): Response
    {
        return $this->json($response, $this->cartService->addToCart($request));
    }

    // Add to cart page endpoint
    public function addProductToCart(Request $request, Response $response)
    {
        return $this->redirect($response, '/products/' . $this->cartService->addProductToCart($request));
    }

    // Update cart quantity endpoint
    public function updateCart(Request $request, Response $response): Response
    {
        return $this->json($response, $this->cartService->updateCart($request));
    }

    // Remove from cart endpoint
    public function removeFromCart(Request $request, Response $response): Response
    {
        return $this->json($response, $this->cartService->removeFromCart($request));
    }

    public function getNumberOfCartItems(Request $request, Response $response): Response
    {
        return $this->json($response, $this->cartService->noOfCartItems());
    }


}
