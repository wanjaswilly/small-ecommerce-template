<?php

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ProductController;
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


        $group->post('/profile/update', [UsersController::class, 'updateProfile'])->setName('account.profile.update');

    });

    # Admin routes
    $app->group('/admin', function ($group) {
        $group->get('', [AdminController::class, 'dashboard'])->setName('admin.dashboard');
        $group->get('/dashboard', [AdminController::class, 'dashboard'])->setName('admin.dashboard');

        # Products management
        $group->get('/products', [ProductController::class, 'index'])->setName('admin.products');
        $group->get('/products/create', [ProductController::class, 'create'])->setName('admin.products.add');
        $group->get('/products/{id}/edit', [ProductController::class, 'edit'])->setName('admin.products.edit');
        $group->post('/products', [ProductController::class, 'store'])->setName('admin.products.store');
        $group->post('/products/{id}', [ProductController::class, 'update'])->setName('admin.products.update');
        $group->post('/products/{id}/delete', [ProductController::class, 'destroy'])->setName('admin.products.destroy');

        # Contact Messages Management
        $group->get('/contact-messages', [AdminController::class, 'contactMessages'])->setName('admin.contact.messages');
        $group->get('/contact-message/{id}/view', [AdminController::class, 'viewContactMessage'])->setName('admin.contact.message.view');
        $group->get('/contact-message/{id}/reply', [AdminController::class, 'replyContactMessage'])->setName('admin.contact.message.reply');
        $group->post('/contact-message/{id}/reply', [AdminController::class, 'saveReplyToContactMessage'])->setName('admin.contact.message.reply.save');

        # reset passwords for users & staff
        $group->get('/reset-password', [AdminController::class, 'resetPasswords'])->setName('admin.reset.passwords');
        $group->post('/reset-password', [AdminController::class, 'resetPasswords'])->setName('admin.reset.passwords');

        # inventory section
        $group->get('/inventory', [AdminController::class, 'inventory'])->setName('admin.inventory');

        # customers
        $group->get('/customers', [AdminController::class, 'customers'])->setName('admin.customers');



    });



    # Test 500 error
    $app->get('/test-500', function () {
        throw new \Exception("Intentional test error");
    });
};
