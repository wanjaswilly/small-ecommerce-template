<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Product;
use App\Models\User;

class ReviewService
{
    public function createReview(int $productId, int $userId, array $data): Review
    {
        return Review::create([
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'content' => $data['content'],
            'is_approved' => false,
        ]);
    }

    public function approveReview(int $id): bool
    {
        $review = Review::findOrFail($id);
        return $review->update(['is_approved' => true]);
    }

    public function getApprovedReviews(int $productId): array
    {
        return Review::where('product_id', $productId)
            ->where('is_approved', true)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function deleteReview(int $id): bool
    {
        $review = Review::findOrFail($id);
        return $review->delete();
    }

    public function getAllForAdmin(): array
    {
        return Review::with(['product', 'user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}