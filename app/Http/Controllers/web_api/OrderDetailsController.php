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
use Illuminate\Support\Facades\Validator;


class OrderDetailsController extends Controller
{

    const REGISTER_USER = 1;
    const GUEST_USER = 2;
    const RETURN_USER = 3;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['checkout']]);
    }


    public function myorder()
    {
        // Check if user is authenticated with Sanctum
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            
            // Try to find customer by user_id
            $customer = Customer::where('user_id', '=', $user->id)->first();
            
            if (!$customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer profile not found for this user',
                    'user' => $user,
                ], 404);
            }
            
            // Get all orders for this customer
            $order = Order::where('customer_id', '=', $customer->id)->get();
            
            return response()->json([
                'status' => 'success',
                'user' => $user,
                'customer' => $customer,
                'order' => $order,
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated',
                'user' => [],
            ], 401);
        }
    }
}
