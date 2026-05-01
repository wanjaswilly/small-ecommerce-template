<?php

use Slim\App;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\HomeController;
use App\Controllers\AdminController;
use App\Controllers\OrderController;
use App\Controllers\UsersController;
use App\Controllers\PaymentController;
use App\Controllers\ProductController;
use App\Controllers\CategoryController;
use App\Controllers\CheckoutController;
use App\Controllers\SettingsController;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

return function (App $app) {
    # Static core routes
    $app->get('/', [HomeController::class, 'index'])->setName('home');
    $app->get('/about', [HomeController::class, 'about'])->setName('about');
    $app->get('/contact', [HomeController::class, 'contact'])->setName('contact');
    $app->post('/contact', [HomeController::class, 'saveContact'])->setName('contact.save');
    $app->get('/help', [HomeController::class, 'help'])->setName('help');
    $app->get('/support', [HomeController::class, 'help'])->setName('help.support');

    // Legal pages
    $app->get('/terms', [HomeController::class, 'terms'])->setName('terms');
    $app->get('/privacy', [HomeController::class, 'privacy'])->setName('privacy');
    $app->get('/compliance', [HomeController::class, 'compliance'])->setName('compliance');

    # auth routes
    $app->get('/login', [AuthController::class, 'showLogin'])->setName('login');
    $app->post('/login', [AuthController::class, 'login'])->setName('login');
    $app->get('/register', [AuthController::class, 'showRegister'])->setName('register');
    $app->post('/register', [AuthController::class, 'register'])->setName('register');
    $app->get('/logout', [AuthController::class, 'logout'])->setName('logout');
    $app->post('/logout', [AuthController::class, 'logout'])->setName('logout');

    
    # product categories
    $app->get('/categories', [CategoryController::class, 'index']);
    $app->get('/categories/hierarchy/tree', [CategoryController::class, 'hierarchy']);
    $app->get('/categories/with-counts', [CategoryController::class, 'withCounts']);
    $app->get('/categories/{id}', [CategoryController::class, 'show']);

    # unified products
    $app->get('/products', [ProductController::class, 'all']);
    $app->get('/products/{slug}', [ProductController::class, 'show']);
    $app->get('/search', [HomeController::class, 'search'])->setName('search');
    $app->get('/category/{categorySlug}', [ProductController::class, 'category']);

    # products search JSON
    $app->get('/products/search/json', [ProductController::class, 'productSearch']);

    // Checkout routes
    $app->get('/checkout', [CheckoutController::class, 'showCheckout'])->setName('checkout.show')->add(new AuthMiddleware());
    $app->post('/checkout/process', [CheckoutController::class, 'processCheckout'])->setName('checkout.process')->add(new AuthMiddleware());
    $app->get('/checkout/success', [CheckoutController::class, 'checkoutSuccess'])->setName('checkout.success');

    # Cart Routes
    $app->get('/cart', [CartController::class, 'showCart'])->setName('cart.show');
    $app->get('/cartitems', [CartController::class, 'getNumberOfCartItems'])->setName('cart.items');
    $app->get('/cart/data', [CartController::class, 'getCartData'])->setName('cart.data');
    $app->post('/cart/add', [CartController::class, 'addToCart'])->setName('cart.add');
    $app->post('/cart/add/product', [CartController::class, 'addProductToCart'])->setName('cart.add');
    $app->post('/cart/update', [CartController::class, 'updateCart']);
    $app->post('/cart/remove', [CartController::class, 'removeFromCart']);

    // Payment routes
    $app->post('/payment/process', [PaymentController::class, 'processPayment'])->setName('payment.process');
    $app->get('/payment/{method}/callback', [PaymentController::class, 'paymentCallback'])->setName('payment.callback');
    $app->get('/payment/methods', [PaymentController::class, 'getPaymentMethods'])->setName('payment.methods');

    # user account routes
    $app->group('/user/account', function ($group) {
        # dashboard
        $group->get('', [UsersController::class, 'dashboard'])->setName('user');
        $group->get('/dashboard', [UsersController::class, 'dashboard'])->setName('user.dashboard');

        # orders
        $group->get('/orders', [UsersController::class, 'orders'])->setName('user.orders');
        $group->get('/order/{id}/details', [UsersController::class, 'orderDetails'])->setName('user.order.detail');
        $group->post('/orders/{id}/cancel', [UsersController::class, 'cancelOrder'])->setName('account.order.cancel');

        # profile
        $group->get('/profile', [UsersController::class, 'profile'])->setName('user.profile');
        $group->post('/profile', [UsersController::class, 'updateProfile'])->setName('user.profile');
        $group->post('/profile/update', [UsersController::class, 'updateProfile'])->setName('account.profile.update');

        # wishlist
        $group->get('/wishlist ', [UsersController::class, 'Wishlist'])->setName('user.add.wishlist');
        $group->get('/wishlist/clear', [UsersController::class, 'clearWishlist'])->setName('account.wishlist');
        $group->post('/wishlist/add/{id}', [UsersController::class, 'AddToWishlist'])->setName('account.wishlist.add');
        $group->post('/wishlist/remove/{id}', [UsersController::class, 'removeFromWishlist'])->setName('account.wishlist.remove');

        # address
        $group->get('/addresses', [UsersController::class, 'addresses'])->setName('account.addresses');
        $group->post('/addresses', [UsersController::class, 'addAddress'])->setName('account.addresses.add');

    })->add(new AuthMiddleware());

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

        # settings section
        $group->get('/settings', [SettingsController::class, 'index'])->setName('admin.settings');
        $group->post('/settings', [SettingsController::class, 'update'])->setName('admin.settings.update');
        $group->post('/settings/reset', [SettingsController::class, 'reset'])->setName('admin.settings.reset');

        # Category management
        $group->get('/categories', [AdminController::class, 'categories'])->setName('admin.categories');
        $group->get('/categories/create', [AdminController::class, 'addCategory'])->setName('admin.categories.add');
        $group->get('/categories/{id}/edit', [AdminController::class, 'editCategory'])->setName('admin.categories.edit');
        $group->post('/categories/store', [CategoryController::class, 'store'])->setName('admin.categories.store');
        $group->post('/categories/{id}/update', [CategoryController::class, 'update'])->setName('admin.categories.update');
        $group->post('/categories/{id}/delete', [CategoryController::class, 'destroy'])->setName('admin.categories.destroy');

        # orders
        $group->get('/orders', [OrderController::class, 'index'])->setName('admin.orders');
        $group->get('/orders/{id}/view', [OrderController::class, 'showOrder'])->setName('admin.orders.view');
        $group->get('/orders/{id}/next-step', [OrderController::class, 'nextStep'])->setName('admin.orders.next-step');
        $group->get('/orders/process', [OrderController::class, 'ordersInProcessing'])->setName('staff.orders.processing');
        $group->get('/orders/dispatched', [OrderController::class, 'dispatchedOrder'])->setName('staff.order.dispatched');
        $group->get('/orders/completed', [OrderController::class, 'completedOrders'])->setName('staff.orders.completed');
        $group->get('/orders/{id}/process', [OrderController::class, 'processOrder'])->setName('staff.order.process');
        $group->post('/orders/{id}/process', [OrderController::class, 'processOrder'])->setName('staff.order.process');
        $group->post('/orders/{id}/ready', [OrderController::class, 'markReadyForDispatch'])->setName('staff.order.ready');
        $group->get('/orders/dispatchable', [OrderController::class, 'dispatchableOrders'])->setName('staff.order.dispatchable');
        $group->get('/orders/dispatch/{id}', [OrderController::class, 'dispatchOrder'])->setName('staff.order.dispatch');

    })->add(new AdminMiddleware());


    # Test 500 error
    $app->get('/test-500', function () {
        throw new \Exception("Intentional test error");
    });
};
