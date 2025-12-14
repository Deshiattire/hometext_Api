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
        // Only protect profile routes and logout, registration and login are public
        $this->middleware('auth:sanctum', ['only' => ['myprofile', 'updateprofile', 'logout']]);
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
        $user = User::where('email', $request->username)->orWhere('phone', $request->username)->first();
        if ($user && Hash::check($request->password, $user->password)) {
            // Check if account is locked
            if($user->isLocked()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Your account has been temporarily locked. Please try again later.'
                ], 400);
            }

            // Check if account is active
            if(!$user->isActive()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Your account is inactive. Please contact support.'
                ], 400);
            }

            $user->recordLogin();
            $token = $user->createToken('auth-token')->plainTextToken;
            return response()->json(['status' => 200, 'message' => 'success', 'token' => $token, 'id' => $user->id], 200);
        } else {
            if($user) {
                $user->recordFailedLogin();
            }
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
        $fields['email'] = 'required|email|unique:users,email';
        $fields['conf_password'] = 'required|same:password';
        $fields['first_name'] = 'required';
        $fields['phone'] = 'required|unique:users,phone|numeric|digits:11';
        // $fields['is_subscribe'] = 'required';
        $validator = Validator::make($request->all(), $fields, $messages);
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => 'validation_err', 'error' => $validator->errors()], 400);
        }

        $input = $request->all();
        // Map first_name to the database column
        $input['first_name'] = $input['first_name'];
        $input['last_name'] = $input['last_name'] ?? null; // Optional field
        $input['password'] = Hash::make($input['password']);
        $input['user_type'] = 'customer'; // Set user type
        $input['status'] = 'active'; // Set status
        
        if ($user = User::create($input)) {
            // Assign customer role if using Spatie Permission
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('customer');
            }
            
            // Log registration activity
            $user->activityLogs()->create([
                'action' => 'registration',
                'description' => 'User registered',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            $token = $user->createToken('auth-token')->plainTextToken;
            $success['name'] = $user->first_name . ($user->last_name ? ' ' . $user->last_name : '');
            $success['statue'] = 200;
            $success['message'] = 'Registration & Authentication successfully done';
            $success['authorisation'] = [
                'token' => $token,
                'type' => 'bearer',
            ];
            return response()->json(['success' => $success], 200);
        } else {
            return response()->json(['status' => 500, 'message' => 'internal_server_err', 'error' => 'Internal Server Error'], 500);
        }
    }

    // myprofile
    public function myprofile()
    {
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            return response()->json([
                'status' => 'success',
                'user' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => $user->first_name . ($user->last_name ? ' ' . $user->last_name : ''),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'phone_country_code' => $user->phone_country_code,
                    'avatar' => $user->avatar,
                    'user_type' => $user->user_type,
                    'status' => $user->status,
                ],
                'customer_info' => Customer::where('user_id', '=', $user->id)->first(),
                'addresses' => $user->addresses,
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'user' => [],
            ], 200);
        }
    }
    
    // updateprofile
    public function updateprofile(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $user = User::where('id', '=', $user->id)->first();
            return response()->json([
                'status' => 'success',
                'user' => $user,
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
                'user_type' => 'required|numeric|in:3'
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

        // Only allow customer login (user_type = 3)
        if($request->input('user_type') == 3){
            $user = (new User())->getUserEmailOrPhone($request->all());
            
            if(!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Login credential is not valid.'
                ],
                400);
            }

            // Check if account is locked
            if($user->isLocked()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account has been temporarily locked. Please try again later.'
                ],
                423);
            }

            // Check if account is active
            if(!$user->isActive()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account is inactive. Please contact support.'
                ],
                403);
            }

            // Check if user is a customer (user_type = customer)
            if($user->user_type != 'customer') {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid user type. This endpoint is for customers only.'
                ],
                403);
            }

            if(Hash::check($request->input('password'), $user->password)){
                // Ensure customer role is assigned
                if (method_exists($user, 'assignRole') && !$user->hasRole('customer')) {
                    $user->assignRole('customer');
                }

                // Record login
                $user->recordLogin();

                $user_data['id'] = $user->id;
                $user_data['token'] = $user->createToken($user->email)->plainTextToken;
                $user_data['name'] = $user->first_name . ($user->last_name ? ' ' . $user->last_name : '');
                $user_data['first_name'] = $user->first_name;
                $user_data['last_name'] = $user->last_name;
                $user_data['phone'] = $user->phone;
                $user_data['avatar'] = $user->avatar;
                $user_data['email'] = $user->email;
                $user_data['user_type'] = $user->user_type;
                $user_data['roles'] = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : ['customer'];
                $user_data['permissions'] = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name') : [];
                
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully Login!',
                    'data' => [$user_data],
                    'error' => [
                        'code' => 0
                    ]
                ]);
            }else{
                $user->recordFailedLogin();
                return response()->json([
                    'status' => false,
                    'message' => 'Login credential is not valid.'
                ],
                400);
            }
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Invalid user type. This endpoint is for customers only (user_type = 3).'
            ],
            400);
        }
    }

    /**
     * Customer logout
     * Deletes the current access token
     */
    public function logout(Request $request): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();
        
        if ($user) {
            // Log logout activity
            if($user instanceof User) {
                $user->activityLogs()->create([
                    'action' => 'logout',
                    'description' => 'User logged out',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
            
            // Delete the current token (the one used for this request)
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ], 200);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 401);
    }
}
