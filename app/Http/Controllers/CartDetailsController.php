<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CartDetails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartDetailsController extends Controller
{
    //

    /**
     * Update or add a product to the cart.
     *
     * This endpoint allows authenticated users to add a product to their cart or update an existing product in the cart. If the quantity is set to zero for an existing product, it will be removed from the cart.
     *
     * @group Cart
     * 
     * @bodyParam product_id integer The ID of the product. Example: 1
     * @bodyParam product_name string required The name of the product if it does not already exist in the cart. Example: "Laptop"
     * @bodyParam quantity integer required The quantity of the product (0 or more). If set to 0, the product will be removed from the cart. Example: 2
     * @bodyParam price integer required The price of a single unit of the product (must be at least 1). Example: 500
     * 
     * @response 200 {
     *   'success' : 1
     *   "message": "Cart updated",
     *   "Cart": {
     *     "product_id": 1,
     *     "product_name": "Laptop",
     *     "quantity": 2,
     *     "price": 1000,
     *     "user_id": 1
     *   }
     * }
     * @response 400 {
     *   'success' : 1
     *   "Message": "Quantity should be greater than 0"
     * }
     * @response 401 {
     *   "Success": 0,
     *   "Message": "Unauthorized, Login To Add Cart"
     * }
     * @response 500 {
     *   'success': 1
     *   "message": "An error occurred while Adding or Updating Cart Section",
     *   "error": "Exception message"
     * }
     */
    public function updateCart(Request $request)
    {
        $tokenType = $request->attributes->get('token_type');

        if ($tokenType === 'public') {
            return response()->json(['Success'=> 0, 'Message' => 'Unauthorized, Login To Add Cart']);
        } 
        elseif ($tokenType === 'user'){
            try {
                $productExist = CartDetails::where('product_id', $request->product_id)->exists();
                $validator = Validator::make($request->all(), [
                    'product_id' => 'nullable|integer',
                    'product_name' => $productExist ? 'nullable|string':'required|string',   
                    'quantity' => 'required|integer|min:0',
                    'price' => 'required|integer|min:1',
                ]);
            
                if ($validator->fails()) {
                    return response()->json($validator->errors(), 400);
                }
            
                $user = Auth::user();
            
                $data = $validator->validated();
                $total = $request->quantity * $request->price;
                $data['price'] = $total;
                $data['user_id'] = $user->id;
            
                $product = CartDetails::where('product_id', $request->product_id)->first();
                if ($productExist && $request->quantity>0) {
                    $product->update($data);
                    return response()->json(['success'=>1,'message' => 'Cart updated','Cart'=>$product], 200);
                } else if($productExist && $request->quantity === 0){
                    $product->delete();
                    return response()->json(['success'=>1,'message' => 'Product removed from cart'], 200);
                }
                else if(!$product && $request->quantity === 0)
                {
                    return response()->json(['success'=>0,'message'=>'Quantity should be greater than 0'],400);
                }
                else {
                    $finalCart = CartDetails::create($data);
                    return response()->json($finalCart);
                }
            } catch (\Exception $e) {
                // Return a custom error response in case of an exception
                return response()->json([
                    'success'=>0,
                    'message' => 'An error occurred while Adding or Updating Cart Section',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['success'=>0,'error' => 'Unauthorized.'], 401);
    }

    /**
     * Show the user's cart and update quantities.
     *
     * This endpoint returns the contents of the user's cart, including the subtotal, GST amount, and grand total. It also allows updating the quantity of products if provided in the request body.
     *
     * @group Cart
     * 
     * @bodyParam products array[] An array of products to update with their `product_id`, `quantity`, and `price`. Only products with quantities greater than zero are retained.
     * 
     * @response 200 {
     *   'success' : 1,
     *   "products": [
     *     {
     *       "product_id": 1,
     *       "product_name": "Laptop",
     *       "quantity": 2,
     *       "price": 500,
     *       "sub_total": 1000
     *     }
     *   ],
     *   "subTotal": 1000,
     *   "gstAmt": 180,
     *   "grand_Total": 1180
     * }
     * @response 401 {
     *   "success": 0,
     *   "message": "Unauthorized, Login To Check Your Cart"
     * }
     * @response 404 {
     *   'success' : 1,
     *   "message": "Please Add Some products in Your Cart"
     * }
     */

    public function showCart(Request $request){
        
        $tokenType = $request->attributes->get('token_type');

        if ($tokenType === 'public') {
            return response()->json(['success'=> 0,'message' => 'Unauthorized, Login To Check Your Cart']);
        }
        elseif($tokenType === 'user'){
            $user=Auth::user();
            $totalCart=CartDetails::where('user_id',$user->id)->get();
            if($totalCart->isEmpty())
            {
                return response()->json(['success' => 0,'message'=>'Please Add Some products in Your Cart'],404);
            }
            if ($request->has('products')) {
                foreach ($request->products as $productData) {
                    $product = CartDetails::where('user_id', $user->id)
                                        ->where('product_id', $productData['product_id'])
                                        ->first();    
                    if ($product) {
                        if ($productData['quantity'] > 0) {
                            $product->update(['quantity' => $productData['quantity']]);
                        $total = $productData['quantity'] * $productData['price'];
                        $product->update([
                            'quantity' => $productData['quantity'],
                            'price' => $total,
                        ]);
                        } else {
                            $product->delete();
                        }
                    }
                }
                $totalCart = CartDetails::where('user_id', $user->id)->get();
            }
    
            $total=$totalCart->sum('price');
            // $gstAmount = $total * 0.18;
            // $gstAmount=$gstAmount;
            // $grand=$total + $gstAmount;
    
            $products = $totalCart->map(function($product) {
                $productArray = $product->toArray();
                $productArray['sub_total'] = $product->price;
                $productArray['price'] = $product->price / $product->quantity;
                unset($productArray['created_at']); // Remove the original 'price' key
                unset($productArray['updated_at']); // Remove the original 'price' key
                return $productArray;
            });
    
            return response()->json(
            [
                'success' => 1,
                'products'=>$products,
                'subTotal'=>$total,
                // 'gstAmt' => $gstAmount,
                // 'grand_Total'=>ceil($grand)
            ],200);
            
        }
    }
}
