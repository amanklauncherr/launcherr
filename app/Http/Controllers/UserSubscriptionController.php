<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionDetail;
use App\Models\UserSubscription;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserSubscriptionController extends Controller
{
    //
    public function subscribeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscription_id' => 'required|exists:subscription_details,id',
            'status' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $data = $validator->validated();

        $user=Auth::user();

        // $subscriptionDetail = SubscriptionDetail::find($data['subscription_id']);
        // $endDate = now()->addDays($subscriptionDetail->sub_days);

        $endDate = now()->addDays(30);
     
        $userSubscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_id' => $data['subscription_id'],
            // 'status' => true,
            'end_date' => $endDate,
        ]);
        
        return response()->json([
            'success' => 1,
            'message' => $userSubscription,
        ], 201);
    }
}
