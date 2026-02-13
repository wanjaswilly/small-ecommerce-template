<?php

use App\Controllers\AuthController;
use App\Controllers\UsersController;
use Slim\App;
use App\Controllers\HomeController;

return function (App $app) {
    # Static core routes
    $app->get('/about', [HomeController::class, 'about'])->setName('about');
    $app->get('/', [HomeController::class, 'home'])->setName('home');
    $app->get('/contact', [HomeController::class, 'contact'])->setName('contact');
    $app->get('/terms', [HomeController::class, 'terms'])->setName('terms');
    $app->get('/privacy', [HomeController::class, 'privacy'])->setName('privacy');
    $app->get('/developer', [HomeController::class, 'developer'])->setName('developer');

    # auth routes
    $app->get('/login', [AuthController::class, 'showLogin'])->setName('login');
    $app->post('/login', [AuthController::class, 'login'])->setName('login');
    $app->get('/register', [AuthController::class, 'showRegister'])->setName('register');
    $app->post('/register', [AuthController::class, 'register'])->setName('register');
    $app->get('/logout', [AuthController::class, 'logout'])->setName('logout');
    $app->post('/logout', [AuthController::class, 'logout'])->setName('logout');

    # user account routes
    $app->group('/user/account', function ($group) {
        $group->get('', [UsersController::class, 'dashboard'])->setName('user');
        $group->get('/dashboard', [UsersController::class, 'dashboard'])->setName('user.dashboard');
        $group->get('/orders', [UsersController::class, 'orders'])->setName('user.orders');
        $group->get('/{id}/details', [UsersController::class, 'orderDetails'])->setName('user.order.detail');
        $group->get('/profile', [UsersController::class, 'profile'])->setName('user.profile');
        $group->post('/profile', [UsersController::class, 'updateProfile'])->setName('user.profile');
        $group->get('/wishlist ', [UsersController::class, 'Wishlist'])->setName('user.add.wishlist');
        $group->get('/wishlist/clear', [UsersController::class, 'clearWishlist'])->setName('account.wishlist');
        $group->post('/wishlist/add/{id}', [UsersController::class, 'AddToWishlist'])->setName('account.wishlist.add');
        $group->post('/wishlist/remove/{id}', [UsersController::class, 'removeFromWishlist'])->setName('account.wishlist.remove');

    });

    // Test 500 error
    $app->get('/test-500', function () {
        throw new \Exception("Intentional test error");
    });
};
