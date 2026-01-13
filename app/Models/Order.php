<?php

namespace App\Models;

use App\Manager\OrderManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_guest_order' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays/JSON.
     */
    protected $hidden = [
        'guest_token', // Hide token in responses for security
        'ip_address',
        'user_agent',
    ];

    public const STATUS_PENDING = 1;
    public const STATUS_PROCESSED = 2;
    public const STATUS_COMPLETED = 3;
    public const SHIPMENT_STATUS_COMPLETED = 1;
    public const PAID = 1;
    public const PARTIAL_PAID = 2;
    public const UNPAID = 3;


    public function getAllOrders(array $input, $auth)
    {
        $is_admin = $auth->guard('admin')->check();
        $query = self::query();
        $query->with(
            [
                'customer:id,name,phone',
                'payment_method:id,name',
                'sales_manager:id,name',
                'shop:id,name',
                ]
        );
        if(!$is_admin){
            $user = $auth->user();
            // Get shop_id based on user type
            if ($user instanceof \App\Models\SalesManager) {
                $shopId = $user->shop_id;
            } elseif ($user instanceof \App\Models\User) {
                // For User model, get primary shop
                $primaryShop = $user->primaryShop();
                $shopId = $primaryShop ? $primaryShop->id : null;
            } else {
                $shopId = null;
            }
            
            // Filter by shop if shop_id is available
            if($shopId) {
                $query->where('shop_id', $shopId);
            }
        }
        return $query->paginate(10);
    }

    /**
     * @param array $input
     * @param $auth
     * @return array|string[]|null
     */
    public function placeOrder(array $input, $auth)
    {
      $order_data = $this->prepareData($input, $auth);
      if(isset($order_data['error_description'])){
        return $order_data;
      }
      $order = self::query()->create($order_data['order_data']);
      (new OrderDetails())->storeOrderDetails($order_data['order_details'],$order);
        (new Transaction())->storeTransaction($input, $order, $auth);
        return $order;
        foreach ($price['order_details'] as $product) {
            self::reduceProductStock($product, $product->quantity, $order_data['shop_id']);
        }
    }

    /**
     * @param array $input
     * @param $auth
     * @return array|string[]
     */
    private function prepareData(array $input, $auth)
    {
       $price = OrderManager::handle_order_data($input);
       if(isset($price['error_description'])){
        return $price;
       }else{

           // Get shop_id based on user type
           if ($auth instanceof \App\Models\SalesManager) {
               $shopId = $auth->shop_id;
           } elseif ($auth instanceof \App\Models\User) {
               $primaryShop = $auth->primaryShop();
               $shopId = $primaryShop ? $primaryShop->id : null;
           } else {
               $shopId = null;
           }
           
           $order_data = [
               'customer_id' =>$input['orderSummary']['customer_id'],
               'sales_manager_id'=> $auth->id,
               'shop_id' => $shopId,
               'sub_total' => $price['sub_total'],
               'discount' => $price['discount'],
               'total' => $price['total'],
               'quantity' => $price['quantity'],
               'paid_amount' =>$input['orderSummary']['paid_amount'],
               'due_amount' =>$input['orderSummary']['due_amount'],
               'order_status' => self::STATUS_COMPLETED,
               'order_number' => OrderManager::generateOrderNumber($shopId),
               'payment_method_id'=> $input['orderSummary']['payment_method_id'],
               'payment_status'=> OrderManager::decidePaymentStatus($price['total'], $input['orderSummary']['paid_amount']),
               'shipment_status' => self::SHIPMENT_STATUS_COMPLETED,
       ];
       return ['order_data'=>$order_data, 'order_details'=>$price['order_details']];
       }
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
    public function sales_manager()
    {
        return $this->belongsTo(SalesManager::class);
    }
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
    public function order_details()
    {
        return $this->hasMany(OrderDetails::class);
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    public function getAllOrdersForReport(bool $is_admin,int $sales_admin_id, array $columns = ['*'])
    {
        $query = DB::table('orders')->select($columns);
        if(!$is_admin){
            !$query->where('sales_manager_id', $sales_admin_id);
        }
        $orders = $query->get();
        return collect($orders);
    }

}
