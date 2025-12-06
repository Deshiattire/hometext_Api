<?php

namespace App\Models;

use App\Services\CategoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Category Model
 * 
 * Unified hierarchical category model supporting unlimited nesting levels
 * Follows industry best practices: single responsibility, proper relationships, scopes, caching
 */
class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Image upload paths (legacy support)
     */
    public const IMAGE_UPLOAD_PATH = 'images/uploads/category/';
    public const THUMB_IMAGE_UPLOAD_PATH = 'images/uploads/category_thumb/';

    /**
     * Status constants (legacy support)
     */
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    /**
     * Level constants
     */
    public const LEVEL_CATEGORY = 1;
    public const LEVEL_SUBCATEGORY = 2;
    public const LEVEL_CHILD_CATEGORY = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parent_id',
        'level',
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
        'photo', // Legacy
        'image', // Legacy
        'serial', // Legacy
        'status', // Legacy
        'meta_title',
        'meta_description',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
        'sort_order' => 'integer',
        'parent_id' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function booted(): void
    {
        // Auto-generate slug if not provided
        static::creating(function ($category) {
            if (empty($category->slug) && !empty($category->name)) {
                $category->slug = Str::slug($category->name);
            }
            
            // Set level based on parent
            if ($category->parent_id) {
                $parent = static::find($category->parent_id);
                if ($parent) {
                    $category->level = $parent->level + 1;
                }
            } else {
                $category->level = self::LEVEL_CATEGORY;
            }
        });

        // Clear cache when category is created, updated, or deleted
        static::saved(function () {
            app(CategoryService::class)->clearAllCaches();
        });
        
        static::deleted(function () {
            app(CategoryService::class)->clearAllCaches();
        });
    }

    /**
     * Get the parent category.
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order', 'asc');
    }

    /**
     * Get all images for this category.
     *
     * @return HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany(CategoryImage::class)->ordered();
    }

    /**
     * Get the primary image.
     *
     * @return BelongsTo
     */
    public function primaryImage(): BelongsTo
    {
        return $this->belongsTo(CategoryImage::class, 'id', 'category_id')
            ->where('is_primary', true)
            ->where('image_type', CategoryImage::TYPE_PRIMARY);
    }

    /**
     * Get the user who created this category.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active categories.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include root categories (level 1).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->where('level', self::LEVEL_CATEGORY);
    }

    /**
     * Scope a query to filter by level.
     *
     * @param Builder $query
     * @param int $level
     * @return Builder
     */
    public function scopeByLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', $level);
    }

    /**
     * Scope a query to order by sort order.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Scope a query to include children relationships.
     *
     * @param Builder $query
     * @param int $depth
     * @return Builder
     */
    public function scopeWithChildren(Builder $query, int $depth = 3): Builder
    {
        return $query->with(['children' => function ($q) use ($depth) {
            if ($depth > 1) {
                $q->with(['children' => function ($q2) use ($depth) {
                    if ($depth > 2) {
                        $q2->with('children');
                    }
                }]);
            }
        }]);
    }

    /**
     * Check if category has children.
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->children()->active()->exists();
    }

    /**
     * Get the breadcrumb path from root to this category.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getBreadcrumb(): \Illuminate\Support\Collection
    {
        $breadcrumb = collect([$this]);
        $parent = $this->parent;

        while ($parent) {
            $breadcrumb->prepend($parent);
            $parent = $parent->parent;
        }

        return $breadcrumb;
    }

    /**
     * Get the primary image URL (legacy support).
     *
     * @return string|null
     */
    public function getImageUrlAttribute(): ?string
    {
        // Try to get from category_images table first
        $primaryImage = $this->images()->primary()->first();
        if ($primaryImage) {
            return $primaryImage->url;
        }

        // Fallback to legacy fields
        if ($this->image) {
            return asset($this->image);
        }

        if ($this->photo) {
            return asset(self::IMAGE_UPLOAD_PATH . $this->photo);
        }

        return null;
    }

    /**
     * Legacy method: Get subcategories (for backward compatibility).
     *
     * @return HasMany
     * @deprecated Use children() instead
     */
    public function subCategories(): HasMany
    {
        return $this->children()->byLevel(self::LEVEL_SUBCATEGORY);
    }

    /**
     * Store a new category.
     *
     * @param array $input
     * @return Builder|Model
     */
    final public function storeCategory(array $input): Builder|Model
    {
        return self::query()->create($input);
    }

    /**
     * Get all categories with pagination.
     *
     * @param array $input
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    final public function getAllCategories(array $input): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $per_page = $input['per_page'] ?? 10;
        $query = self::query();
        
        if (!empty($input['search'])) {
            $query->where('name', 'like', '%' . $input['search'] . '%');
        }
        
        if (!empty($input['order_by'])) {
            $query->orderBy($input['order_by'], $input['direction'] ?? 'asc');
        } else {
            $query->ordered();
        }
        
        return $query->with('user:id,name')->paginate($per_page);
    }

    /**
     * Get category ID and name for dropdowns.
     *
     * @return \Illuminate\Support\Collection
     */
    final public function getCategoryIdAndName(): \Illuminate\Support\Collection
    {
        return self::query()
            ->active()
            ->select('id', 'name')
            ->ordered()
            ->get();
    }
}
