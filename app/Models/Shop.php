<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Shop extends Model
{
    use HasFactory;

    // Define the fillable attributes
    protected $fillable = ['details', 'email', 'logo', 'name', 'phone', 'status', 'user_id', 'slug'];

    // Define constants for shop status
    public const STATUS_ACTIVE = 1;
    public const STATUS_ACTIVE_TEXT = 'Active';
    public const STATUS_INACTIVE = 0;
    public const STATUS_INACTIVE_TEXT = 'Inactive';

    // Define constants for logo dimensions and paths
    public const LOGO_WIDTH = 800;
    public const LOGO_HEIGHT = 800;
    public const LOGO_THUMB_WIDTH = 200;
    public const LOGO_THUMB_HEIGHT = 200;
    public const IMAGE_UPLOAD_PATH = 'images/uploads/shop/';
    public const THUMB_IMAGE_UPLOAD_PATH = 'images/uploads/shop_thumb/';

    // Add a method to prepare data
    public function prepareData(array $input, $auth): array
    {
        $shop['details'] = $input['details'] ?? '';
        $shop['email'] = $input['email'] ?? '';
        $shop['name'] = $input['name'] ?? '';
        $shop['phone'] = $input['phone'] ?? '';
        $shop['status'] = $input['status'] ?? '';
        $shop['user_id'] = $auth->id();
        return $shop;
    }

    // Define the address relationship
    final public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    // Add a method to get a list of shops with optional search and pagination
    final public function getShopList($input)
    {
        $per_page = $input['per_page'] ?? 10;

        $query = self::query()->with(
            'address',
            'address.division:id,name',
            'address.district:id,name',
            'address.area:id,name',
            'user:id,name',
        );

        if (!empty($input['search'])) {
            $query->where('name', 'like', '%' . $input['search'] . '%')
                ->orWhere('phone', 'like', '%' . $input['search'] . '%')
                ->orWhere('email', 'like', '%' . $input['search'] . '%');
        }

        if (!empty($input['order_by'])) {
            $query->orderBy($input['order_by'], $input['direction'] ?? 'asc');
        }

        return $query->paginate($per_page);
    }

    // Define the user relationship
    final public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Add a method to delete addresses by shop id
    final public function deleteAddressByShopId(Shop $shop): int
    {
        return $shop->address()->delete();
    }

    // Add a method to get a list of shop IDs and names
    public function getShopListIdName()
    {
        return self::query()->select(['id', 'name'])->where('status', 1)->get();
    }

    // Add a method to get shop details by ID with related address information
    public function getShopDetailsById($id)
    {
        return self::query()->with('address',
            'address.division:id,name',
            'address.district:id,name',
            'address.area:id,name'
        )->findOrFail($id);
    }

    // Add a method to get a list of shop IDs and names
    public function getShopIdAndName()
    {
        return self::query()->select('id as value', 'name as label')->get();
    }

    // Define the products relationship to associate shops with products
    public function products()
    {
        return $this->belongsToMany(Product::class, 'shop_product')
            ->withPivot('quantity')
            ->using(ShopProduct::class);
    }

    // Define the users relationship through user_shop_access
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_shop_access', 'shop_id', 'user_id')
            ->wherePivotNull('revoked_at')
            ->withPivot('role', 'is_primary', 'granted_at', 'revoked_at')
            ->withTimestamps();
    }
}
