<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Mail;
use App\Mail\UserVerificationConfirmation;
use App\Mail\UserResetPassword;

use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Illuminate\Support\Facades\Crypt;

class UserProfileController extends Controller
{
    //

    /**
     * Register a new user.
     *
     * This endpoint registers a new user with the provided details.
     * It requires a name, last name, email, and password, validates the input,
     * and sends an email verification link if the registration is successful.
     *
     * @group User Management
     *
     * @bodyParam name string required The first name of the user. Example: John
     * @bodyParam last_name string required The last name of the user. Example: Doe
     * @bodyParam email string required The unique email of the user. Must be a valid email format. Example: john.doe@example.com
     * @bodyParam password string required The password for the user account. Must be at least 8 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.
     *
     * @response 201 {
     *     "success": 1,
     *     "message": "User registered successfully. Visit Your email to Verify",
     *     "user": {
     *         "id": 1,
     *         "name": "John",
     *         "last_name": "Doe",
     *         "email": "john.doe@example.com",
     *         "updated_at": "2024-11-14T12:00:00.000000Z",
     *         "created_at": "2024-11-14T12:00:00.000000Z"
     *     }
     * }
     *
     * @response 422 {
     *     "success": 0,
     *     "error": "Validation error message"
     * }
     *
     * @response 500 {
     *     "success": 0,
     *     "message": "Error while Register",
     *     "error": "Detailed error message"
     * }
     */

