<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * CategoryImage Model
 * 
 * Handles multiple images per category with support for different image types
 * Follows industry best practices: single responsibility, proper relationships, scopes
 */
class CategoryImage extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Image types constants
     */
    public const TYPE_PRIMARY = 'primary';
    public const TYPE_THUMBNAIL = 'thumbnail';
    public const TYPE_BANNER = 'banner';
    public const TYPE_GALLERY = 'gallery';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'image_path',
        'image_type',
        'alt_text',
        'width',
        'height',
        'file_size',
        'position',
        'is_primary',
        'storage_disk',
        'mime_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the category that owns the image.
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope a query to only include primary images.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope a query to filter by image type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('image_type', $type);
    }

    /**
     * Scope a query to order by position.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc')->orderBy('created_at', 'asc');
    }

    /**
     * Get the full URL for the image.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        if ($this->storage_disk === 'public' || $this->storage_disk === 'local') {
            return asset('storage/' . $this->image_path);
        }
        
        // For S3 or other cloud storage
        return Storage::disk($this->storage_disk)->url($this->image_path);
    }

    /**
     * Get image dimensions as a string.
     *
     * @return string|null
     */
    public function getDimensionsAttribute(): ?string
    {
        if ($this->width && $this->height) {
            return "{$this->width}x{$this->height}";
        }
        
        return null;
    }

    /**
     * Get formatted file size.
     *
     * @return string|null
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }
}
