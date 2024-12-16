<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;


class CheckBearerToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle(Request $request, Closure $next)
    {
        try {
            // Check if the token is present in the request
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json(['success' => 0, 'error' => 'Token not provided. This Api requires Token'], 401);
            }

            // Validate the token and authenticate the user
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['success' => 0, 'error' => 'User not found'], 404);
            }

        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json(['success' => 0, 'error' => 'Token expired'], 401);
            } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json(['success' => 0, 'error' => 'Token invalid'], 401);
            } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\JWTException) {
                return response()->json(['success' => 0, 'error' => 'Token absent'], 401);
            } else {
                return response()->json(['success' => 0, 'error' => 'Token error: ' . $e->getMessage()], 500);
            }
        }
        // If token is valid, proceed to the next request handler
        return $next($request);
    }
}
