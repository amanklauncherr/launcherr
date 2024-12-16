<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class PublicTokenOrAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $publicToken = env('PUBLIC_TOKEN','WlDEZA0QpYffLXRRjPTaAwE83YImK4JE5utDP91fBpmbyiBIvejCRWmDMuYpm4xIYYJU2muY9aQql6RiYY7khCS9BPOW8ra8ezTHyX1pQcYhtOd5b5ZT2fkuHxwazFSdlYBqlVByqaz72jRhLJx5x7J7dolhLGGo28fTklfQwq77cwDu0QBLkiAUAWwX10abith47P3xrA9sL2DH9kra14X9w6JRae36PpbycXg1uhoXvIMOzpqHHDUyto6');

        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['success' => 0, 'error' => 'Token not provided. This Api requires Token'], 401);
        }else
        {
            if ($request->header('Authorization') === 'Bearer ' . $publicToken) {
                // Add a flag to the request indicating it is a public request
                $request->attributes->set('token_type', 'public');
                return $next($request);
            }else
            {
                try 
                {
                    $user = JWTAuth::parseToken()->authenticate();
                    if (!$user) {
                        return response()->json(['success' => 0, 'error' => 'User not found'], 404);
    
                        // Add a flag to the request indicating it is a user request
                    }
                    $request->attributes->set('token_type', 'user');
                    $request->attributes->set('user', $user);
                
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
    
                return $next($request);
            }
    
        }
       

        // If neither token is valid, return unauthorized response
        // return response()->json(['error' => 'Unauthorized'], 401);
    }
}

// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;
// use Tymon\JWTAuth\Facades\JWTAuth;

// class PublicTokenMiddleware
// {
//     public function handle(Request $request, Closure $next)
//     {
//         // Define your public token
//         $publicToken = env('PUBLIC_TOKEN', 'your-public-token');

//         // Check if the token matches the public token
//         if ($request->header('Authorization') === 'Bearer ' . $publicToken) {
//             // Add a flag to the request indicating it is a public request
//             $request->attributes->set('token_type', 'public');
//             return $next($request);
//         }

//         // Check if the token matches a user token
//         try {
//             if ($user = JWTAuth::parseToken()->authenticate()) {
//                 // Add a flag to the request indicating it is a user request
//                 $request->attributes->set('token_type', 'user');
//                 $request->attributes->set('user', $user);
//                 return $next($request);
//             }
//         } catch (\Exception $e) {
//             // Do nothing, will return unauthorized below
//         }

//         // If neither token is valid, return unauthorized response
//         return response()->json(['error' => 'Unauthorized'], 401);
//     }
// }