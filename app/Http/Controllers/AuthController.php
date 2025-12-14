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
        if($request->input('user_type') == self::ADMIN_USER){
            $user = (new User())->getUserEmailOrPhone($request->all());
            $role = self::ADMIN_USER;
        }else{
            $user= (new SalesManager())->getUserEmailOrPhone($request->all());
            $role = self::SALES_MANAGER;
        }
        
        if(!$user) {
            throw ValidationException::withMessages([
                'email'=> ['The Provided credentials are incorrect']
            ]);
        }

        // Check if account is locked
        if($user instanceof User && $user->isLocked()) {
            throw ValidationException::withMessages([
                'email'=> ['Your account has been temporarily locked. Please try again later.']
            ]);
        }

        // Check if account is active
        if($user instanceof User && !$user->isActive()) {
            throw ValidationException::withMessages([
                'email'=> ['Your account is inactive. Please contact support.']
            ]);
        }
        
        if(Hash::check($request->input('password'), $user->password)){
            // Ensure user has the correct role assigned (if using Spatie Permission)
            if (method_exists($user, 'assignRole')) {
                if ($role == self::ADMIN_USER && !$user->hasRole('admin')) {
                    $user->assignRole('admin');
                } elseif ($role == self::SALES_MANAGER && !$user->hasRole('sales_manager')) {
                    $user->assignRole('sales_manager');
                }
            }
            
            // Record login activity for User model
            if($user instanceof User) {
                $user->recordLogin();
            }
            
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
            
            $user_data['id'] = $user->id;
            $user_data['token'] = $user->createToken($user->email)->plainTextToken;
            $user_data['name'] = $user->name ?? ($user->first_name . ' ' . ($user->last_name ?? ''));
            $user_data['phone'] = $user->phone;
            $user_data['photo'] = $user->avatar ?? null;
            $user_data['email'] = $user->email;
            $user_data['role'] = $role;
            $user_data['roles'] = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [];
            $user_data['permissions'] = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name') : [];
            $user_data['employee_type'] = $user->employee_type ?? null;
            $user_data['branch'] = new ShopListResource($branch);
            return response()->json($user_data);
        }
        
        // Record failed login attempt
        if($user instanceof User) {
            $user->recordFailedLogin();
        }
        
        throw ValidationException::withMessages([
            'email'=> ['The Provided credentials are incorrect']
        ]);
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
