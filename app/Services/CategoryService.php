<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    /**
     * Cache key for menu data
     */
    private const MENU_CACHE_KEY = 'category_menu_data';
    
    /**
     * Cache TTL in seconds (24 hours)
     */
    private const MENU_CACHE_TTL = 86400; // 24 hours

    /**
     * Get optimized category menu with subcategories and child subcategories
     * Uses caching for performance since menus don't change frequently
     *
     * @param bool $forceRefresh Force refresh cache
     * @return Collection
     */
    public function getMenu(bool $forceRefresh = false): Collection
    {
        $cacheKey = self::MENU_CACHE_KEY;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::MENU_CACHE_TTL, function () {
            return $this->fetchMenuData();
        });
    }

    /**
     * Fetch menu data from database with optimized query
     *
     * @return Collection
     */
    private function fetchMenuData(): Collection
    {
        return Category::query()
            ->select('id', 'name', 'image')
            ->where('status', Category::STATUS_ACTIVE)
            ->orderByRaw('COALESCE(serial, 999999) ASC') // Handle null serial values
            ->orderBy('name', 'asc')
            ->with([
                'subCategories' => function ($query) {
                    $query->select('id', 'name', 'category_id')
                        ->where('status', \App\Models\SubCategory::STATUS_ACTIVE)
                        ->orderByRaw('COALESCE(serial, 999999) ASC')
                        ->orderBy('name', 'asc');
                },
                'subCategories.childSubCategories' => function ($query) {
                    $query->select('id', 'name', 'sub_category_id')
                        ->where('status', \App\Models\ChildSubCategory::STATUS_ACTIVE)
                        ->orderByRaw('COALESCE(serial, 999999) ASC')
                        ->orderBy('name', 'asc');
                }
            ])
            ->get();
    }

    /**
     * Clear menu cache (call this when categories are updated)
     *
     * @return void
     */
    public function clearMenuCache(): void
    {
        Cache::forget(self::MENU_CACHE_KEY);
    }
}

