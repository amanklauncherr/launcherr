<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class WebHookRefundController extends Controller
{
    //
    
    public function WebHookRefund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "url" => "nullable|string", 
        ]);     
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
        
        $data = $validator->validated();

        $url = $data['url'];

        try {
            $response = Http::post($url);

            // Get the JSON response and status code
            $result = $response->json();
            $statusCode = $response->status();

            // Check if the response was successful
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook successfully sent.',
                    'data' => $result,
                ], $statusCode);
            } else {
                // Handle non-2xx responses
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send the webhook.',
                    'data' => $result,
                    'status_code' => $statusCode,
                ], $statusCode);
            }
        } catch  (\Exception $e) {
            // Handle exception (e.g. network issues)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }     
    }
}
