<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\CartDetails;
use GuzzleHttp\Client;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaymentController extends Controller
{
    
    // public function paypal(Request $request)
    // {
    //     // Validate price input
    //     $request->validate([
    //         'price' => 'required|numeric|min:1',
    //     ]);
    
    //     // Create PayPal client and set API credentials
    //     $provider = new PayPalClient();
    //     $provider->setApiCredentials(config('paypal'));
    //     $paypalToken = $provider->getAccessToken();
    
    //     // Create PayPal order
    //     $response = $provider->createOrder([
    //         "intent" => "CAPTURE",
    //         "application_context" => [
    //             "return_url" => secure_url(route('success')),
    //             "cancel_url" => secure_url(route('cancel')),
    //         ],
    //         "purchase_units" => [
    //             [
    //                 "amount" => [
    //                     "currency_code" => "USD",
    //                     "value" => $request->price,
    //                 ]
    //             ]
    //         ]
    //     ]);
    
    //     // Check for successful order creation
    //     if (isset($response['id']) && $response['id'] != null) {
    //         // Look for the approval link to redirect user
    //         foreach ($response['links'] as $link) {
    //             if ($link['rel'] === 'approve') {
    //                 // Save payment details in the database
    //                 Payment::create([
    //                     'paypal_order_id' => $response['id'],
    //                     'amount' => $request->price,
    //                     'status' => 'PENDING',  
    //                     'user_id' => auth()->id(),
    //                 ]);
    
    //                 return response()->json(['approve_link' => $link['href']]);
    //             }
    //         }
    //     } else {
    //         // Log error and return a failure response
    //         Log::error('PayPal Order Creation Failed', ['response' => $response]);
    //         return response()->json(['error' => 'Failed to create PayPal order. Please try again later.'], 500);
    //     }
    // }
    

    // public function success(Request $request)
    // {
    //     try {
    //         // Validate the presence of token
    //         if (!$request->has('token')) {
    //             return response()->json(['success' => false, 'message' => 'Invalid token'], 400);
    //         }
    
    //         // Create PayPal client and set API credentials
    //         $provider = new PayPalClient();
    //         $provider->setApiCredentials(config('paypal'));
    //         $paypalToken = $provider->getAccessToken();
    
    //         // Capture payment order
    //         $response = $provider->capturePaymentOrder($request->token);
    
    //         // Check if payment is completed
    //         if ($response['status'] === 'COMPLETED') {
    //             // Check if payment has already been processed
    //             if ($this->isPaymentAlreadyProcessed($response['id'])) {
    //                 return response()->json(['success' => false, 'message' => 'Payment already processed'], 400);
    //             }
    
    //             // Store payment status in database (if needed) and return success response
    //             Payment::where('paypal_order_id', $response['id'])->update([
    //                 'status' => 'COMPLETED',
    //                 'transaction_id' => $response['id'],
    //             ]);
    
    //             return response()->json(['success' => true, 'message' => 'Payment Successful', 'link' => 'https://launcherr.co/paymentSuccess', 'data' => $response], 200);
    //         } else {
    //             Log::error('PayPal Payment Capture Failed', ['response' => $response]);
    //             return response()->json(['success' => false, 'message' => 'Payment Failure', 'link' => 'https://launcherr.co/paymentFailure', 'error' => $response], 500);
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('PayPal Payment Capture Error', ['exception' => $e]);
    //         return response()->json(['success' => false, 'message' => 'Payment Failure', 'link' => 'https://launcherr.co/paymentFailure', 'error' => $e->getMessage()], 500);
    //     }
    // }
    
    // public function cancel()
    // {
    //     return response()->json(['success' => false, 'message' => 'Payment Failure', 'link' => 'https://launcherr.co/paymentFailure'], 500);
    // }
    
    // // New Function: Check if payment is already processed
    // protected function isPaymentAlreadyProcessed($paypalOrderId)
    // {
    //     // Query the database for a payment with this PayPal order ID
    //     $payment = Payment::where('paypal_order_id', $paypalOrderId)->first();
    
    //     // If payment exists and its status is completed, it means it's already processed
    //     if ($payment && $payment->status === 'COMPLETED') {
    //         return true;  // Payment already processed
    //     }
    
    //     return false;  // Payment not processed yet
    // }
    
    public function paypal(Request $request)
    {
        // Use input instead of query for POST requests
        $price = $request->input('price');
        $BookingRef = $request->input('BookingRef');

        if(!$price || !$BookingRef)
        {
            return response()->json(['success'=> 0, 'message'=>'Please provide both Amount and Booking Ref'],400);
        }        
    
        // Create PayPal client and set API credentials
        $provider = new PayPalClient();
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();

        $final = $price/env('USD');

        // return response()->json(Round($final,2));
    
        // Create PayPal order
        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                // Append BookingRef to the success URL
                "return_url" => secure_url(route('success', ['BookingRef' => $BookingRef])),
                "cancel_url" => secure_url(route('cancel')),
            ],
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => Round($final,2),
                    ]
                ]
            ]
        ]);
    
        if (isset($response['id']) && $response['id'] != null) {
            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return response()->json($link['href']);
                }
            }
        } else {
            Log::error('PayPal Order Creation Failed', ['response' => $response]);
            return response()->json(['error' => 'Failed to create PayPal order. Please try again later.'], 500);
        }
    }
    
    public function success(Request $request)
    {
        try {
            $provider = new PayPalClient();
            $provider->setApiCredentials(config('paypal'));
            $paypalToken = $provider->getAccessToken();
    
            $response = $provider->capturePaymentOrder($request->token);
    
            if ($response['status'] === 'COMPLETED') 
            {
                // Get the BookingRef from the request
                $bookingRef = $request->query('BookingRef'); 
    
                return response()->json([
                    'success' => true,
                    'message' => 'Payment Successful',
                    // Append BookingRef to the success URL
                    'link' => 'https://launcherr.co/flightSuccess?BookingRef=' . urlencode($bookingRef),
                    'data' => $response
                ], 200);
            } else {
                Log::error('PayPal Payment Capture Failed', ['response' => $response]);
                return response()->json([
                    'success' => false,
                    'message' => 'Payment Failure',
                    'link' => 'https://launcherr.co/paymentFailure',
                    'error' => $response
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('PayPal Payment Capture Error', ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Payment Failure',
                'link' => 'https://launcherr.co/paymentFailure',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

    public function cancel()
    {
        return response()->json(['success' => false, 'message' => 'Payment Failure', 'link' => 'https://launcherr.co/paymentFailure'], 500); 
    }

    // public function success(Request $request){
    //     $provider= new PayPalClient();
    //     $provider->setApiCredentials(config('paypal'));
    //     $paypalToken=$provider->getAccessToken();
    //     $response=$provider->capturePaymentOrder($request->token);
    //     // return response()->json($response);
    //     if ($response['status'] === 'COMPLETED') {
    //         // Payment was successful
    //         return response()->json(['success' => 'Payment successful', 'details' => $response]);
    //     } else {
    //         // Payment failed or was not completed
    //         Log::error('PayPal Payment Capture Failed', ['response' => $response]);
    
    //         return response()->json(['error' => 'Payment failed. Please contact support or try again.'], 500);
    //     }
    // }

    // public function cancel(){
    //     return response()->json('Error');
    // }

    // protected $client;

    // public function __construct()
    // {
    //     $this->client = new Client();
    // }

//     public function initiatePayment(Request $request)
//     {
//         // $user = Auth::user();
//         // $cartItems = CartDetails::where('user_id', $user->id)->get();

//         // if ($cartItems->isEmpty()) {
//         //     return response()->json(['message' => 'Cart is empty'], 400);
//         // }

//         // $totalAmount = $cartItems->sum('price'); // assuming 'price' is the total price for each item

//         // $gstAmount = $totalAmount * 0.18;
//         // $grand=$totalAmount + $gstAmount;

//         $payload = [
//             "merchantId" => "",
//             "merchantTransactionId" => uniqid('MT'),
//             "merchantUserId" => "MUID123456",
//             "amount" => 10000,
//             "redirectUrl" => "https://webhook.site/redirect-url",
//             "redirectMode" => "POST",
//             "callbackUrl" => "https://webhook.site/callback-url",
//             // url('/api/payment-callback'),
//             "mobileNumber" => "9999999999",
//             // $user->mobile_number, 
//             "paymentInstrument" => [
//                 "type" => "PAY_PAGE"
//             ]        
//         ];


//         $payloadJson = base64_encode(json_encode($payload));

//         // $checksum = hash('sha256', $payloadJson . '/pg/v1/pay' . env('PHONEPE_API_KEY')) . '###' . env('PHONEPE_API_KEY_INDEX');

//         $string = $payloadJson  . '/pg/v1/pay' . '9fa262d3-09d5-4edf-9f93-de27dc8bbcde';

//         $sha265 = hash('sha256',$string);

//         $finalXHeader = $sha265 . '###' . '1';

//         // return response()->json([$payloadJson,$sha265,$finalXHeader]);

//         try {
//             // $response = $this->client->post(env('PHONEPE_BASE_URL').'pg/v1/pay', [
//             //     'body' => $payloadJson,
//             //     'headers' => [
//             //         'Content-Type' => 'application/json',
//             //         'X-VERIFY' => $finalXHeader . '###' . env('PHONEPE_API_KEY_INDEX'),
//             //     ]
//             // ]);

//             $response = Curl::to('https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay')
//             ->withHeader('Content-Type: application/json')
//             ->withHeader('X-VERIFY: ' . $finalXHeader)
//             ->withHeader('MERCHANT-ID: MERCHANTUAT1004') // Assuming 'MERCHANT-ID' is the correct header key
//             ->withData(json_encode(['request' => $payloadJson]))
//             ->post();
        
//             // $client = new \GuzzleHttp\Client();
//             // $response = $client->post('https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay', [
//             //     'headers' => [
//             //         'Content-Type' => 'application/json',
//             //         'X-VERIFY' => $finalXHeader,
//             //         'MERCHANT-ID' => 'MERCHANTUAT1004',
//             //     ],
//             //     'json' => ['request' => $payloadJson],
//             // ]);

// // $responseBody = json_decode($response->getBody(), true);

//             $result = json_decode($response, true);

//             if (isset($result['success']) && $result['success']) {
//                 // Store transaction details in the database if needed
//                 return response()->json([
//                     'message' => 'Payment initiation successful',
//                     'data' => $result['data']
//                 ], 200);
//             } else {
//                 $errorCode = isset($result['error']) && isset($result['error']['code']) ? $result['error']['code'] : 500;
                
//                 return response()->json([
//                     'message' => 'Payment initiation failed',
//                     'error' => $result['error'] ?? 'Unknown error'
//                 ], $errorCode);
//             }

//         } catch (\Exception $e) {
//             return response()->json([
//                 'message' => 'An error occurred while initiating payment',
//                 'error' => $e->getMessage()
//             ], 500);
//         }
//     }


//     public function response(Request $request){
//         $input = $request->all();
//         dd($input);
//     }

//     public function paymentCallback(Request $request)
//     {
//         $data = $request->all();
//         // Process the callback data and verify the payment status

//         // Example:
//         if (isset($data['status']) && $data['status'] == 'SUCCESS') {
//             // Payment successful, update order status, etc.
//             Log::info('Payment successful', $data);
//         } else {
//             // Payment failed
//             Log::error('Payment failed', $data);
//         }

//         return response()->json(['status' => 'callback received']);
//     }

}
