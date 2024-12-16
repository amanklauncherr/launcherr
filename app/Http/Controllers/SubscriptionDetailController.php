<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionDetail;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionDetailController extends Controller
{
    //
    public function addSubscription(Request $request)
    {
        try {
            //code...
            $validator=Validator::make($request->all(),[
                'sub_type'   => 'required|string|unique:subscription_details',
                'sub_price'  => 'required|integer',
                'sub_detail' => 'required|string',
                'sub_days'   => 'required|integer',
                'find_gigs' => 'sometimes|boolean',
                'book_travel' => 'sometimes|boolean',
                'book_adventure' => 'sometimes|boolean','booking_fee' => 'sometimes|boolean','coupon_voucher' => 'sometimes|boolean','p_itinerary' => 'sometimes|boolean',
            ]);
    
            if ($validator->fails()) {
                $errors = $validator->errors()->all(); // Get all error messages
                $formattedErrors = [];
        
                foreach ($errors as $error) {
                    $formattedErrors[] = $error;
                }
    
                return response()->json([
                    'success' => 0,
                    'errors' => $formattedErrors
                ], 422);
            }
    
            $data=$validator->validated();
    
            $sub=SubscriptionDetail::create($data);
            if($sub)
            {
                return response()->json(['success'=>1,'message'=>'Subscription Details Added'],201);
            }   
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json([
                'success' => 0,
                'message' => 'Error',
                'error' => $e->getMessage()
            ], 500);
        } 
    }

}
