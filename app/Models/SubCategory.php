<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class SubCategory extends Model
{
    use HasFactory;
    public const IMAGE_UPLOAD_PATH = 'images/uploads/sub_category/';
    public const THUMB_IMAGE_UPLOAD_PATH = 'images/uploads/sub_category_thumb/';
    public const STATUS_ACTIVE = 1;

    protected $fillable = ['name','category_id','slug','serial','status','description','photo','user_id' ];


     /**
     *@param array $input
     *@return Builder|Model
     */

     final public function storeSubCategory(array $input): Builder|Model
     {
         return self::query()->create($input);
     }

     /**
     *@param array $input
    * @return LengthAwarePaginator
     */
    final public function getAllSubCategories(array $input):LengthAwarePaginator
    {
        $per_page = $input['per_page'] ?? 10;
        $query = self::query();
        if (!empty($input['search'])) {
            $query->where('name', 'like', '%' . $input['search'] . '%');
        }
        if (!empty($input['order_by'])) {
            $query->orderBy($input['order_by'], $input['direction'] ?? 'asc');
        }
        return $query->with(['user:id,first_name,last_name', 'category:id,name'])->paginate($per_page);
    }
    /**
     *@return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    final public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    /**
     *@return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    final public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

     /**
      * @param int $category_id
     * @return \Illuminate\Support\Collection
     */
    final public function getSubCategoryIdAndName(int $category_id):Collection
    {
        return self::query()->select('id', 'name')->where('category_id', $category_id)->get();
    }
    final public function getSubCategoryIdAndNameForProduct():Collection
    {
        return self::query()->select('id', 'name', 'category_id')->get();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    final public function getSubCategoryIdAndNameFC():Collection
    {
        return self::query()->where('status', self::STATUS_ACTIVE)->select('id', 'name')->get();
    }

    public function childSubCategories(){
        return $this->hasMany(ChildSubCategory::class);
    }
}


