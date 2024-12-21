<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerSlider extends Model
{
    use HasFactory;
    public const BANNER_UPLOAD_PATH = 'images/uploads/banner/';

    protected $casts = [
        'slider' => 'json',
    ];

    protected $fillable = [
        'name',
        'slider',
        'sl',
        'status'
    ];
}
