<?php

namespace App\Models;

use App\Services\BannerSliderService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerSlider extends Model
{
    use HasFactory;

    protected $table = 'banner_sliders';

    public const IMAGE_UPLOAD_PATH = 'images/uploads/banner_slider/';
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    protected $fillable = [
        'name',
        'slider',
        'sl',
        'status',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Clear cache when banner slider is created, updated, or deleted
        static::saved(function () {
            app(BannerSliderService::class)->clearCache();
        });
        
        static::deleted(function () {
            app(BannerSliderService::class)->clearCache();
        });
    }

    /**
     * Get all active banner sliders ordered by serial (sl)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveSliders()
    {
        return self::query()
            ->where('status', self::STATUS_ACTIVE)
            ->orderBy('sl', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }
}
