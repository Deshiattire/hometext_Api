<?php

namespace App\Http\Controllers\web_api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;



class EcomUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['signup', 'UserLogin', 'myorder', 'registration', 'myprofile', 'updateprofile']]);
    }

    // E-com user signup
    public function signup(Request $request)
    {
        $fields['password'] = 'required';
        $fields['username'] = 'required';
        $validator = Validator::make($request->all(), $fields);
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => 'validation_err', 'error' => $validator->errors()], 400);
        }
        // check valid user
        $is_valid = Auth::attempt(['email' => $request->username, 'password' => $request->password]);
        if ($is_valid) {
            return response()->json(['status' => 200, 'message' => 'success', 'token' => $is_valid], 200);
        } else {
            $validator->errors()->add('password', 'Login credential is not valid.');
            return response()->json(['status' => 400, 'message' => 'validation_err', 'error' => $validator->errors()], 400);
        }
    }

    // registration

    public function registration(Request $request)
    {
        $messages = [
            'conf_password.required' => 'The confirm password field is required.',
            'conf_password.same' => 'Password and confirm password are not same.',
        ];

        $fields['password'] = 'required|min:6|max:12';
        $fields['email'] = 'required';
        $fields['conf_password'] = 'required|same:password';
        $fields['first_name'] = 'required';
        // 'email' => 'email|unique:users|max:255',
        $fields['phone'] = 'required|unique:users|numeric|digits:11';
        // $fields['is_subscribe'] = 'required';
        $validator = Validator::make($request->all(), $fields, $messages);
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => 'validation_err', 'error' => $validator->errors()], 400);
        }

        $input = $request->all();
        $input['name'] =  $input['first_name'];
        $input['shop_id'] = 4;
        $input['salt'] = rand(1111, 9999);
        // $input['username'] = $request->mobile_no;
        $input['password'] = Hash::make($input['password']);
        // $input['created_at'] = date('Y-m-d H:i:s');
        if ($user = User::create($input)) {
            $token = Auth::attempt(['phone' => $request->phone, 'password' => $request->password]);
            if ($token) {
                $success['name'] = $user->name;
                $success['statue'] = 200;
                $success['message'] = 'Registration & Authentication successfully done';
                $success['authorisation'] = [
                    'token' => $token,
                    'type' => 'bearer',
                ];
                return response()->json(['success' => $success], 200);
            } else {
                return response()->json(['status' => 500, 'message' => 'auth_err', 'error' => 'Authentication failed'], 500);
            }
        } else {
            return response()->json(['status' => 500, 'message' => 'internal_server_err', 'error' => 'Internal Server Error'], 500);
        }
    }

    // // myprofile
    public function myprofile()
    {
        if (Auth::check()) {
            return response()->json([
                'status' => 'success',
                'user' => Auth::user(),
                'customer_info' =>  Customer::where('user_id', '=', Auth::user()->id)->first(),
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'user' => [],
            ], 200);
        }
    }
    // // myprofile
    public function updateprofile(Request $request)
    {
        if (Auth::check()) {
            $user =  User::where('id', '=', Auth::user()->id)->first();
            return response()->json([
                'status' => 'success',
                'user' =>  $user,
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'user' => [],
            ], 200);
        }
    }

    public function UserLogin(Request $request)
    {
        $validator = Validator::make(
            $request->only('email', 'password', 'user_type'),
            [
                'email' => 'required|max:50',
                'password' => 'required|max:50',
                'user_type' => 'required|numeric'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation_err',
                'error' => $validator->errors()
            ],
            400);
        }

        if($request->input('user_type') == 3){
            $user = (new User())->getUserEmailOrPhone($request->all());
            Log::debug($user);
            $role = $request->input('user_type');
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Please provide valid information'
            ],
            400);
        }

        if($user && Hash::check($request->input('password'), $user->password)){
            $user_data['token'] = $user->createToken($user->email)->plainTextToken;
            $user_data['name'] = $user->name;
            $user_data['phone'] = $user->phone;
            $user_data['photo'] = $user->photo;
            $user_data['email'] = $user->email;
            $user_data['role'] = $role;
            return response()->json([
                'success' => true,
                'message' => 'Successfully Login!',
                'data' => [$user_data],
                'error' => [
                    'code' => 0
                ]
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Login credential is not valid.'
            ],
            400);
        }
    }
}
