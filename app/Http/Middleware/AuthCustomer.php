<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            if(Auth::user()->role_id == 3){
                return $next($request);
            }
        } else {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Sorry, you don\'t have permission.'
                ]
            ]);
        }
        return $next($request);
    }
}
