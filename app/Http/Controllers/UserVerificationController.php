<?php

namespace App\Http\Controllers;

use App\Models\UserVerification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserVerificationController extends Controller
{
    //
    /**
     * Verify user by unique code.
     *
     * This endpoint verifies a user using a unique code. If the code is valid, the user's verification status is updated.
     *
     * @group User Management
     *
     * @urlParam uniqueCode string required The unique code provided to verify the user.
     *
     * @response 200 {
     *    "success": 1,
     *    "message": "User successfully verified"
     * }
     * @response 401 {
     *    "success": 0,
     *    "message": "Not Found"
     * }
     */
    public function verify(Request $request,$uniqueCode){
        $userVerified=UserVerification::where('uniqueCode',$uniqueCode)->first();
        if (!$userVerified) {
            return response()->json(['success' => 0, 'message' => 'Not Found'], 401);
        }
        
        $userVerified->verified = 1;
        $userVerified->save();
    
        return response()->json(['success' => 1, 'message' => 'User successfully verified']);
    }
}
