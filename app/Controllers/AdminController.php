<?php
# app/Controllers/AdminViewController.php

namespace App\Controllers;

use App\Services\AdminService;
use App\Services\ContactMessageService;
use App\Services\ProductsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController extends BaseController
{
    private AdminService $adminService;
    private ProductsService $productsService;
    private ContactMessageService $contactMessageService;

    public function __construct()
    {

    }
    # Dashboard
    public function dashboard(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/dashboard.twig', $this->adminService->dashboardData());
    }

    # Categories List
    public function categories(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/categories.twig', $this->productsService->categoryData($request));
    }

    # Add Category Form
    public function addCategory(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/add-category.twig', $this->productsService->parentCategories());
    }

    # Products List
    public function products(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/products.twig', $this->adminService->productsList());
    }

    # Add Product Form
    public function addProduct(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/add-product.twig', $this->productsService->categoryData($request));
    }

    # Orders List
    public function orders(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/orders.twig', $this->adminService->allOrders());
    }

    # Customers List
    public function customers(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/customers.twig',$this->adminService->allCustomers() );
    }

    # Inventory Management
    public function inventory(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/inventory.twig', $this->adminService->inventoryData($request));
    }

    # Analytics
    public function analytics(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/analytics.twig', $this->adminService->analyticsData());
    }

    # Settings
    public function settings(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/settings.twig', $this->adminService->settingsData());
    }

    # Edit Product Form
    public function editProduct(Request $request, Response $response, $id): Response
    {
        return $this->render($request, $response, 'admin/edit-product.twig', $this->productsService->singleProductById($id));
    }

    public function editCategory(Request $request, Response $response, $id): Response
    {
        return $this->render($request, $response, 'admin/edit-category.twig', $this->adminService->editCategoryData($id) );
    }


    public function searchProducts(Request $request, Response $response): Response
    {
        return $this->json($response, $this->productsService->productSearch($request));
    }


    public function resetPasswords(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/reset-password.twig');
    }

    public function contactMessages(Request $request, Response $response):Response
    {
        return $this->render($request, $response, 'admin/contact-messages.twig', $this->contactMessageService->allMessages());
    }

    public function viewContactMessage(Request $request, Response $response, $args):Response
    {
        return $this->render($request, $response, 'admin/contact-message.twig', $this->contactMessageService->singleMessageData($args['id']));
    }

    public function saveReplyToContactMessage(Request $request, Response $response, $args):Response
    {
                return $this->render($request, $response, 'admin/contact-message.twig', $this->contactMessageService->saveReply($request, $args['id']));
    }
}
