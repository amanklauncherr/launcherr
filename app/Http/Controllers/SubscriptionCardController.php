<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionCard;
use Illuminate\Support\Facades\Validator;

class SubscriptionCardController extends Controller
{
    //

    /**
     * @group Subscription Card Management
     *
     * Add or Update a Subscription Card
     *
     * This endpoint allows you to add a new subscription card or update an existing one based on the `card_no`. 
     * If the `card_no` already exists, the card is updated; otherwise, a new card is created.
     *
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @bodyParam card_no string required The unique identifier for the subscription card. Example: "CARD123"
     * @bodyParam title string required The title of the card (only required if creating a new card). Example: "Premium Subscription"
     * @bodyParam price string required The price of the card (only required if creating a new card). Example: "100"
     * @bodyParam price_2 string Optional secondary price of the card. Example: "90"
     * @bodyParam features array required The features of the card as an array (only required if creating a new card). Example: ["Feature 1", "Feature 2", "Feature 3"]
     * @bodyParam buttonLabel string required The button label text for the card (only required if creating a new card). Example: "Subscribe Now"
     *
     * @response 201 {
     *   "Success": 1,
     *   "Message": "Card Added Successfully"
     * }
     *
     * @response 201 {
     *   "Success": 1,
     *   "Message": "Updated Successfully"
     * }
     *
     * @response 422 {
     *   "errors": {
     *     "card_no": ["The card_no field is required."],
     *     "title": ["The title field is required."],
     *     "price": ["The price field is required."]
     *   }
     * }
     *
     * @response 500 {
     *   "success" : 0
     *   "message": "An error occurred while Adding Coupon",
     *   "error": "Detailed error message here"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function addSubCard(Request $request){
        try {
            $cardnoExists = SubscriptionCard::where('card_no', $request->card_no)->exists();
            $validator = Validator::make($request->all(), [
                'card_no' => 'required|string|unique:subscription_cards,card_no,' . $request->card_no . ',card_no',
                'title' => $cardnoExists ? 'nullable|string' : 'required|string',
                'price' => $cardnoExists ? 'nullable|string' : 'required|string',
                'price_2' => 'nullable|string',
                'features' => $cardnoExists ? 'nullable|array':'required|array',
                'buttonLabel' =>$cardnoExists ? 'nullable|string' : 'required|string',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
    
            $card = SubscriptionCard::where('card_no', $request->card_no)->first();
    
            if ($card) {
                $card->update([
                    'title' => $request->title ?? $card->title,
                    'price' => $request->price ?? $card->price,
                    'price_2' => $request->price_2 ?? $card->price_2,
                    'features' => json_encode($request->features ?? json_decode($card->features, true)),
                    'buttonLabel' => $request->buttonLabel ?? $card->buttonLabel,
            ]);
                return response()->json(['Success' => 1, 'Message' => 'Updated Successfully'], 201);
            }
            SubscriptionCard::create([
                'card_no' => $request->card_no,
                'title' => $request->title,
                'price' => $request->price,
                'price_2' => $request->price_2,
                'features' => json_encode($request->features),
                'buttonLabel' => $request->buttonLabel,
            ]);
            return response()->json(['Success' => 1, 'Message' => 'Card Added Successfully'], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                "success" => 0,
                'message' => 'An error occurred while Adding Coupon',
                'error' => $e->getMessage()
            ], 500);
        }    
    }


    /**
     * @group Subscription Card Management
     *
     * Show Subscription Cards for Front 
     *
     * This endpoint retrieves all subscription cards, formatting the response to include card details such as title, price, optional secondary price, features, and button label.     
     *
     * @response 200 [
     *   {
     *     "title": "Premium Subscription",
     *     "price": "100",
     *     "price_2": "90",
     *     "features": ["Feature 1", "Feature 2", "Feature 3"],
     *     "buttonLabel": "Subscribe Now"
     *   },
     *   {
     *     "title": "Basic Subscription",
     *     "price": "50",
     *     "features": ["Feature A", "Feature B"],
     *     "buttonLabel": "Join Today"
     *   }
     * ]
     *
     * @response 404 {
     *   "message": "No subscription cards found"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function showSubCard()
    {
        // $plans = DB::table('your_table_name')->get();
       $plans = SubscriptionCard::all();
       $formattedPlans = [];

        foreach ($plans as $plan) {
            $formattedPlan = [
                'title' => $plan->title,
                'price' => $plan->price,
            ];

            if (!is_null($plan->price_2)) {
                $formattedPlan['price_2'] = $plan->price_2;
            }

            $formattedPlan['features'] = json_decode($plan->features);
            $formattedPlan['buttonLabel'] = $plan->buttonLabel;

            $formattedPlans[] = $formattedPlan;
        }
        return response()->json($formattedPlans);
    }

    /**
     * @group Subscription Card Management
     *
     * Show All Subscription Cards (Admin View)
     *
     * This endpoint retrieves all subscription cards with full details for administrative purposes.
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
     *   'success':1,
     *   "Cards": [
     *     {
     *       "card_no": "001",
     *       "title": "Premium Subscription",
     *       "price": "100",
     *       "price_2": "90",
     *       "features": ["Feature 1", "Feature 2", "Feature 3"],
     *       "buttonLabel": "Subscribe Now",
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     },
     *     {
     *       "card_no": "002",
     *       "title": "Basic Subscription",
     *       "price": "50",
     *       "features": ["Feature A", "Feature B"],
     *       "buttonLabel": "Join Today",
     *       "created_at": "2024-01-02T00:00:00.000000Z",
     *       "updated_at": "2024-01-02T00:00:00.000000Z"
     *     }
     *   ]
     * }
     *
     * @response 404 {
     *   'success': 0,
     *   "Message": "No Subscription Card Found"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function showSubCardAdmin()
    {
        $cards=SubscriptionCard::all();
        if($cards->isEmpty()){
            return response()->json(['success'=>0,'Message'=>'No Subscription Card Found'],40);
        }
        return response()->json(['success'=>1,'Cards'=>$cards],200);
    }
}


