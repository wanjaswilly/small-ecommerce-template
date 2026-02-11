<?php

namespace App\Controllers;

use App\Models\Address;
use App\Models\Favourite;
use App\Services\UsersService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;

class UsersController extends BaseController
{
    private User $user;
    private UsersService $usersService;

    public function __construct()
    {
        $this->user = User::find($_SESSION['user_id']);
        $this->usersService = new UsersService();
    }

    public function dashboard(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {

        return $this->render($request, $response, 'users/dashboard.twig', $this->usersService->dashboardData());
    }

    public function orders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'users/orders.twig', $this->usersService->userOrdersData());
    }

    public function orderDetails(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->render($request, $response, 'users/order-details.twig', $this->usersService->singleOrderData($args['id']));
    }

    public function profile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {

        return $this->render($request, $response, 'users/profile.twig', $this->usersService->user());
    }
    

    public function updateProfile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->usersService->updateProfile($request);
        return $this->redirect($response, '/user/account/profile', 302);
    }
}
