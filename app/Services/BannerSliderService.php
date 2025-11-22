<?php

namespace App\Services;

use App\Models\BannerSlider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BannerSliderService
{
    /**
     * Cache key for banner sliders
     */
    private const CACHE_KEY = 'banner_sliders_data';
    
    /**
     * Cache TTL in seconds (24 hours)
     */
    private const CACHE_TTL = 86400;

    /**
     * Get all active banner sliders
     * Uses caching for performance
     *
     * @param bool $forceRefresh Force refresh cache
     * @return Collection
     */
    public function getActiveSliders(bool $forceRefresh = false): Collection
    {
        $cacheKey = self::CACHE_KEY;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return BannerSlider::query()
                ->select('id', 'name', 'slider', 'sl', 'status')
                ->where('status', BannerSlider::STATUS_ACTIVE)
                ->orderByRaw('COALESCE(sl, 999999) ASC')
                ->orderBy('id', 'asc')
                ->get();
        });
    }

    /**
     * Clear banner slider cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

