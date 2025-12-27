<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticateAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the bearer token from the request
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // Find the token and load the tokenable (User)
        $accessToken = PersonalAccessToken::findToken($token);
        
        if (!$accessToken) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }
        
        $user = $accessToken->tokenable;
        
        if (!$user) {
            return response()->json(['message' => 'Token user not found.'], 401);
        }
        
        // Check if user is an instance of User model and has admin user_type
        if ($user instanceof \App\Models\User && $user->user_type === 'admin') {
            // Set the authenticated user in the request
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            
            return $next($request);
        }
        
        return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
    }
}
