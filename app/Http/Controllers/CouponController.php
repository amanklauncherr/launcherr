<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;



class CouponController extends Controller
{
    //

    /**
     * @group Coupon Management
     *
     * API to create a new coupon with specific attributes like code, applicable places, and discount percentage.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @bodyParam coupon_code string required Unique code for the coupon. Example: SPRING20
     * @bodyParam coupon_places array required Array of applicable places where the coupon can be used. Example: ["New York", "Los Angeles"]
     * @bodyParam discount numeric required Discount percentage for the coupon. Must be between 0 and 100. Example: 15
     *
     * @response 201 {
     *  "message": "Coupon created successfully",
     *  "coupon": {
     *      "id": 1,
     *      "coupon_code": "SPRING20",
     *      "coupon_places": "[\"New York\", \"Los Angeles\"]",
     *      "discount": 15,
     *      "created_at": "2024-11-14T12:00:00.000Z",
     *      "updated_at": "2024-11-14T12:00:00.000Z"
     *  }
     * }
     *
     * @response 422 {
     *   "errors": {
     *       "coupon_code": [
     *           "The coupon_code field is required.",
     *           "The coupon_code has already been taken."
     *       ],
     *       "coupon_places": [
     *           "The coupon_places field is required."
     *       ],
     *       "discount": [
     *           "The discount field is required.",
     *           "The discount must be between 0 and 100."
     *       ]
     *   }
     * }
     *
     * @response 500 {
     *   "message": "An error occurred while Adding Coupon",
     *   "error": "Error message details"
     * }
     */

    public function addCoupon(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'coupon_code' => 'required|string|unique:coupons,coupon_code',
            'coupon_places' => 'required|array',
            'discount' => 'required|numeric|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            //code...
            
        $coupon=Coupon::create([
            'coupon_code'=>$request->coupon_code,
            'coupon_places'=>json_encode($request->coupon_places),
            'discount'=>$request->discount,
        ]);

        return response()->json(['message' => 'Coupon created successfully', 'coupon' => $coupon], 201);

        } catch (\Exception $e) {
            // Return a custom error response in case of an exception
            return response()->json([
                'message' => 'An error occurred while Adding Coupon',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @group Coupon Management
     *
     * API to retrieve all available coupons.
     *
     * @response 200 {
     *   "Coupon": [
     *       {
     *           "id": 1,
     *           "coupon_code": "SPRING20",
     *           "coupon_places": "[\"New York\", \"Los Angeles\"]",
     *           "discount": 15,
     *           "created_at": "2024-11-14T12:00:00.000Z",
     *           "updated_at": "2024-11-14T12:00:00.000Z"
     *       },
     *       {
     *           "id": 2,
     *           "coupon_code": "SUMMER30",
     *           "coupon_places": "[\"Chicago\", \"San Francisco\"]",
     *           "discount": 30,
     *           "created_at": "2024-11-14T12:30:00.000Z",
     *           "updated_at": "2024-11-14T12:30:00.000Z"
     *       }
     *   ]
     * }
     *
     * @response 404 {
     *   "Message": "No Coupon Found"
     * }
     */


    public function showCoupon(){
        $coupon=Coupon::all();
        // json_decode($coupon->coupon_places);
        if($coupon->isEmpty())
        {
            return response()->json(['Message'=>'No Coupon Found'],404);
        }
        else{
            return response()->json(['Coupon'=>$coupon],200);
        }
    }

    public function applyCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string|exists:coupons,coupon_code',
            'place' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            //code...
            $coupon = Coupon::where('coupon_code',$request->coupon_code)->first();

            if(!in_array($request->place,json_decode($coupon->coupon_places))){
                return response()->json(['error' => 'Coupon not applicable for this place'], 400);
            }
    
            return response()->json(['message' => 'Coupon applied successfully', 'discount' => $coupon->discount], 200);
        } catch (ModelNotFoundException $e) {
            // Return a response if the record was not found
            return response()->json(['message' => 'Coupon not found'], 404);
        }catch (\Exception $e) {
            // Return a custom error response in case of an exception
            return response()->json([
                'message' => 'An error occurred while Adding Coupon',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @group Coupon Management
     *
     * API to update an existing coupon.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @bodyParam coupon_places array|null List of places where the coupon can be applied. Example: ["New York", "Los Angeles"]
     * @bodyParam discount numeric|null Discount percentage for the coupon. Example: 20
     *
     * @response 201 {
     *   "message": "Coupon updated successfully",
     *   "coupon": {
     *     "id": 1,
     *     "coupon_code": "SPRING20",
     *     "coupon_places": "[\"New York\", \"Los Angeles\"]",
     *     "discount": 20,
     *     "created_at": "2024-11-14T12:00:00.000Z",
     *     "updated_at": "2024-11-14T13:00:00.000Z"
     *   }
     * }
     *
     * @response 404 {
     *   "message": "Record not found"
     * }
     *
     * @response 422 {
     *   "errors": {
     *     "coupon_places": ["The coupon places field must be an array."]
     *   }
     * }
     *
     * @response 500 {
     *   "message": "An error occurred while deleting the record",
     *   "error": "<error-message>"
     * }
     */


    public function updateCoupon(Request $request,$coupon_code)
    {
        $validator = Validator::make($request->all(),[
            'coupon_places' => 'nullable|array',  
            'discount' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            //code...
            $coupon = Coupon::where('coupon_code', $coupon_code)->firstOrFail();

            $coupon->update([
                'coupon_places'=>json_encode($request->coupon_places),
                'discount' => $request->discount
            ]
            );

        return response()->json(['message' => 'Coupon updated successfully', 'coupon' => $coupon], 201);

        } catch (ModelNotFoundException $e) {
            // Return a response if the record was not found
            return response()->json(['message' => 'Record not found'], 404);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'message' => 'An error occurred while deleting the record',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @group Coupon Management
     *
     * API to delete an existing coupon by coupon code.
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
     *   "message": "Record deleted successfully"
     * }
     *
     * @response 404 {
     *   "message": "Record not found"
     * }
     *
     * @response 500 {
     *   "message": "An error occurred while deleting the record",
     *   "error": "<error-message>"
     * }
     */

    public function deleteCoupon(Request $request,$coupon_code)
    {
        try {
            //code...
            $coupon = Coupon::where('coupon_code', $coupon_code)->firstOrFail();

            $coupon->delete();

            return response()->json(['message' => 'Record deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            // Return a response if the record was not found
            return response()->json(['message' => 'Record not found'], 404);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'message' => 'An error occurred while deleting the record',
                'error' => $e->getMessage()
            ], 500);
        }

    }

}
