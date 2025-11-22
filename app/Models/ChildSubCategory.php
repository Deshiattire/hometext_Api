<?php

namespace App\Models;

use App\Services\CategoryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ChildSubCategory extends Model
{
    use HasFactory;
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Clear menu cache when child subcategory is created, updated, or deleted
        static::saved(function () {
            app(CategoryService::class)->clearMenuCache();
        });
        
        static::deleted(function () {
            app(CategoryService::class)->clearMenuCache();
        });
    }

    public const IMAGE_UPLOAD_PATH = 'images/uploads/child_sub_category/';
    public const THUMB_IMAGE_UPLOAD_PATH = 'images/uploads/child_sub_category_thumb/';
    public const STATUS_ACTIVE = 1;

    protected $fillable = ['name', 'sub_category_id', 'slug', 'serial', 'status', 'description', 'photo', 'user_id'];

    /**
     * @param array $input
     * @return Builder|Model
     */
    final public function storeChildSubCategory(array $input): Builder|Model
    {
        return self::query()->create($input);
    }

    final public function getAllChildSubCategories(array $input): LengthAwarePaginator
    {
        $per_page = $input['per_page'] ?? 10;
        $query = self::query();

        if (!empty($input['search'])) {
            $query->where('name', 'like', '%' . $input['search'] . '%');
        }

        if (!empty($input['order_by'])) {
            $query->orderBy($input['order_by'], $input['direction'] ?? 'asc');
        }

        return $query->with(['user:id,name', 'sub_category:id,name'])->paginate($per_page);
    }

    /**
     * @return BelongsTo
     */
    final public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    final public function sub_category(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    /**
     * @param int $sub_category_id
     * @return Collection
     */
    final public function getChildSubCategoryIdAndName(int $sub_category_id): Collection
    {
        return self::query()->select('id', 'name')->where('sub_category_id', $sub_category_id)->get();
    }
    final public function getChildSubCategoryIdAndNameForProduct(): Collection
    {
        return self::query()->select('id', 'name', 'sub_category_id')->get();
    }
}
