<?php

use App\Controllers\AuthController;
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

    // Test 500 error
    $app->get('/test-500', function () {
        throw new \Exception("Intentional test error");
    });
};