    public function userRegister(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
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
            $user = User::create([
                'name' => $request->name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            // $token = JWTAuth::fromUser($user);
            $user->assignRole('user');
    
            if ($user) {
                UserVerification::create([
                    'userID'=>$user->id,
                    'uniqueCode'=> Str::random(100),
                    'verified' => 0,
                ]);              
                $code=UserVerification::where('userID',$user->id)->first();
                
                Mail::to($request->email)->send(new UserVerificationConfirmation($code->uniqueCode));
                return response()->json([
                    'success' => 1,
                    'message' => 'User registered successfully. Visit Your email to Verify',
                    'user' => $user,
                ], 201);
            }
             else {
                return response()->json([
                    'success' => 0,
                    'error' => 'Failed to register user'
                ], 500);
            }
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json([
                'success' => '0',
                'message' => 'Error while Register',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * User Login
     *
     * This endpoint authenticates a user with their email and password.
     * It validates the credentials, checks if the user is verified, ensures they have the 'user' role,
     * and returns an access token if successful.
     *
     * @group User Management
     *
     * @bodyParam email string required The user's email address. Example: john.doe@example.com
     * @bodyParam password string required The user's password. Must be at least 8 characters, containing at least one uppercase letter, one lowercase letter, one number, and one special character.
     *
     * @response 200 {
     *     "success": 1,
     *     "user": {
     *         "id": 1,
     *         "name": "John",
     *         "email": "john.doe@example.com",
     *         "created_at": "2024-11-14T12:00:00.000000Z",
     *         "updated_at": "2024-11-14T12:00:00.000000Z"
     *     },
     *     "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
     *     "token_type": "bearer",
     *     "expires_in": 3600
     * }
     *
     * @response 401 {
     *     "success": 0,
     *     "error": "Unauthorized Login Role. Only User can Login"
     * }
     *
     * @response 401 {
     *     "success": 0,
     *     "error": "Password does not match"
     * }
     *
     * @response 401 {
     *     "success": 0,
     *     "error": "Please Verify. Before Login"
     * }
     *
     * @response 404 {
     *     "success": 0,
     *     "error": "Email does not exist"
     * }
     *
     * @response 422 {
     *     "success": 0,
     *     "error": "Validation error message"
     * }
     *
     * @response 500 {
     *     "success": 0,
     *     "message": "Error while Login",
     *     "error": "Detailed error message"
     * }
     */
    public function userLogin(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'email' => 'required|email|max:50',
            'password' => [
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

            //code...
            $credentials = $request->only('email','password');
            $user = User::where('email',$credentials['email'])->first();
            
            if(!$user){
                return response()->json([ 'success' => 0,'error' => 'Email does not exist'], 404);
            }
            $userVerified=UserVerification::where('userID',$user->id)->first();
            if($userVerified->verified === 0)
            {
                return response()->json([ 'success' => 0,'error' => 'Please Verify. Before Login'], 401);
            }
            if(!Hash::check($credentials['password'],$user->password))
            {
                return response()->json([ 'success' => 0,'error' => 'Password does not match'], 401);
            }
            if (!$user->hasRole('user')) 
            {
                // User has the 'admin' role
                return response()->json([ 'success' => 0,'error' => 'Unauthorized Login Role. Only User can Login'], 401);  
            }    

        try {
                $token=Auth::guard('api')->login($user);
                return $this->respondWithToken($token);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json([
                'success' => 0,
                'message' => 'Error while Login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function respondWithToken($token){
        return response()->json([
            'success' => 1,
            'user'=>Auth::guard('api')->user(),
            'access_token'=>$token,
            'token_type'=>'bearer',
            'expires_in'=>auth()->guard('api')->factory()->getTTL()*60,
            
        ]);
    }

    /**
     * Update User Password
     *
     * This endpoint allows an authenticated user to update their password. It checks if the old password is correct,
     * verifies that the new password matches the confirmation password, and updates the password if all checks pass.
     *
     * @group User Management
     *
     * @bodyParam old_password string required The user's current password. Must be at least 8 characters. Example: OldPassword1!
     * @bodyParam new_password string required The user's new password. Must be at least 8 characters, containing at least one uppercase letter, one lowercase letter, one number, and one special character. Example: NewPassword1!
     * @bodyParam confirm_new_password string required Confirmation of the new password. Must match the new password. Example: NewPassword1!
     *
     * @response 201 {
     *     "message": "Password Updated Successfully"
     * }
     *
     * @response 401 {
     *     "error": "Old Password does not match"
     * }
     *
     * @response 401 {
     *     "error": "Confirm New Password Should match with New Password"
     * }
     *
     * @response 401 {
     *     "error": "Unauthorized"
     * }
     *
     * @response 422 {
     *     "errors": {
     *         "old_password": [
     *             "The old password field is required."
     *         ],
     *         "new_password": [
     *             "The new password must be at least 8 characters.",
     *             "The new password must contain an uppercase letter, a lowercase letter, a number, and a special character."
     *         ],
     *         "confirm_new_password": [
     *             "The confirm new password must match the new password."
     *         ]
     *     }
     * }
     *
     * @response 500 {
     *     "message": "Error while Updating User Profile",
     *     "error": "Detailed error message"
     * }
     */

    public function passwordUpdateUser(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'old_password'=>'required|string|min:8',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/'
            ],
            'confirm_new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/'
            ],
        ]);

        if($validator->fails())
        {
           return response()->json(['errors' => $validator->errors()], 422);   
        }

        try {
            //code...
            $credentials = $request->only('old_password', 'new_password','confirm_new_password');

            $tokenType = $request->attributes->get('token_type');
            if ($tokenType === 'public') {
                return response()->json(['Success'=> 0,'data' => 'Unauthorized, Public token provided, Login To Access']);
            } elseif ($tokenType === 'user') 
            {
                // $user = Auth::user();

                $user = $request->attributes->get('user');
                if(!Hash::check($credentials['old_password'],$user->password)){
                    return response()->json(['success'=> 0,'error' => 'Old Password does not match'], 401);
                }
                if($credentials['new_password'] != $credentials['confirm_new_password']){
                    return response()->json(['success'=> 0,'error' => 'Confirm New Password Should match with New Password '], 401);
                }

                $user->password = Hash::make($credentials['new_password']);

                $user->save();

                return response()->json(['success'=> 1,'message' => 'Password Updated Succcessfully'], 201);
            }

            // return response()->json(['success'=> 0,'error' => 'Unauthorized'], 401);


        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json([
                'message' => 'Error while Updating Admin Profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add or update the user profile.
     *
     * This endpoint allows an authenticated user to create or update their profile.
     * If the profile exists, it will be updated with the provided details.
     *
     * @group User Management
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after User Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}     
     *
     * @bodyParam user_Number integer The user's phone number. Required if creating a profile, else optional.
     * @bodyParam user_Address string The user's address. Required if creating a profile, else optional.
     * @bodyParam user_City string The user's city. Required if creating a profile, else optional.
     * @bodyParam user_State string The user's state. Required if creating a profile, else optional.
     * @bodyParam user_Country string The user's country. Required if creating a profile, else optional.
     * @bodyParam user_PinCode string The user's postal code. Required if creating a profile, else optional.
     * @bodyParam user_AboutMe string The user's bio or description. Required if creating a profile, else optional.
     *
     * @response 201 {
     *    "success": 1,
     *    "message": "Profile created successfully",
     *    "profile": {
     *       "user_id": 1,
     *       "user_Number": "1234567890",
     *       "user_Address": "123 Main St",
     *       "user_City": "New York",
     *       "user_State": "NY",
     *       "user_Country": "USA",
     *       "user_PinCode": "10001",
     *       "user_AboutMe": "Hello, I am a developer."
     *    }
     * }
     * @response 422 {
     *    "errors": "Validation error message here"
     * }
     * @response 401 {
     *    "success": 0,
     *    "message": "Unauthorized, Login To Update Your Profile"
     * }
     */

    public function AddUserProfile(Request $request)
    {
        $tokenType = $request->attributes->get('token_type');

        if ($tokenType === 'public') {
            return response()->json(['success'=> 0,'message' => 'Unauthorized, Login To Update Your Profile']);
        }
        elseif ($tokenType === 'user'){
            // $user = Auth::user();
            $user = $request->attributes->get('user');
            $userProfile= UserProfile::where('user_id',$user->id)->first();
            $validator = Validator::make($request->all(), [
                // 'last_name' => 'req'
                'user_Number' => $userProfile ? 'nullable|integer|unique:user_profiles,user_Number' : 'required|integer|unique:user_profiles,user_Number',
                'user_Address' => $userProfile ? 'nullable|string' : 'required|string',
                'user_City' => $userProfile ? 'nullable|string' : 'required|string',
                'user_State' => $userProfile ? 'nullable|string' : 'required|string',
                'user_Country' => $userProfile ? 'nullable|string' : 'required|string',
                'user_PinCode' => $userProfile ? 'nullable|string' : 'required|string',
                'user_AboutMe' => 'nullable|string',
            ]);
        
            if ($validator->fails()) {
                $error=$validator->errors()->first();
                return response()->json([
                    "success" => 0,
                    'errors' => $error
                ], 422);
            }
        
            try {
                // $user = Auth::user();
                $profile = $request->only(['user_Number', 'user_Address', 'user_City', 'user_State', 'user_Country', 'user_PinCode', 'user_AboutMe']);             
                
                
                
                if($userProfile)
                {
                    // $user->name = $request->user_Name;
                    // $user->last_name = $re
                    $userProfile->update($profile);
                    // $user->save();   
                    
                    return response()->json([
                        'success'=> 1,
                        'message' => 'Profile updated successfully',
                        'profile' => $userProfile,
                        'user'=>$user
                    ], 200);
                }
                else{

                    if(!isset($profile['user_AboutMe']))
                    {
                    $profile['user_AboutMe'] = 'About Me';
                    }

                    $Profile = UserProfile::create([
                        'user_id' => $user->id,
                        'user_Number' => $profile['user_Number'],
                        'user_Address' => $profile['user_Address'],
                        'user_City' => $profile['user_City'],
                        'user_State' => $profile['user_State'],
                        'user_Country' => $profile['user_Country'],
                        'user_PinCode' => $profile['user_PinCode'],
                        'user_AboutMe' => $profile['user_AboutMe']
                    ]);

                    $userisProfile = User::where('id',$user->id)->first();

                    $userisProfile->update([
                        'isProfile' => true
                    ]);

                    return response()->json(['success'=>1, 'message' => 'Profile created successfully', 'profile' => $Profile], 201);
                }
    
            } catch (\Exception $e) {
                return response()->json([
                    'success' => 0,
                    'message' => 'An error occurred while Creating User Profile',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['success'=>0,'error' => 'Unauthorized.'], 401);
    }


    /**
     * Show the user's profile information.
     *
     * This endpoint allows an authenticated user to view their profile information.
     * If the user profile doesn't exist, a message prompting them to create one is returned.
     *
     * @group User Management
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after User Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @response 200 {
     *    "success": 1,
     *    "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *    },
     *    "userprofile": {
     *       "user_id": 1,
     *       "user_Number": "1234567890",
     *       "user_Address": "123 Main St",
     *       "user_City": "New York",
     *       "user_State": "NY",
     *       "user_Country": "USA",
     *       "user_PinCode": "10001",
     *       "user_AboutMe": "Hello, I am a developer."
     *    }
     * }
     * 
     * @response 200 {
     *    "success": 1,
     *    "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *    },
     *    "userprofile": "Please add your personal information in your profile"
     * }
     * 
     * @response 401 {
     *    "success": 0,
     *    "message": "Unauthorized, Login To Update Your Profile"
     * }
     */
    public function showUserProfile(Request $request)
    {
        try {
            //code...            
        $tokenType = $request->attributes->get('token_type');

        if ($tokenType === 'public') {
            return response()->json(['success'=> 0,'message' => 'Unauthorized, Login To Update Your Profile']);
        }
        elseif ($tokenType === 'user')
        {
            // $user=Auth::user();
            $user = $request->attributes->get('user');
            $id=$user->id;
            // return response()->json([$user->id]);
            $userProfile=UserProfile::where('user_id',$id)->first();
            if($user && $userProfile)
            {        
                return response()->json([
                    'success' =>1,
                    'user'=>$user,
                    'userprofile'=>$userProfile
                ],200);
            }            
            if($user && !$userProfile)
            {
                {        
                    return response()->json([
                        'success' => 1,
                        'user'=>$user,
                        'userprofile'=>'Please add your personal information in your profile'
                    ],200);
                }      
            }
        }
            return response()->json(['success'=>0,'error' => 'Unauthorized.'], 401);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while Showing User data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a password reset email.
     *
     * This endpoint allows a user to request a password reset by providing their email address.
     * If the email is valid and belongs to a user with the "user" role, an encrypted token is sent to the email.
     *
     * @group User Password Reset
     *
     * @bodyParam email string required The user's email address.
     *
     * @response 200 {
     *    "success": 1,
     *    "message": "Mail Sent Successfully. Check Your mail"
     * }
     * @response 400 {
     *    "success": 0,
     *    "error": "No User Exists with provided email"
     * }
     * @response 401 {
     *    "success": 0,
     *    "error": "Unauthorized Email Role. Only User email can access"
     * }
     * @response 422 {
     *    "success": 0,
     *    "error": "Validation error message here"
     * }
     */
    
    public function ResetPasswordEmail(Request $request)
    {
        $validator=Validator::make(request()->all(),[
            'email' => 'required|string',
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json([
                'success' => 0,
                'error' => $error 
            ], 422);
        }  

        $data=$validator->validated();

        $user = User::where('email', $data['email'])->first();
        
        if(!$user)
        {
            return response()->json(['success'=>0, 'error'=>'No User Exists with provided email'], 400);
        }
        if (!$user->hasRole('user')) 
        {
            return response()->json([ 'success' => 0,'error' => 'Unauthorized Email Role. Only User email can access'], 401);  
        }    
        try {

            $tokenData = [
                'email' => $data['email'],
                'created_at' => Carbon::now()->timestamp
            ];
            
            $encryptedToken =  Crypt::encryptString(json_encode($tokenData));

            Mail::to($data['email'])->send(new UserResetPassword($encryptedToken));
 
            return response()->json([
                'success' => 1,
                'message' => 'Mail Sent Successfully. Check Your mail'
            ], 200);  

        } catch (\Exception $e) {
            //throw $th;
            return response()->json([
                'message' => 'An error occurred while Adding Email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset the user's password.
     *
     * This endpoint allows a user to reset their password using a token sent to their email.
     * The token is validated, and if it has not expired, the user's password is updated.
     *
     * @group User Password Reset
     *
     * @bodyParam token string required The token sent to the user's email for verification.
     * @bodyParam password string required The new password (minimum 8 characters, must include at least one uppercase letter, one lowercase letter, one number, and one special character).
     *
     * @response 200 {
     *    "success": 1,
     *    "message": "Password Updated Successfully"
     * }
     * @response 400 {
     *    "success": 0,
     *    "error": "Your Link has Expired create a new one"
     * }
     * @response 401 {
     *    "success": 0,
     *    "error": "Unauthorized Email Role. Only User email can access"
     * }
     * @response 422 {
     *    "success": 0,
     *    "error": "Validation error message here"
     * }
     * @response 500 {
     *    "success": false,
     *    "message": "An error occurred while updating the password"
     * }
     */

    public function ResetPassword(Request $request)
    {
        $validator=Validator::make(request()->all(),[
            "token" => 'required|string',
            "password" => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/'
            ]

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
            $data=$validator->validated();


            $decryptedTokenData = Crypt::decryptString($data['token']);
            $tokenData = json_decode($decryptedTokenData, true);
        
            $email = $tokenData['email'];
            $createdAt = Carbon::createFromTimestamp($tokenData['created_at']);
            
            // Set the expiration time (e.g., 5 minutes)
            $expirationTime = $createdAt->addMinutes(5);
        
            if (Carbon::now()->greaterThan($expirationTime)) {
                return response()->json(['success' => 0, 'error' => 'Your Link has Expired create a new one'], 400);
            }

            $user=User::where('email',$email)->first();

            if(!$user)
            {
                return response()->json(['success'=>0, 'error'=>'No User Exists with provided email'], 400);
            }
            if (!$user->hasRole('user')) 
            {
                // User has the 'admin' role
                return response()->json([ 'success' => 0,'error' => 'Unauthorized Email Role. Only User email can access'], 401);  
            }
            $user->update([
                'password' => Hash::make($data['password'])
            ]);

            return response()->json(['success'=>1, 'message'=> 'Password Updated Successfully'],200);
        } catch  (\Exception $e) {
                // Handle exception (e.g. network issues)
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
        }
    }

}
