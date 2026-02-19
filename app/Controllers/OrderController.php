<?php

namespace App\Controllers;

use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\OrderService;
use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

class OrderController extends BaseController
{

    private OrderService $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }


    /**
     * User orders - Show all orders for the logged-in user
     */
    public function userOrders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'account/orders.twig', $this->orderService->userOrders());
    }

    /**
     * Show single order details for user
     */
    public function showOrder(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        return $this->render($request, $response, 'admin/orders/order-details.twig', $this->orderService->showOrder($request, $args['id']));
    }

    /**
     * Admin - Show all orders with filters
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'admin/orders/index.twig', $this->orderService->allOrdersAdmin($request));
    }

    /**
     * Staff - Show orders dispatched by staff after processing
     */
    public function dispatchedOrders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'staffs/orders.twig', $this->orderService->dispatchedOrders($request));
    }

    /**
     * Staff - Process order (update status, add notes, etc.)
     */
    public function processOrder(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        return $this->render($request, $response, 'staffs/process-order.twig', $this->orderService->processOrder($request, $args['id']));
    }

    /**
     * Staff/Dispatch - Mark order as ready for dispatch
     */
    public function markReadyForDispatch(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        return $this->json($response, $this->orderService->markReadyForDispatch($request, $args['id']));
    }

    /**
     * Dispatch - Show orders ready for dispatch
     */
    public function dispatchableOrders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'dispatch/orders.twig', $this->orderService->dispatchableOrders($request));
    }

    /**
     * Pending Orders - Show orders ready for dispatch
     */
    public function pendingOrders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'staff/pending-orders.twig', $this->orderService->pendingOrders($request));
    }

    /**
     * Pending Orders - Show orders ready for dispatch
     */
    public function completedOrders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'staffs/completed-orders.twig', $this->orderService->completedOrders($request));
    }

    /**
     * Pending Orders - Show orders ready for dispatch
     */
    public function ordersInProcessing(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'staffs/orders-in-processing.twig', $this->orderService->ordersInProcessing($request));
    }

    /**
     * Admin - Update order status
     */
    public function updateOrderStatus(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        return $this->json($response, $this->orderService->updateOrderStatus($request, $args['id']));
    }

    /**
     * View order history/timeline
     */
    public function orderHistory(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        return $this->render($request, $response, 'orders/history.twig', $this->orderService->orderHistory($request, $args['id']));
    }

}
