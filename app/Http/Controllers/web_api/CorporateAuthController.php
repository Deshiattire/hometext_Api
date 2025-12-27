<?php

namespace App\Http\Controllers\web_api;

use App\Http\Controllers\Controller;
use App\Models\CorporateProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Corporate Authentication Controller
 * Handles registration and login for B2B corporate customers
 */
class CorporateAuthController extends Controller
{
    public function __construct()
    {
        // Only protect profile routes, registration and login are public
        $this->middleware('auth:sanctum', ['only' => ['profile', 'updateProfile', 'logout']]);
    }

    /**
     * Corporate Registration
     * Creates both user account and corporate profile
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $messages = [
            'conf_password.required' => 'The confirm password field is required.',
            'conf_password.same' => 'Password and confirm password must match.',
            'trade_license_number.unique' => 'This trade license number is already registered.',
            'vat_registration_number.unique' => 'This VAT registration number is already registered.',
        ];

        $validator = Validator::make($request->all(), [
            // User Account Fields
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|max:20|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            'conf_password' => 'required|same:password',
            'phone' => 'required|unique:users,phone|numeric|digits:11',
            'phone_country_code' => 'nullable|string|max:5',
            
            // Corporate Profile Fields (Required)
            'company_legal_name' => 'required|string|max:255',
            'trade_license_number' => 'nullable|string|max:100|unique:corporate_profiles,trade_license_number',
            'vat_registration_number' => 'nullable|string|max:50|unique:corporate_profiles,vat_registration_number',
            'incorporation_date' => 'nullable|date|before:today',
            
            // Primary Contact
            'primary_contact_name' => 'required|string|max:255',
            'primary_contact_email' => 'required|email|max:255',
            'primary_contact_phone' => 'required|string|max:20',
            
            // Business Details (Optional)
            'industry' => 'nullable|string|max:100',
            'employee_count' => 'nullable|integer|min:1',
            'annual_revenue' => 'nullable|integer|min:0',
            
            // Credit & Payment Terms (Admin will approve later)
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|in:net_15,net_30,net_45,net_60,prepaid',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create User Account
            $user = User::create([
                'first_name' => $request->primary_contact_name,
                'last_name' => null,
                'email' => $request->email,
                'phone' => $request->phone,
                'phone_country_code' => $request->phone_country_code ?? '+880',
                'password' => Hash::make($request->password),
                'user_type' => 'corporate',
                'status' => 'pending', // Pending admin approval
                'company_name' => $request->company_legal_name,
                'tax_id' => $request->vat_registration_number,
                'business_type' => 'company', // Corporate accounts are always 'company' type
            ]);

            // Create Corporate Profile
            $corporateProfile = CorporateProfile::create([
                'user_id' => $user->id,
                'company_legal_name' => $request->company_legal_name,
                'trade_license_number' => $request->trade_license_number,
                'vat_registration_number' => $request->vat_registration_number,
                'incorporation_date' => $request->incorporation_date,
                'primary_contact_name' => $request->primary_contact_name,
                'primary_contact_email' => $request->primary_contact_email,
                'primary_contact_phone' => $request->primary_contact_phone,
                'industry' => $request->industry,
                'employee_count' => $request->employee_count,
                'annual_revenue' => $request->annual_revenue,
                'credit_limit' => $request->credit_limit ?? 0.00,
                'payment_terms' => $request->payment_terms ?? 'prepaid',
            ]);

            // Assign corporate role if using Spatie Permission
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('corporate');
            }

            // Log registration activity
            $user->activityLogs()->create([
                'action' => 'corporate_registration',
                'description' => 'Corporate account registered - pending approval',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();

            // Generate token (even though account is pending)
            $token = $user->createToken('corporate-auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Corporate account registered successfully. Your account is pending admin approval.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'uuid' => $user->uuid,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'company_name' => $user->company_name,
                        'user_type' => $user->user_type,
                        'status' => $user->status,
                    ],
                    'corporate_profile' => [
                        'company_legal_name' => $corporateProfile->company_legal_name,
                        'primary_contact_name' => $corporateProfile->primary_contact_name,
                        'primary_contact_email' => $corporateProfile->primary_contact_email,
                        'trade_license_number' => $corporateProfile->trade_license_number,
                        'payment_terms' => $corporateProfile->payment_terms,
                        'credit_limit' => $corporateProfile->credit_limit,
                    ],
                    'authorization' => [
                        'token' => $token,
                        'type' => 'bearer',
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Corporate Registration Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Corporate Login
     * Authenticates corporate users
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find user by email or phone
            $user = User::where('email', $request->email)
                       ->orWhere('phone', $request->email)
                       ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Verify user type is corporate
            if ($user->user_type !== 'corporate') {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is for corporate accounts only. Please use the appropriate login endpoint.'
                ], 403);
            }

            // Check password
            if (!Hash::check($request->password, $user->password)) {
                $user->recordFailedLogin();
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check if account is locked
            if ($user->isLocked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been temporarily locked due to multiple failed login attempts. Please try again later.'
                ], 423);
            }

            // Check if account is active (approved by admin)
            if ($user->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your corporate account is pending admin approval. You will be notified once approved.'
                ], 403);
            }

            if (!$user->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is inactive. Please contact support.'
                ], 403);
            }

            // Load corporate profile
            $corporateProfile = $user->corporateProfile;

            // Ensure corporate role is assigned
            if (method_exists($user, 'assignRole') && !$user->hasRole('corporate')) {
                $user->assignRole('corporate');
            }

            // Record successful login
            $user->recordLogin();

            // Generate token
            $token = $user->createToken('corporate-auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'uuid' => $user->uuid,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'company_name' => $user->company_name,
                        'user_type' => $user->user_type,
                        'status' => $user->status,
                        'avatar' => $user->avatar,
                    ],
                    'corporate_profile' => $corporateProfile ? [
                        'company_legal_name' => $corporateProfile->company_legal_name,
                        'primary_contact_name' => $corporateProfile->primary_contact_name,
                        'primary_contact_email' => $corporateProfile->primary_contact_email,
                        'primary_contact_phone' => $corporateProfile->primary_contact_phone,
                        'trade_license_number' => $corporateProfile->trade_license_number,
                        'vat_registration_number' => $corporateProfile->vat_registration_number,
                        'industry' => $corporateProfile->industry,
                        'employee_count' => $corporateProfile->employee_count,
                        'credit_limit' => $corporateProfile->credit_limit,
                        'payment_terms' => $corporateProfile->payment_terms,
                    ] : null,
                    'authorization' => [
                        'token' => $token,
                        'type' => 'bearer',
                    ],
                    'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : ['corporate'],
                    'permissions' => method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name') : [],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Corporate Login Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get Corporate Profile
     * 
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user || $user->user_type !== 'corporate') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $corporateProfile = $user->corporateProfile;
        $addresses = $user->addresses;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'phone_country_code' => $user->phone_country_code,
                    'company_name' => $user->company_name,
                    'user_type' => $user->user_type,
                    'status' => $user->status,
                    'avatar' => $user->avatar,
                    'last_login_at' => $user->last_login_at,
                ],
                'corporate_profile' => $corporateProfile,
                'addresses' => $addresses,
            ],
        ], 200);
    }

    /**
     * Update Corporate Profile
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user || $user->user_type !== 'corporate') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'phone_country_code' => 'nullable|string|max:5',
            'avatar' => 'nullable|string|max:255',
            
            // Corporate Profile Updates
            'primary_contact_name' => 'nullable|string|max:255',
            'primary_contact_email' => 'nullable|email|max:255',
            'primary_contact_phone' => 'nullable|string|max:20',
            'industry' => 'nullable|string|max:100',
            'employee_count' => 'nullable|integer|min:1',
            'annual_revenue' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update user fields
            $userFields = $request->only(['phone', 'phone_country_code', 'avatar']);
            if (!empty($userFields)) {
                $user->update($userFields);
            }

            // Update corporate profile
            $corporateProfile = $user->corporateProfile;
            if ($corporateProfile) {
                $profileFields = $request->only([
                    'primary_contact_name',
                    'primary_contact_email',
                    'primary_contact_phone',
                    'industry',
                    'employee_count',
                    'annual_revenue',
                ]);
                
                if (!empty($profileFields)) {
                    $corporateProfile->update($profileFields);
                }
            }

            // Log activity
            $user->activityLogs()->create([
                'action' => 'profile_update',
                'description' => 'Corporate profile updated',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $user->fresh(),
                    'corporate_profile' => $corporateProfile->fresh(),
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Corporate Profile Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Update failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Corporate Logout
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();

        if ($user) {
            // Log logout activity
            $user->activityLogs()->create([
                'action' => 'logout',
                'description' => 'Corporate user logged out',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Delete current token
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
