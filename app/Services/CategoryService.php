<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * CategoryService
 * 
 * Handles category business logic with proper caching and optimization
 * Follows industry best practices: repository pattern, caching strategy, query optimization
 */
class CategoryService
{
    /**
     * Cache keys
     */
    private const CACHE_PREFIX = 'categories';
    private const TREE_CACHE_KEY = self::CACHE_PREFIX . ':tree';
    private const ROOT_CACHE_KEY = self::CACHE_PREFIX . ':root';
    private const MENU_CACHE_KEY = self::CACHE_PREFIX . ':menu';
    
    /**
     * Cache TTL in seconds (24 hours)
     */
    private const CACHE_TTL = 86400;

    /**
     * Get complete menu tree with all levels
     * Uses recursive CTE for efficient querying
     *
     * @param bool $forceRefresh
     * @return array
     */
    public function getTree(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget(self::TREE_CACHE_KEY);
        }

        return Cache::remember(self::TREE_CACHE_KEY, self::CACHE_TTL, function () {
            return $this->buildTree();
        });
    }

    /**
     * Build complete tree structure using optimized query
     *
     * @return array
     */
    private function buildTree(): array
    {
        // Use eager loading to prevent N+1 queries
        $categories = Category::query()
            ->active()
            ->root()
            ->with([
                'children' => function ($query) {
                    $query->active()
                        ->ordered()
                        ->with([
                            'children' => function ($query) {
                                $query->active()->ordered();
                            }
                        ]);
                },
                'images' => function ($query) {
                    $query->primary()->ordered();
                }
            ])
            ->ordered()
            ->get();

        return $categories->map(function ($category) {
            return $this->formatCategoryForTree($category);
        })->toArray();
    }

    /**
     * Format category for tree structure
     *
     * @param Category $category
     * @return array
     */
    private function formatCategoryForTree(Category $category): array
    {
        $primaryImage = $category->images->first();
        
        $data = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'image' => $primaryImage ? $primaryImage->url : ($category->image_url ?? null),
            'description' => $category->description,
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order,
            'subcategories' => []
        ];

        // Format children (subcategories)
        foreach ($category->children as $child) {
            $childData = [
                'id' => $child->id,
                'name' => $child->name,
                'slug' => $child->slug,
                'parent_id' => $child->parent_id,
                'image' => $child->image_url,
                'is_active' => $child->is_active,
                'sort_order' => $child->sort_order,
                'child_categories' => []
            ];

            // Format grandchildren (child categories)
            foreach ($child->children as $grandchild) {
                $childData['child_categories'][] = [
                    'id' => $grandchild->id,
                    'name' => $grandchild->name,
                    'slug' => $grandchild->slug,
                    'parent_id' => $grandchild->parent_id,
                    'image' => $grandchild->image_url,
                    'is_active' => $grandchild->is_active,
                    'sort_order' => $grandchild->sort_order
                ];
            }

            $data['subcategories'][] = $childData;
        }

        return $data;
    }

    /**
     * Get root categories only
     *
     * @param bool $forceRefresh
     * @return array
     */
    public function getRootCategories(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget(self::ROOT_CACHE_KEY);
        }

        return Cache::remember(self::ROOT_CACHE_KEY, self::CACHE_TTL, function () {
            return Category::query()
                ->active()
                ->root()
                ->with(['images' => function ($query) {
                    $query->primary();
                }])
                ->ordered()
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'image' => $category->image_url,
                        'has_children' => $category->hasChildren(),
                        'is_active' => $category->is_active,
                        'sort_order' => $category->sort_order
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get children of a specific category
     *
     * @param int $id
     * @return array|null
     */
    public function getCategoryChildren(int $id): ?array
    {
        $category = Category::query()
            ->active()
            ->with(['images' => function ($query) {
                $query->primary();
            }])
            ->find($id);

        if (!$category) {
            return null;
        }

        $children = Category::query()
            ->active()
            ->where('parent_id', $id)
            ->with(['images' => function ($query) {
                $query->primary();
            }])
            ->ordered()
            ->get()
            ->map(function ($child) {
                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'parent_id' => $child->parent_id,
                    'has_children' => $child->hasChildren(),
                    'is_active' => $child->is_active,
                    'sort_order' => $child->sort_order
                ];
            })
            ->toArray();

        return [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'level' => $category->level
            ],
            'children' => $children
        ];
    }

    /**
     * Get category by slug
     *
     * @param string $slug
     * @return array|null
     */
    public function getCategoryBySlug(string $slug): ?array
    {
        $category = Category::query()
            ->active()
            ->where('slug', $slug)
            ->with(['images' => function ($query) {
                $query->primary();
            }])
            ->first();

        if (!$category) {
            return null;
        }

        $breadcrumb = $category->getBreadcrumb()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'level' => $item->level
                ];
            })
            ->toArray();

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'parent_id' => $category->parent_id,
            'level' => $category->level,
            'breadcrumb' => $breadcrumb,
            'is_active' => $category->is_active,
            'meta_title' => $category->meta_title,
            'meta_description' => $category->meta_description,
            'image' => $category->image_url
        ];
    }

    /**
     * Get breadcrumb path for a category
     *
     * @param int $id
     * @return array|null
     */
    public function getBreadcrumb(int $id): ?array
    {
        $category = Category::query()
            ->active()
            ->find($id);

        if (!$category) {
            return null;
        }

        return $category->getBreadcrumb()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'level' => $item->level
                ];
            })
            ->toArray();
    }

    /**
     * Clear all category caches
     *
     * @return void
     */
    public function clearAllCaches(): void
    {
        Cache::forget(self::TREE_CACHE_KEY);
        Cache::forget(self::ROOT_CACHE_KEY);
        Cache::forget(self::MENU_CACHE_KEY);
    }

    /**
     * Clear menu cache (legacy method for backward compatibility)
     *
     * @return void
     */
    public function clearMenuCache(): void
    {
        $this->clearAllCaches();
    }

    /**
     * Get optimized category menu (legacy method for backward compatibility)
     *
     * @param bool $forceRefresh
     * @return Collection
     */
    public function getMenu(bool $forceRefresh = false): Collection
    {
        if ($forceRefresh) {
            Cache::forget(self::MENU_CACHE_KEY);
        }

        return Cache::remember(self::MENU_CACHE_KEY, self::CACHE_TTL, function () {
            return Category::query()
                ->active()
                ->root()
                ->with([
                    'children' => function ($query) {
                        $query->active()
                            ->ordered()
                            ->with([
                                'children' => function ($query) {
                                    $query->active()->ordered();
                                }
                            ]);
                    },
                    'images' => function ($query) {
                        $query->primary();
                    }
                ])
                ->ordered()
                ->get();
        });
    }
}
