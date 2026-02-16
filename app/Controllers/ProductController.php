<?php
# app/Controllers/ProductController.php

namespace App\Controllers;

use App\Services\ProductsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductController extends BaseController
{
    private $productService;

    public function __construct()
    {
        $this->productService = new ProductsService();
    }

    # Display products by category
    public function category(Request $request, Response $response, $args): Response
    {
        return $this->render($request, $response, 'pages/category.twig', $this->productService->categoryData($request));
    }

    # Display all products
    public function allProducts(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'pages/products.twig', $this->productService->allProductsData($request));
    }

    # Index products (admin)
    public function index(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/products.twig', $this->productService->allProductsData($request));
    }

    # Show single product
    public function show(Request $request, Response $response, $args): Response
    {
        return $this->render($request, $response, 'pages/product.twig', $this->productService->singleProductData($args['slug']));
    }

    # Show create product form (admin)
    public function create(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/add-product.twig', $this->productService->activeCategories());
    }

    # Show edit product form (admin)
    public function edit(Request $request, Response $response, $args): Response
    {
        return $this->render($request, $response, 'admin/edit-product.twig', $this->productService->singleProductById($args['id']));
    }

    # Create new product (admin)
    public function store(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/products.twig', $this->productService->saveProduct($request));
    }


    # Update product (admin)
    public function update(Request $request, Response $response, $id): Response
    {
        return $this->render($request, $response, 'admin/products.twig', $this->productService->updateProduct($request, $id));
    }

    # Delete product (admin)
    public function destroy(Request $request, Response $response, $id): Response
    {
        $this->productService->destroyProduct($id);
        $_SESSION['success'] = 'Product deleted successfully';
        return $this->redirect($response, '/admin/products');
    }

    public function productSearch(Request $request, Response $response): Response
    {
        return $this->json($response, $this->productService->productSearch($request), 200);
    }

}
