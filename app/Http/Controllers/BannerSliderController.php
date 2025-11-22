<?php

namespace App\Http\Controllers;

use App\Http\Resources\BannerSliderResource;
use App\Services\BannerSliderService;
use Illuminate\Http\JsonResponse;

class BannerSliderController extends Controller
{
    /**
     * Get all active banner sliders
     * Optimized with caching for performance
     *
     * @param BannerSliderService $bannerSliderService
     * @return JsonResponse
     */
    public function index(BannerSliderService $bannerSliderService): JsonResponse
    {
        try {
            $sliders = $bannerSliderService->getActiveSliders();
            
            return $this->success(
                BannerSliderResource::collection($sliders),
                'Banner sliders retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve banner sliders', $e->getMessage(), 500);
        }
    }
}
