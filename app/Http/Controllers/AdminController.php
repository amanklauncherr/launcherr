<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

class AdminController extends Controller
{   
    
    /**
     * @group AdminAuth
     *
     * Register a new admin.
     *
     * This endpoint registers a new user with the role of "admin" and returns the user details along with a JWT token upon successful registration.
     *
     * @bodyParam name string required The name of the user. Example: John Doe
     * @bodyParam email string required The email address of the user. Must be unique and follow RFC validation. Example: john.doe@example.com
     * @bodyParam password string required The password for the user, must be at least 8 characters long and contain an uppercase letter, a lowercase letter, a digit, and a special character. Example: StrongP@ssw0rd
     *
     * @response 201 {
     *     "success": 1
     *     "admin": {
     *         "id": 1,
     *         "name": "John Doe",
     *         "email": "john.doe@example.com",
     *         "created_at": "2024-11-11T19:16:43.000000Z",
     *         "updated_at": "2024-11-11T19:16:43.000000Z",
     *         "roles": ["admin"]
     *     },
     *     "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
     * }
     *
     * @response 422 {
     *     "success": 0,
     *     "error": "The email is invalid."
     * }
     *
     * @response 500 {
     *     "success": 0,
     *     "message": "Error while Register",
     *     "error": "Exception message here"
     * }
     */
    public function register(Request $request){
        $validator = Validator::make($request->all(),[
            'name' => 'required|string|max:50',
            'email' => [
                'required',
                'email',
                'unique:users',
                'max:50',
                function ($attribute, $value, $fail) {
                    $validator = new EmailValidator();
                    if (!$validator->isValid($value, new RFCValidation())) {
                        $fail('The '.$attribute.' is invalid.');
                    }
                },
            ],
             'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/'
            ]
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->all(); // Get all error messages
            $formattedErrors = [];
            foreach ($errors as $error) {
                $formattedErrors[] = $error;
            }
            return response()->json([
                'success' => 0,
                'error' => $formattedErrors[0]
            ], 422);
        }

        try {
            //code...
            $admin = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
    
            $token = JWTAuth::fromUser($admin);
            $admin->assignRole('admin');
    
            return response()->json(compact('admin'), 201);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json([
                'message' => 'Error while Register',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @group AdminAuth
     *
     * Admin Login
     *
     * This endpoint allows an admin user to log in by providing their email and password.
     * Only users with the "admin" role are authorized to log in using this endpoint.
     *
     * @bodyParam email string required The email address of the user. Example: admin@example.com
     * @bodyParam password string required The user's password, must be at least 8 characters long and contain an uppercase letter, a lowercase letter, a digit, and a special character. Example: StrongP@ssw0rd
     *
     * @response 200 {
     *     "success": 1
     *     "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
     *     "token_type": "bearer",
     *     "expires_in": 3600,
     *     "user" : {
     *         "id": 1,
     *         "name": "John Doe",
     *         "email": "john.doe@example.com",
     *         "created_at": "2024-11-11T19:16:43.000000Z",
     *         "updated_at": "2024-11-11T19:16:43.000000Z",
     *         "roles": ["admin"]
     *      }
     *   }
     *
     * @response 401 {
     *     "success": 0,
     *     "error": "Password does not match"
     * }
     *
     * @response 401 {
     *     "success": 0,
     *     "error": "Unauthorized Login Role. Only Admin can Login"
     * }
     *
     * @response 404 {
     *     "success": 0,
     *     "error": "Email doesn't exist"
     * }
     *
     * @response 422 {
     *     "success": 0,
     *     "error": "The email field is required."
     * }
     *
     * @response 500 {
     *     "success": 0,
     *     "message": "Error while logging in",
     *     "error": "Exception message here"
     * }
     */

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(),
        [
            'email'=>'required|email',
            'password'=>[
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/'
            ],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all(); // Get all error messages
            $formattedErrors = [];
            foreach ($errors as $error) {
                $formattedErrors[] = $error;
            }
            return response()->json([
                'success' => 0,
                'error' => $formattedErrors[0]
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        $admin = User::where('email', $credentials['email'])->first();

        if (!$admin) {
            return response()->json(['success' => 0, 'error' => "Email doesn't exist"], 404);
        }
        
        if (!Hash::check($credentials['password'], $admin->password)) {
            return response()->json(['success' => 0, 'error' => 'Password does not match'], 401);
        }
        
        if (!$admin->hasRole('admin')) {
            return response()->json(['success' => 0, 'error' => 'Unauthorized Login Role. Only Admin can Login'], 401);
        }
        
        try {
            $token = Auth::guard('api')->login($admin);
            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => 'Error while logging in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function respondWithToken($token){
        return response()->json([
            "success" => 1,
            'access_token'=>$token,
            'token_type'=>'bearer',
            'expires_in'=>auth()->guard('api')->factory()->getTTL()*60,
            'user'=>Auth::guard('api')->user(),
            // 'refresh_token' => $this->createRefreshToken(),
        ], 200);
    }

    /**
     * @group AdminAuth
     *
     * Get Profile
     *
     * Retrieves the authenticated Admin's profile.
     *
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @response 200 {
     *     "success": 1,
     *     "data": {
     *         "id": 1,
     *         "name": "Admin User",
     *         "email": "admin@example.com",
     *         "created_at": "2024-01-01T00:00:00.000000Z",
     *         "updated_at": "2024-01-01T00:00:00.000000Z"
     *     }
     * }
     *
     * @response 401 {
     *     "success": 0,
     *     "error": "No User Found"
     * }
     */

    public function profile()
    {
        $user = Auth::guard('api')->user();
    
        if (!$user) {
            return response()->json(['success' => 0, 'error' => 'No User Found'], 401);
        }
        return response()->json(['success' => 1, 'data' => $user], 200);    
    }

    /**
     * @group AdminAuth
     *
     * Update Admin Profile
     *
     * Allows the authenticated admin to update their profile information.
     * The name, email, and password fields are optional, but if provided,
     * they must meet the specified validation criteria.
     *
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * 
     * @bodyParam name string optional The user's name, max 50 characters. Example: John Doe
     * @bodyParam email string optional The user's email address. Example: newadmin@example.com
     * @bodyParam password string optional The new password, must be at least 8 characters long and contain an uppercase letter, a lowercase letter, a digit, and a special character. Example: StrongP@ssw0rd
     *
     * @response 200 {
     *     "success" : 1
     *     "message": "Profile updated successfully",
     *     "user": {
     *         "id": 1,
     *         "name": "Updated Name",
     *         "email": "updatedemail@example.com",
     *         "created_at": "2024-01-01T00:00:00.000000Z",
     *         "updated_at": "2024-01-02T00:00:00.000000Z"
     *     }
     * }
     *
     * @response 422 {
     *     "success" : 0 
     *     "errors" : "The email has already been taken."
     *     }
     * }
     *
     * @response 404 {
     *     'success': 0,
     *     "message": "Record not found"
     * }
     *
     * @response 500 {
     *     'success': 0,
     *     "message": "Error while Updating Admin Profile",
     *     "error": "Exception message here"
     * }
     */
    public function updateProfile(Request $request){
        $validator = Validator::make($request->all(),[
            'name'=>'nullable|string|max:50',
            'email'=>'nullable|email|unique:users,email'.Auth::id(),
            'password'=>[
                'nullable',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/'
            ],
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first(); 
            return response()->json([
                'success' => 0,
                'error' => $error
            ], 422);
        }
        try {
            //code...
            $user=Auth::user();

            if ($request->filled('name')) {
                $user->name = $request->name;
            }
    
            if ($request->filled('email')) {
                $user->email = $request->email;
            }
    
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
    
            $user->save();

            return response()->json([
                "success" => 1,
                'message' => 'Profile updated successfully', 'user' => $user
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return a response if the record was not found
            return response()->json(
                [
                    'success'=> 0,
                    'message' => 'Record not found'
                ], 404);
        }catch (\Exception $e) {
            // Handle any exceptions
            return response()->json([
                'success'=> 0,
                'message' => 'Error while Updating Admin Profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}




   
// public function refresh()
    // {
    //     return $this->respondWithToken(JWTAuth::refresh());
    // }

    // protected function createRefreshToken() {
    //     // $customClaims = ['type' => 'refresh'];
      
    //     try {
    //     // $refreshToken = JWTAuth::customClaims($customClaims)
    //     //     ->setTTLMinutes(config('jwt.refresh_ttl'))
    //     //     ->fromUser(auth()->user());
      
    //     //   return $refreshToken;

    //     $customClaims = [
    //         'type' => 'refresh',
    //         'exp' => now()->addMinutes(config('jwt.refresh_ttl'))->timestamp
    //     ];
    //     return JWTAuth::claims($customClaims)->fromUser(auth()->user());

    //     } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
    //       report($e); // Report the exception for logging or debugging
      
    //       return response()->json([
    //         'message' => 'Error generating refresh token',
    //         'error' => $e->getMessage(), // Consider providing a more generic message for security reasons
    //       ], 500);
    //     }
    //   }

    // public function allUser()
    // {
    //     $users= User::all();      
    //     if($users->isEmpty())
    //     {
    //         return response()->json(['error' => 'not found'], 404);
    //     }
    //     return response()->json(['message'=>$users]);
    // }
    

    // public function logout()
    // {
    //     Auth::guard('api')->logout();
    //     return response()->json(['message' => 'Successfully logged out']);
    // }

    // try {
    //     // Extract the refresh token from the Authorization header
    //     $authHeader = $request->header('Authorization');
    //     if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
    //         return response()->json(['error' => 'Refresh token not provided'], 400);
    //     }

    //     $refreshToken = str_replace('Bearer ', '', $authHeader);

    //     // Pass the refresh token to the JWTAuth::refresh() method
    //     $newToken = JWTAuth::setToken($refreshToken)->refresh();

    //     return $this->respondWithToken($newToken);
    // } catch (JWTException $e) {
    //     return response()->json(['error' => 'Could not refresh token'], 500);
    // }
    
    // protected function createRefreshToken(){
    //     // return JWTAuth::claims(['type' => 'refresh'])->attempt(['email' => JWTAuth::user()->email, 'password' =>'']);

    //     // $customClaims = ['type' => 'refresh'];
    //     // return JWTAuth::customClaims($customClaims)->fromUser(auth()->user());

    //     $customClaims = ['type' => 'refresh'];
    //     $refreshToken = JWTAuth::customClaims($customClaims)
    //         ->setTTL(config('jwt.refresh_ttl'))
    //         ->fromUser(auth()->user());
    
    //     return $refreshToken;
    // }
