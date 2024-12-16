<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Card;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class CardController extends Controller
{
    //
    /**
     * @group Card Management
     *
     * Add or Update Card
     *
     * This endpoint allows you to add a new card or update an existing one based on the `Card_No`. The total number of cards is limited to three.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @bodyParam Card_No string required The unique card number. Example: "1"
     * @bodyParam Card_Heading string required The heading of the card (required if the card does not already exist). Maximum 50 characters. Example: "Our Mission"
     * @bodyParam Card_Subheading string required The subheading of the card (required if the card does not already exist). Maximum 135 characters. Example: "Delivering Excellence in Every Step"
     *
     * @response 201 {
     *   "success": 1,
     *   "message": "Card created"
     * }
     *
     * @response 201 {
     *   "success": 1,
     *   "message": "Card updated"
     * }
     *
     * @response 400 {
     *   "success": 0,
     *   "message": "Cannot create more than 3 cards"
     * }
     *
     * @response 422 {
     *   "errors": {
     *     "Card_No": [
     *       "The Card_No field is required."
     *     ],
     *     "Card_Heading": [
     *       "The Card_Heading field is required."
     *     ]
     *   }
     * }
     *
     * @response 500 {
     *   "success": 0,
     *   "message": "An error occurred while Adding or Updating Section",
     *   "error": "Detailed error message here"
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCard(Request $request)
    {
        $card = Card::where('Card_No', $request->Card_No)->first();

        $validator = Validator::make($request->all(), [
            'Card_No' => 'required|string',
            'Card_Heading' => $card ? 'nullable|string|max:50' : 'required|string|max:50',
            'Card_Subheading' => $card ? 'nullable|string|max:135' : 'required|string|max:135',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            $data = $validator->validated();

            if (!$card && Card::count() >= 3) {
                return response()->json([
                    'success'=> 0 ,
                    'message' => 'Cannot create more than 3 cards'
                ], 400);
            }

            if($card)
            {
                $card->update($data);
                return response()->json(['success'=> 1 ,'message' => 'Card updated'], 201);
            }else {
                Card::create($data);
                return response()->json(['success'=> 1 ,'message' => 'Card created'], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success'=> 0, 
                'message' => 'An error occurred while Adding or Updating Section',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
