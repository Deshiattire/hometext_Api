<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For ALL API routes, ALWAYS return null (no redirect, returns 401 JSON)
        if ($request->is('api/*') || str_starts_with($request->path(), 'api/')) {
            return null;
        }
        
        // For JSON requests, return null
        if ($request->expectsJson() || $request->wantsJson()) {
            return null;
        }
        
        // Only redirect web requests to login
        try {
            return route('login');
        } catch (\Exception $e) {
            return '/login';
        }
    }
}
