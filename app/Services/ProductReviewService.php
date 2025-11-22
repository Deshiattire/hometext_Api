<?php

namespace App\Services;

use App\Models\ProductReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductReviewService
{
    /**
     * Get all reviews for a product (with pagination)
     *
     * @param int $productId
     * @param bool $approvedOnly
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getReviewsByProduct(int $productId, bool $approvedOnly = true, int $perPage = 20): LengthAwarePaginator
    {
        $query = ProductReview::query()
            ->where('product_id', $productId)
            ->with(['user:id,first_name,last_name', 'product:id,name,slug'])
            ->orderBy('created_at', 'desc');

        if ($approvedOnly) {
            $query->where('is_approved', true);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get all pending reviews (for admin)
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPendingReviews(int $perPage = 20): LengthAwarePaginator
    {
        return ProductReview::query()
            ->where('is_approved', false)
            ->with(['user:id,first_name,last_name', 'product:id,name,slug'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get a single review by ID
     *
     * @param int $id
     * @return ProductReview
     */
    public function getReviewById(int $id): ProductReview
    {
        return ProductReview::query()
            ->with(['user:id,first_name,last_name', 'product:id,name,slug'])
            ->findOrFail($id);
    }

    /**
     * Create a new review
     *
     * @param array $data
     * @return ProductReview
     */
    public function createReview(array $data): ProductReview
    {
        // New reviews are not approved by default
        $data['is_approved'] = false;

        return ProductReview::create($data);
    }

    /**
     * Update a review
     *
     * @param int $id
     * @param array $data
     * @return ProductReview
     */
    public function updateReview(int $id, array $data): ProductReview
    {
        $review = ProductReview::findOrFail($id);
        $review->update($data);

        return $review->fresh(['user:id,first_name,last_name', 'product:id,name,slug']);
    }

    /**
     * Delete a review
     *
     * @param int $id
     * @return bool
     */
    public function deleteReview(int $id): bool
    {
        $review = ProductReview::findOrFail($id);
        return $review->delete();
    }

    /**
     * Approve a review
     *
     * @param int $id
     * @return ProductReview
     */
    public function approveReview(int $id): ProductReview
    {
        $review = ProductReview::findOrFail($id);
        $review->update(['is_approved' => true]);

        return $review->fresh(['user:id,first_name,last_name', 'product:id,name,slug']);
    }

    /**
     * Reject/Disapprove a review
     *
     * @param int $id
     * @return ProductReview
     */
    public function rejectReview(int $id): ProductReview
    {
        $review = ProductReview::findOrFail($id);
        $review->update(['is_approved' => false]);

        return $review->fresh(['user:id,first_name,last_name', 'product:id,name,slug']);
    }

    /**
     * Bulk approve reviews
     *
     * @param array $ids
     * @return int Number of approved reviews
     */
    public function bulkApproveReviews(array $ids): int
    {
        return ProductReview::whereIn('id', $ids)->update(['is_approved' => true]);
    }

    /**
     * Bulk reject reviews
     *
     * @param array $ids
     * @return int Number of rejected reviews
     */
    public function bulkRejectReviews(array $ids): int
    {
        return ProductReview::whereIn('id', $ids)->update(['is_approved' => false]);
    }
}

