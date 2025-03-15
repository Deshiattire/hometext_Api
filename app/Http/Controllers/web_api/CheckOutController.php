<?php

namespace App\Http\Controllers\web_api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class CheckOutController extends Controller
{

    const REGISTER_USER = 1;
    const GUEST_USER = 2;
    const RETURN_USER = 3;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['checkout', 'myorder']]);
    }


    /**
     * @param int $division_id
     * @return JsonResponse
     */
    final public function checkout(Request $request)
    {
        Log::info($request->all());

        // http://127.0.0.1:8000/api/check-out

        // {
        //     "user_type": "1",
        //     "pd_first_name":"F_Name",
        //     "pd_last_name":"L_Name",
        //     "pd_email":"admin@admin.com",
        //     "pd_phone":"",
        //     "pd_fax":"",
        //     "username":"admin@hometexbd.ltd2",
        //     "password":"123",
        //     "billing_company":"",
        //     "billing_address_1":"",
        //     "billing_address_2":"",
        //     "billing_city":"",
        //     "billing_post_code":"",
        //     "billing_country":"",
        //     "billing_district":"",
        //     "shipping_frist_name":"",
        //     "shipping_last_name":"",
        //     "shipping_company":"",
        //     "shipping_address_1":"",
        //     "shipping_address_2":"",
        //     "shipping_post_code":"",
        //     "shipping_country":"",
        //     "shipping_district":"",
        //     "payment_method":"",
        //     "shipping_method":"",
        //     "coupon_code":"",
        //     "voucher_code":"",
        //     "product_details": ""
        //     "cartData": "[{\"product_id\":1,\"name\":\"Ash Box\",\"price\":4450,\"image\":\"\",\"in_stock\":91,\"supplier_id\":3,\"quantity\":1,\"sku\":\"h457893652\",\"total_price\":4450},{\"product_id\":6,\"name\":\"Trex\",\"price\":2050,\"image\":\"\",\"in_stock\":100,\"supplier_id\":3,\"quantity\":1,\"sku\":\"IKBS 1013\",\"total_price\":2050},{\"product_id\":2,\"name\":\"Shahadath\",\"price\":200,\"image\":\"\",\"in_stock\":0,\"supplier_id\":0,\"quantity\":1,\"sku\":\"aa2223233\",\"total_price\":200}]"
        // }





        $post  = $request->post();
        if ($request->user_type == self::RETURN_USER) {
            $fields = [
                'password' => 'required',
                'username' => 'required',
            ];
        } else {
            $fields = [
                'pd_first_name' => 'required',
                'pd_last_name' => 'required',
                'pd_email' => 'required|email',
                'pd_phone' => 'required|numeric|digits:11',
                'billing_address_1' => 'required',
                'billing_country' => 'required',
                // 'billing_district' => 'required',
            ];
        }
        if ($request->user_type == self::REGISTER_USER) {

            $fields['password'] = 'required';
            $fields['username'] = 'required';
        } else if ($request->user_type == self::GUEST_USER) {
        }


        $validator = Validator::make($request->all(), $fields);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => 'validation_err', 'error' => $validator->errors()], 400);
        }
        // return response()->json(['success' => json_decode($request->cartData)], 200);

        // check user already exist or not
        if ($request->user_type == self::REGISTER_USER) {
            $check_is_user_exist = User::where('email', '=', $request->username)->count();
            if ($check_is_user_exist) {
                $validator->errors()->add('username', 'Username already exist.');
                return response()->json(['status' => 400, 'message' => 'validation_err', 'error' => $validator->errors()], 400);
            }
        }

        try {
            $user = new User();
            $user->password = Hash::make($request->password);
            $user->email =  $request->username;
            $user->first_name =  $request->pd_first_name;
            $user->phone =  $request->pd_phone;
            $user->shop_id = 4;
            $user->salt = rand(1111, 9999);
            $user->save();

            $token = Auth::attempt(['email' => $request->username, 'password' => $request->password]);

            if ($token) {
                $customer = new Customer();
                $customer->email =  $request->username;
                $customer->name =  $request->pd_first_name;
                $customer->phone =  $request->pd_phone;
                $customer->user_id = $user->id;
                $customer->save();

                //customer id
                $customer_id = $customer->id;

                $new_order = new Order();
                $new_order->customer_id = $customer->id;
                $new_order->payment_method_id = $request->payment_method;
                $new_order->shop_id = 4;
                $new_order->sales_manager_id = 2;
                $new_order->order_number = 'HTB' . date('ymdHis') . $user->id;
                $new_order->sub_total = $request->total_payable_amount;
                $new_order->discount = $request->discount;
                $new_order->save();

                $order_data = json_decode($request->cartData, true);

                if ($order_data) {
                    foreach ($order_data as $key => $value) {
                        $oder_details = new OrderDetails();
                        $oder_details->order_id = $new_order->id;
                        $oder_details->category_id = 1;
                        $oder_details->name = $value['name'];
                        $oder_details->sku = $value['sku'];
                        $oder_details->price = $value['price'];
                        $oder_details->quantity = $value['quantity'];
                        $oder_details->save();
                    }
                }

                // transaction
                $transaction = new Transaction();
                $transaction->order_id =  $new_order->id;
                $transaction->customer_id =  $customer->id;
                $transaction->transactionable_type =  'ecommerce';
                $transaction->transactionable_id =  '5';
                $transaction->transaction_type =  '1';
                $transaction->payment_method_id =  '1';
                $transaction->status =  '2';
                $transaction->amount =  $request->total_payable_amount;

                $transaction->save();
            }
            $success['name'] = $user->first_name;

            $success['authorisation'] = [
                'token' => $token,
                'type' => 'bearer',
            ];

            $success['return_payment_page'] = 'yes';
            $success['order_id'] = $new_order->id;
            $success['payment_method'] = $request->payment_method;   // 1 cash on delivary 2=>Online


            return response()->json(['status' => 200, 'message' => 'success', 'success' => $success], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }

        print_r($post);
        echo 'Sanjib';
        //JsonResponse
        // $areas = (new Area())->getAreaByDistrictId($district_id);
        // return response()->json($areas);
    }




    public function checkoutbyloginuser(Request $request)
    {
        if (Auth::check()) {
            $customer =  Customer::where('user_id', '=', Auth::user()->id)->first();

            if (empty($customer)) {
                $customer = new Customer();
                $customer->email =  Auth::user()->email;
                $customer->name =  Auth::user()->name;
                $customer->phone =  Auth::user()->phone;
                $customer->user_id = Auth::user()->id;
                $customer->save();
                //customer id
                $customer_id = $customer->id;
            } else
                $customer_id = $customer->id;


            $new_order = new Order();
            $new_order->customer_id = $customer->id;
            $new_order->payment_method_id = $request->payment_method;
            $new_order->shop_id = 4;
            $new_order->sales_manager_id = 2;
            $new_order->order_number = 'HTB' . date('ymdHis') . Auth::user()->id;
            $new_order->sub_total = $request->total_payable_amount;
            $new_order->discount = $request->discount;
            $new_order->save();

            $order_data = json_decode($request->cartData, true);

            if ($order_data) {
                foreach ($order_data as $key => $value) {
                    $oder_details = new OrderDetails();
                    $oder_details->order_id = $new_order->id;
                    $oder_details->category_id = 1;
                    $oder_details->name = $value['name'];
                    $oder_details->sku = $value['sku'];
                    $oder_details->price = $value['price'];
                    $oder_details->quantity = $value['quantity'];
                    $oder_details->save();
                }
            }
            // transaction
            $transaction = new Transaction();
            $transaction->order_id =  $new_order->id;
            $transaction->customer_id =  $customer->id;
            $transaction->transactionable_type =  'ecommerce';
            $transaction->transactionable_id =  '5';
            $transaction->transaction_type =  '1';
            $transaction->payment_method_id =  '1';
            $transaction->status =  '2';
            $transaction->amount =  $request->total_payable_amount;
            $transaction->save();


            $success['name'] = Auth::user()->name;

            $success['return_payment_page'] = 'yes';
            $success['order_id'] = $new_order->id;
            $success['payment_method'] = $request->payment_method;   // 1 cash on delivary 2=>Online

            return response()->json(['status' => 200, 'message' => 'success', 'success' => $success], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'user' => [],
            ], 200);
        }
    }

    public function myorder()
    {
        if (Auth::check()) {
            return response()->json([
                'status' => 'success',
                'user' => Auth::user(),
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'user' => [],
            ], 200);
        }
    }

    // protected function createNewToken($token){
    //     return response()->json([
    //         'access_token' => $token,
    //         'token_type' => 'bearer',
    //         'expires_in' => auth()->factory()->getTTL() * 60,
    //         'user' => auth()->user()
    //     ]);
    // }
}
