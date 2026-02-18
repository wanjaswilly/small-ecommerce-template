<?php
// app/Controllers/CategoryController.php

namespace App\Controllers;

use App\Services\CategoryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CategoryController extends BaseController
{
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->categoryService = new CategoryService();
    }
    // Get all categories
    public function index(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'pages/categories.twig', $this->categoryService->allCategoriesData());
    }

    // Get single category with its products
    public function show(Request $request, Response $response, $id): Response
    {
        return $this->render($request, $response, 'pages/categories.twig', $this->categoryService->singleCategoryData($id));
    }

    // Create new category
    public function store(Request $request, Response $response): Response
    {
        $this->categoryService->storeCategory($request);
        return $this->redirect($response, '/admin/categories');
    }

    // Update category
    public function update(Request $request, Response $response, $args): Response
    {
        $this->categoryService->updateCategory($request, $args['id']);  
        return $this->redirect($response, '/admin/categories');
    }

    // Delete category
    public function destroy(Request $request, Response $response, $args): Response
    {
        $this->categoryService->destroyCategory($args['id']);
        return $this->redirect($response,'/admin/categories');
    }

    // Get category hierarchy (tree structure)
    public function hierarchy(Request $request, Response $response): Response
    {
        return $this->json($response, $this->categoryService->categoryTree());
    }

}
