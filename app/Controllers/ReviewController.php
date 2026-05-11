<?php

namespace App\Controllers;

use App\Services\ReviewService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReviewController extends BaseController
{
    private ReviewService $reviewService;

    public function __construct()
    {
        $this->reviewService = new ReviewService();
    }

    public function index(Request $request, Response $response): Response
    {
        $reviews = $this->reviewService->getAllForAdmin();
        return $this->render($request, $response, 'admin/reviews/index.twig', ['reviews' => $reviews]);
    }

    public function store(Request $request, Response $response, $args): Response
    {
        $data = $request->getParsedBody();
        $productId = (int) $args['id'];
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $_SESSION['error'] = 'You must be logged in to leave a review';
            return $this->redirect($response, '/login');
        }

        $this->reviewService->createReview($productId, $userId, [
            'rating' => (int) $data['rating'],
            'title' => $data['title'] ?? null,
            'content' => $data['content']
        ]);

        $_SESSION['success'] = 'Review submitted and awaiting approval';
        return $this->redirect($response, '/products/' . $args['slug'] ?? $args['id']);
    }

    public function approve(Request $request, Response $response, $args): Response
    {
        $this->reviewService->approveReview($args['id']);
        $_SESSION['success'] = 'Review approved';
        return $this->redirect($response, '/admin/reviews');
    }

    public function destroy(Request $request, Response $response, $args): Response
    {
        $this->reviewService->deleteReview($args['id']);
        $_SESSION['success'] = 'Review deleted';
        return $this->redirect($response, '/admin/reviews');
    }
}