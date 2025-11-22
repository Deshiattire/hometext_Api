<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductReviewRequest;
use App\Http\Requests\UpdateProductReviewRequest;
use App\Http\Resources\ProductReviewResource;
use App\Services\ProductReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    /**
     * Get reviews by product ID
     *
     * @param int $productId
     * @param Request $request
     * @param ProductReviewService $reviewService
     * @return JsonResponse
     */
    public function getByProduct(int $productId, Request $request, ProductReviewService $reviewService): JsonResponse
    {
        try {
            $approvedOnly = $request->input('approved_only', true);
            $perPage = $request->input('per_page', 20);

            $reviews = $reviewService->getReviewsByProduct($productId, $approvedOnly, $perPage);

            return $this->success([
                'reviews' => ProductReviewResource::collection($reviews),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'has_more' => $reviews->hasMorePages(),
                ]
            ], 'Product reviews retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve product reviews', $e->getMessage(), 500);
        }
    }

    /**
     * Get pending reviews (Admin only)
     *
     * @param Request $request
     * @param ProductReviewService $reviewService
     * @return JsonResponse
     */
    public function getPending(Request $request, ProductReviewService $reviewService): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);
            $reviews = $reviewService->getPendingReviews($perPage);

            return $this->success([
                'reviews' => ProductReviewResource::collection($reviews),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'has_more' => $reviews->hasMorePages(),
                ]
            ], 'Pending reviews retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve pending reviews', $e->getMessage(), 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Not needed - use getByProduct or getPending instead
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductReviewRequest $request
     * @param ProductReviewService $reviewService
     * @return JsonResponse
     */
    public function store(StoreProductReviewRequest $request, ProductReviewService $reviewService): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // If user is authenticated, use their ID
            if (auth()->check()) {
                $data['user_id'] = auth()->id();
            }

            $review = $reviewService->createReview($data);

            return $this->success(
                new ProductReviewResource($review),
                'Review submitted successfully. It will be visible after admin approval.',
                201
            );
        } catch (\Exception $e) {
            return $this->error('Failed to submit review', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @param ProductReviewService $reviewService
     * @return JsonResponse
     */
    public function show(int $id, ProductReviewService $reviewService): JsonResponse
    {
        try {
            $review = $reviewService->getReviewById($id);

            return $this->success(
                new ProductReviewResource($review),
                'Review retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Review not found', null, 404);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve review', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProductReviewRequest $request
     * @param int $id
     * @param ProductReviewService $reviewService
     * @return JsonResponse
     */
    public function update(UpdateProductReviewRequest $request, int $id, ProductReviewService $reviewService): JsonResponse
    {
        try {
            $review = $reviewService->updateReview($id, $request->validated());

            return $this->success(
                new ProductReviewResource($review),
                'Review updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Review not found', null, 404);
        } catch (\Exception $e) {
            return $this->error('Failed to update review', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param ProductReviewService $reviewService
     * @return JsonResponse
     */
    public function destroy(int $id, ProductReviewService $reviewService): JsonResponse
    {
        try {
            $reviewService->deleteReview($id);

            return $this->success(null, 'Review deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Review not found', null, 404);
        } catch (\Exception $e) {
            return $this->error('Failed to delete review', $e->getMessage(), 500);
        }
    }

    /**
     * Approve a review (Admin only)
     *
     * @param int $id
     * @param ProductReviewService $reviewService
     * @return JsonResponse
     */
    public function approve(int $id, ProductReviewService $reviewService): JsonResponse
    {
        try {
            $review = $reviewService->approveReview($id);

            return $this->success(
                new ProductReviewResource($review),
                'Review approved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Review not found', null, 404);
        } catch (\Exception $e) {
            return $this->error('Failed to approve review', $e->getMessage(), 500);
        }
    }

    /**
     * Reject a review (Admin only)
     *
     * @param int $id
     * @param ProductReviewService $reviewService
     * @return JsonResponse
     */
    public function reject(int $id, ProductReviewService $reviewService): JsonResponse
    {
        try {
            $review = $reviewService->rejectReview($id);

            return $this->success(
                new ProductReviewResource($review),
                'Review rejected successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Review not found', null, 404);
        } catch (\Exception $e) {
            return $this->error('Failed to reject review', $e->getMessage(), 500);
        }
    }

    /**
     * Bulk approve reviews (Admin only)
     *
     * @param Request $request
     * @param ProductReviewService $reviewService
     * @return JsonResponse
     */
    public function bulkApprove(Request $request, ProductReviewService $reviewService): JsonResponse
    {
        try {
            $request->validate([
                'review_ids' => 'required|array',
                'review_ids.*' => 'integer|exists:product_reviews,id',
            ]);

            $count = $reviewService->bulkApproveReviews($request->input('review_ids'));

            return $this->success(
                ['approved_count' => $count],
                "Successfully approved {$count} review(s)"
            );
        } catch (\Exception $e) {
            return $this->error('Failed to approve reviews', $e->getMessage(), 500);
        }
    }

    /**
     * Bulk reject reviews (Admin only)
     *
     * @param Request $request
     * @param ProductReviewService $reviewService
     * @return JsonResponse
     */
    public function bulkReject(Request $request, ProductReviewService $reviewService): JsonResponse
    {
        try {
            $request->validate([
                'review_ids' => 'required|array',
                'review_ids.*' => 'integer|exists:product_reviews,id',
            ]);

            $count = $reviewService->bulkRejectReviews($request->input('review_ids'));

            return $this->success(
                ['rejected_count' => $count],
                "Successfully rejected {$count} review(s)"
            );
        } catch (\Exception $e) {
            return $this->error('Failed to reject reviews', $e->getMessage(), 500);
        }
    }
}
