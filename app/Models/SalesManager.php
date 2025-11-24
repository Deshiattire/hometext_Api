<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class SalesManager extends Model
{
    use HasFactory, HasApiTokens, Notifiable, HasRoles;
    protected  $hidden = [
        'password',
    ];

    protected $fillable = ['bio',
        'email',
        'nid_photo',
        'nid',
        'password',
        'photo',
        'name',
        'phone',
        'status',
        'user_id',
        'shop_id',
        'employee_type'];


    public const STATUS_ACTIVE = 1;
    public const STATUS_ACTIVE_TEXT = 'Active';

    public const STATUS_INACTIVE = 0;
    public const STATUS_INACTIVE_TEXT = 'Inactive';

    public const PHOTO_WIDTH = 800;
    public const PHOTO_HEIGHT = 800;
    public const PHOTO_THUMB_WIDTH = 200;
    public const PHOTO_THUMB_HEIGHT = 200;
    public const PHOTO_UPLOAD_PATH = 'images/uploads/sales_manager/';
    public const THUMB_PHOTO_UPLOAD_PATH = 'images/uploads/sales_manager_thumb/';

     /**
     * @param array $input
     * @param $auth
     * @return array
     */
    public function prepareData(array $input):array
    {
        $sales_manager['bio'] = $input['bio'] ?? null;
        $sales_manager['email'] = $input['email'] ?? null;
        $sales_manager['name'] = $input['name'] ?? null;
        $sales_manager['phone'] = $input['phone'] ?? null;
        $sales_manager['status'] = $input['status'] ?? 0;
//        $sales_manager['user_id'] = $auth->id();
        $sales_manager['shop_id'] = $input['shop_id'] ?? null;
        $sales_manager['employee_type'] = $input['employee_type'] ?? null;
        $sales_manager['nid'] = $input['nid'];
        $sales_manager['password'] =Hash::make($input['password']) ;
        return $sales_manager;
    }
    /**
     * @return MorphOne
     */
    final public function address():MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    final public function getSalesManagerList($input)
    {
        $per_page = $input['per_page'] ??10;

        $query =self::query()->with(
            'address',
            'address.division:id,name',
            'address.district:id,name',
            'address.area:id,name',
            'user:id,name',
            'shop:id,name',
        );
        if(!empty($input['search'])){
            $query -> where('name', 'like', '%'.$input['search'].'%')
            ->orWhere('phone', 'like', '%'.$input['search'].'%')
            ->orWhere('email', 'like', '%'.$input['search'].'%');
        }
        if (!empty($input['order_by'])) {
            $query->orderBy($input['order_by'], $input['direction'] ?? 'asc');
        }
        return $query->paginate($per_page);
    }
    /**
     * @return BelongsTo
     */
    final public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    /**
     * @return BelongsTo
     */
    final public function shop():BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
    /**
     * @param array $input
     * @return Builder|Model|object|null
    */

    final public function getUserEmailOrPhone(array $input):Builder|Model|null
    {
        return self::query()->where('email', $input['email'])->orWhere('phone', $input['email'])->first();
    }


    final public function transactionable():MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }
}
