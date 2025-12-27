<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\AuthRequest;
use App\Http\Resources\ShopListResource;
use App\Models\SalesManager;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public const ADMIN_USER = 1;
    public const SALES_MANAGER = 2;
    
    /**
     * @param AuthRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function login(AuthRequest $request): JsonResponse
    {
        // Determine user type and fetch user
        if($request->input('user_type') == self::ADMIN_USER){
            $user = (new User())->getUserEmailOrPhone($request->all());
            $role = self::ADMIN_USER;
        }else{
            $user = (new SalesManager())->getUserEmailOrPhone($request->all());
            $role = self::SALES_MANAGER;
        }
        
        // Check if user exists
        if(!$user) {
            throw ValidationException::withMessages([
                'email'=> ['The provided credentials are incorrect']
            ]);
        }

        // Verify user type matches for User model (admin should have 'admin' user_type)
        if($user instanceof User && $role == self::ADMIN_USER) {
            if($user->user_type !== 'admin') {
                throw ValidationException::withMessages([
                    'email'=> ['Access denied. This account does not have admin privileges.']
                ]);
            }
        }

        // Check if account is locked (Admin/User only)
        if($user instanceof User && $user->isLocked()) {
            throw ValidationException::withMessages([
                'email'=> ['Your account has been temporarily locked due to multiple failed login attempts. Please try again later.']
            ]);
        }

        // Check if account is active (Admin/User only)
        if($user instanceof User && !$user->isActive()) {
            throw ValidationException::withMessages([
                'email'=> ['Your account is inactive. Please contact support.']
            ]);
        }

        // Check if sales manager is active
        if($user instanceof SalesManager && $user->status != SalesManager::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'email'=> ['Your account is inactive. Please contact administrator.']
            ]);
        }
        
        // Verify password
        if(!Hash::check($request->input('password'), $user->password)){
            // Record failed login attempt
            if($user instanceof User) {
                $user->recordFailedLogin();
            }
            
            throw ValidationException::withMessages([
                'email'=> ['The provided credentials are incorrect']
            ]);
        }
        
        // Password is correct - proceed with login
        
        // Ensure user has the correct role assigned (only for User model with Spatie Permission)
        if ($user instanceof User && method_exists($user, 'assignRole')) {
            try {
                if ($role == self::ADMIN_USER && !$user->hasRole('admin')) {
                    $user->assignRole('admin');
                }
            } catch (\Exception $e) {
                // Role doesn't exist - skip assignment
            }
        }
        
        // Record login activity for User model
        if($user instanceof User) {
            $user->recordLogin();
        }
        
        // Get branch/shop information
        $branch = null;
        if($role == self::SALES_MANAGER){
            $branch = (new Shop())->getShopDetailsById($user->shop_id);
        } elseif($user instanceof User) {
            // Get primary shop for admin users
            $primaryShop = $user->primaryShop();
            if($primaryShop) {
                $branch = (new Shop())->getShopDetailsById($primaryShop->id);
            }
        }
        
        // Prepare user data for response
        $user_data = [];
        $user_data['token'] = $user->createToken($user->email)->plainTextToken;
        
        // Handle different name formats
        if($user instanceof User) {
            $user_data['name'] = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $user_data['first_name'] = $user->first_name ?? null;
            $user_data['last_name'] = $user->last_name ?? null;
        } else {
            $user_data['name'] = $user->name ?? null;
        }
        
        $user_data['phone'] = $user->phone ?? null;
        $user_data['photo'] = $user->avatar ?? $user->photo ?? null;
        $user_data['email'] = $user->email ?? null;
        $user_data['role'] = $role;
        $user_data['user_type'] = $role;
        $user_data['roles'] = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [];
        $user_data['permissions'] = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name') : [];
        $user_data['employee_type'] = $user->employee_type ?? null;
        $user_data['branch'] = $branch ? new ShopListResource($branch) : null;
        
        return response()->json($user_data);
    }

     /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): JsonResponse
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
            $user->tokens()->delete();
        }
        return response()->json(['msg'=> "You have successfully logout"]);
    }
}
