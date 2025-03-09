<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Category extends Model
{
    use HasFactory;
    public const IMAGE_UPLOAD_PATH = 'images/uploads/category/';
    public const THUMB_IMAGE_UPLOAD_PATH = 'images/uploads/category_thumb/';

    public const STATUS_ACTIVE = 1;

    protected $fillable = [
        'name',
        'image',
        'slug',
        'serial',
        'status',
        'description',
        'photo',
        'user_id'
    ];

    /**
     *@param array $input
     *@return Builder|Model
     */

    final public function storeCategory(array $input): Builder|Model
    {
        return self::query()->create($input);
    }
    /**
     *@param array $input
    * @return LengthAwarePaginator
     */
    final public function getAllCategories(array $input):LengthAwarePaginator
    {
        $per_page = $input['per_page'] ?? 10;
        $query = self::query();
        if (!empty($input['search'])) {
            $query->where('name', 'like', '%' . $input['search'] . '%');
        }
        if (!empty($input['order_by'])) {
            $query->orderBy($input['order_by'], $input['direction'] ?? 'asc');
        }
        return $query->with('user:id,first_name,last_name')->paginate($per_page);
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    final public function getCategoryIdAndName():Collection
    {
        return self::query()->where('status', self::STATUS_ACTIVE)->select('id', 'name')->get();
    }


    /**
     *@return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    final public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subCategories(){
        return $this->hasMany(SubCategory::class);
    }
}
