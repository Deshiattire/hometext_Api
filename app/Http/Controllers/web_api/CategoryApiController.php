<?php

namespace App\Http\Controllers\web_api;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * CategoryApiController
 * 
 * Public API controller for category endpoints
 * Follows industry best practices: proper validation, error handling, response consistency
 */
class CategoryApiController extends Controller
{
    protected CategoryService $categoryService;

    /**
     * Constructor with dependency injection
     *
     * @param CategoryService $categoryService
     */
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Get Complete Menu Tree
     * GET /api/v1/categories/tree
     * 
     * Returns the complete hierarchical menu structure with all categories, 
     * subcategories, and child categories in a nested format.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function tree(Request $request): JsonResponse
    {
        try {
            $forceRefresh = $request->boolean('refresh', false);
            $tree = $this->categoryService->getTree($forceRefresh);
            
            return $this->success($tree, 'Menu tree retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve menu tree', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->error(
                'Failed to retrieve menu tree',
                config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving the menu tree',
                500
            );
        }
    }

    /**
     * Get Root Categories Only
     * GET /api/v1/categories
     * 
     * Returns only top-level/parent categories without nested children.
     * Useful for initial page load optimization.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate query parameters
            $validator = Validator::make($request->all(), [
                'refresh' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return $this->error(
                    'Validation failed',
                    $validator->errors()->toArray(),
                    422
                );
            }

            $forceRefresh = $request->boolean('refresh', false);
            $categories = $this->categoryService->getRootCategories($forceRefresh);
            
            return $this->success($categories, 'Root categories retrieved successfully');
        } catch (ValidationException $e) {
            return $this->error(
                'Validation failed',
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve root categories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->error(
                'Failed to retrieve root categories',
                config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving categories',
                500
            );
        }
    }

    /**
     * Get Specific Category with Children
     * GET /api/v1/categories/{id}/children
     * 
     * Fetches subcategories and child categories for a specific parent category.
     * Useful for lazy-loading on hover.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function children(Request $request, int $id): JsonResponse
    {
        try {
            // Validate ID
            if ($id <= 0) {
                return $this->error('Invalid category ID', null, 400);
            }

            $data = $this->categoryService->getCategoryChildren($id);
            
            if (!$data) {
                return $this->error('Category not found', null, 404);
            }

            return $this->success($data, 'Category children retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve category children', [
                'category_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->error(
                'Failed to retrieve category children',
                config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving category children',
                500
            );
        }
    }

    /**
     * Get Category Details by Slug
     * GET /api/v1/categories/slug/{slug}
     * 
     * Retrieves category information using URL-friendly slug.
     * Essential for routing and displaying category pages.
     * 
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    public function showBySlug(Request $request, string $slug): JsonResponse
    {
        try {
            // Validate slug
            if (empty($slug) || !preg_match('/^[a-z0-9-]+$/', $slug)) {
                return $this->error('Invalid slug format', null, 400);
            }

            $category = $this->categoryService->getCategoryBySlug($slug);
            
            if (!$category) {
                return $this->error('Category not found', null, 404);
            }

            return $this->success($category, 'Category retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve category by slug', [
                'slug' => $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->error(
                'Failed to retrieve category',
                config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving the category',
                500
            );
        }
    }

    /**
     * Get Breadcrumb Path
     * GET /api/v1/categories/{id}/breadcrumb
     * 
     * Returns the hierarchical path from root to the specified category.
     * Essential for breadcrumb navigation.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function breadcrumb(Request $request, int $id): JsonResponse
    {
        try {
            // Validate ID
            if ($id <= 0) {
                return $this->error('Invalid category ID', null, 400);
            }

            $breadcrumb = $this->categoryService->getBreadcrumb($id);
            
            if ($breadcrumb === null) {
                return $this->error('Category not found', null, 404);
            }

            return $this->success($breadcrumb, 'Breadcrumb retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve breadcrumb', [
                'category_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->error(
                'Failed to retrieve breadcrumb',
                config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving the breadcrumb',
                500
            );
        }
    }
}
