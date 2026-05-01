<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Product;
use Exception;
use Psr\Http\Message\ServerRequestInterface;

class CartService
{
    public function showCartData(): array
    {
        $cartData = $this->getCart();
        return [
            'cart_items' => $cartData['items'],
            'cart_total' => $cartData['total_price'],
            'cart_total_quantity' => $cartData['total_quantity']
        ];
    }

    public function getCartData()
    {
        $cart = $this->getCart();
        return ['success' => true, 'cart' => $cart];
    }

    public function addToCart(ServerRequestInterface $request): array
    {
        $data = $request->getParsedBody();
        $productId = $data['product_id'] ?? null;
        $quantity = $data['quantity'] ?? 1;
        $variant = $data['variant'] ?? [];

        if (!$productId) {
            return ['success' => false, 'message' => 'Product ID is required'];
        }

        $product = Product::find($productId);

        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        if ($product->stock_quantity < $quantity) {
            return ['success' => false, 'message' => 'Insufficient stock available'];
        }

        $cart = $this->addItemToCart($productId, $quantity, $variant);

        return ['success' => true, 'message' => 'Item added to cart', 'cart' => $cart];

    }

    public function addProductToCart(ServerRequestInterface $request): string
    {
        try {
            $data = $request->getParsedBody();
            $productId = $data['product_id'] ?? null;
            $quantity = $data['quantity'] ?? 1;
            $variant = $data['variant'] ?? [];

            if (!$productId) {
                throw new ValidationException('Supply Product ID', ['error' => 'Product ID is required']);
            }

            if (!$product = Product::find($productId)) {
                throw new ValidationException('Unknown Product', ['error' => 'Product not found']);
            }

            if ($product->stock_quantity < $quantity) {
                throw new ValidationException('Low Product Stock', ['error' => 'Insufficient stock available']);
            }

            $this->addItemToCart($productId, $quantity, $variant);

            $_SESSION['success'] = 'Item added to cart successfully';
            return $product->slug;
        } catch (Exception $e) {
            throw new ValidationException("Error Adding to Cart", ['error' => 'an error occured while adding product to cart: ' . $e->getMessage()]);
        }

    }

    public function getCart()
    {
        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        $totalQuantity = 0;
        $totalPrice = 0;

        foreach ($cart as $productId => $item) {
            $product = Product::find($productId);

            if ($product) {
                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'variant' => $item['variant'],
                    'price' => $flashItem->sale_price ?? $product->price

                ];
                $totalQuantity += $item['quantity'];


                # get total price
                $totalPrice += $product->price * $item['quantity'];
            }
        }

        return [
            'items' => $items,
            'total_quantity' => $totalQuantity,
            'total_price' => $totalPrice
        ];
    }

    private function addItemToCart($productId, $quantity, $variant)
    {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
            $_SESSION['cart'][$productId]['variant'] = $variant;
        } else {
            $_SESSION['cart'][$productId]['variant'] = $variant;
            $_SESSION['cart'][$productId]['quantity'] = $quantity;
        }

        return $this->getCart();
    }

    public function updateCart(ServerRequestInterface $request)
    {

        $data = $request->getParsedBody();
        $productId = $data['product_id'] ?? null;
        $quantityChange = $data['quantity_change'] ?? 0;

        if (!$productId) {
            return ['success' => false, 'message' => 'Product ID is required'];
        }

        $cart = $this->updateCartQuantity($productId, $quantityChange);

        return ['success' => true, 'message' => 'Cart updated successfully', 'cart' => $cart];
    }

    private function updateCartQuantity($productId, $quantityChange)
    {
        if (!isset($_SESSION['cart'][$productId])) {
            return $this->getCart();
        }

        $newQuantity = $_SESSION['cart'][$productId]['quantity'] + $quantityChange;

        if ($newQuantity <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId]['quantity'] = $newQuantity;
        }

        return $this->getCart();
    }

    public function noOfCartItems(): array
    {
        $cart = $this->getCart();
        return ['success' => true, 'items' => $cart['total_quantity']];
    }

    public function removeFromCart(ServerRequestInterface $request): array
    {

        $data = $request->getParsedBody();
        $productId = $data['product_id'] ?? null;

        if (!$productId) {
            return ['success' => false, 'message' => 'Product ID is required'];
        }

        $cart = $this->removeItemFromCart($productId);

        return ['success' => true, 'message' => 'Item removed from cart', 'cart' => $cart];
    }

    private function removeItemFromCart($productId)
    {
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }

        return $this->getCart();
    }

    public function applyCoupon(string $code, ?int $userId = null): array
    {
        $cart = $this->getCart();
        $couponService = new CouponService();

        $result = $couponService->validate($code, $cart['total_price'], $userId);

        if (!$result['valid']) {
            return ['success' => false, 'message' => $result['error'], 'discount' => 0];
        }

        $_SESSION['cart_coupon'] = $code;
        $_SESSION['cart_discount'] = $result['discount'];

        return [
            'success' => true,
            'discount' => $result['discount'],
            'total' => $cart['total_price'] - $result['discount']
        ];
    }

}