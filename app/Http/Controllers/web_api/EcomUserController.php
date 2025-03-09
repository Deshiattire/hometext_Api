<?php

namespace App\Http\Controllers\web_api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;



class EcomUserController extends Controller
{
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
        $input['name'] =  $input['first_name'] +" "+ $input['last_name'];
        $input['shop_id'] = 4;
        $input['role_id'] = 3;
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

    // public function UserLogin(Request $request)
    // {
    //     $fields['email'] = 'required';
    //     $fields['password'] = 'required|min:6|max:12';

    //     // $fields['is_subscribe'] = 'required';

    //     $validator = Validator::make($request->all(), $fields);
    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'message' => 'validation_err', 'error' => $validator->errors()], 400);
    //     }

    //     $input = $request->all();
    //     $user = (new User())->getUserEmailOrPhone($request->all());
    //     $input['password'] = Hash::make($input['password']);

    //     if ($user) {
    //         if($user->role_id == 3 && Hash::check($request->input('password'), $user->password)){
    //             $user_data['token'] = $user->createToken($user->email)->plainTextToken;
    //             $user_data['name'] = $user->name;
    //             $user_data['phone'] = $user->phone;
    //             $user_data['photo'] = $user->photo;
    //             $user_data['email'] = $user->email;
    //             return response()->json($user_data);
    //         }
    //         else {
    //             return response()->json(['status' => 500, 'message' => 'auth_err', 'error' => 'Authentication failed'], 500);
    //         }
    //     } else {
    //         return response()->json(['status' => 500, 'message' => 'auth_err', 'error' => 'The Provided credentials are incorrect'], 500);
    //     }
    // }

    //// myprofile
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

    public function UserPasswordChange(Request $request)
    {
        $validator = Validator::make(
            $request->only('old_password', 'new_password'),
            [
                'old_password' => 'required',
                'new_password' => 'required|min:6',
            ]
        );

        $user = User::where('id', Auth::id())->first();

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation_err',
                'error' => $validator->errors()
            ],
            400);
        }
       

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Old password is incorrect'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();
        return response()->json(['message' => 'Password changed successfully']);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make(
            $request->only('email'),
            [
                'email' => 'required|email|exists:users,email',
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

        // Generate a unique token
        $token = Str::random(60);

        // Store the token in the password_resets table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        // Send reset link via email
        $resetLink = env('FRONTEND_URL') . "/reset-password?token=$token&email=" . $request->email;
        

        Mail::raw("Click the link to reset your password: $resetLink", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Reset Password');
        });

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent successfully.',
            'data' => [],
            'error' => [
                'code' => 0
            ]
        ]);
        
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make(
            $request->only('token', 'email', 'password'),
            [
                'token' => 'required',
                'email' => 'required|email|exists:users,email',
                'password' => 'required|min:6|confirmed',
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

        // Get token record
        $resetRecord = DB::table('password_resets')->where('email', $request->email)->first();


        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return response()->json(['error' => 'Invalid token'], 400);
        }

        // Reset user password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete token record
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
            'data' => [],
            'error' => [
                'code' => 0
            ]
        ]);
    }
}
