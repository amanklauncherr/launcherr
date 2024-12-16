<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PhonePay extends Controller
{
    //
    // public function PhonePay(Request $request)
    // {
    //     // Replace these with your actual PhonePe API credentials
    //     $merchantId = 'M22FICRJ6HDSJ'; // live merchantId
    //     $apiKey = 'b783ea05-3d7b-436b-a176-a052357d7f40'; // live API key
    //     //$redirectUrl = 'https://launcherr.co/paymentSuccess';
    //     $redirectUrl = 'https://shubhangverma.com/success.php';
    //     // Set transaction details
    //     $order_id = uniqid();
    //     $name = "Launcherr Website";
    //     $email = "info@launcherr.co";
    //     $mobile = '9161760876'; // ensure mobile number is a string
    //     $amount = $_GET['amount']; // amount in INR
    //     $description = 'Payment for Product/Service';
    //     $paymentData = array(
    //         'merchantId' => $merchantId,
    //         'merchantTransactionId' => time(), // live transaction ID
    //         "merchantUserId" => "MUID1223",
    //         'amount' => $amount * 100, // amount in paise
    //         'redirectUrl' => $redirectUrl,
    //         'redirectMode' => "POST",
    //         'callbackUrl' => $redirectUrl,
    //         "merchantOrderId" => $order_id,
    //         "mobileNumber" => $mobile,
    //         "message" => $description,
    //         "email" => $email,
    //         "shortName" => $name,
    //         "paymentInstrument" => array(
    //             "type" => "PAY_PAGE",
    //         )
    //     );
    //     $jsonencode = json_encode($paymentData);
    //     $payloadMain = base64_encode($jsonencode);
    //     $salt_index = 1; // key index 1
    //     $payload = $payloadMain . "/pg/v1/pay" . $apiKey;
    //     $sha256 = hash("sha256", $payload);
    //     $final_x_header = $sha256 . '###' . $salt_index;
    //     $request = json_encode(array('request' => $payloadMain));
    //     $curl = curl_init();
    //     curl_setopt_array($curl, [
    //         CURLOPT_URL => "https://api.phonepe.com/apis/hermes/pg/v1/pay",
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => "",
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 30,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => "POST",
    //         CURLOPT_POSTFIELDS => $request,
    //         CURLOPT_HTTPHEADER => [
    //             "Content-Type: application/json",
    //             "X-VERIFY: " . $final_x_header,
    //             "accept: application/json"
    //         ],
    //     ]);
    //     $response = curl_exec($curl);
    //     $err = curl_error($curl);
    //     curl_close($curl);
    //     if ($err) {
    //         echo "cURL Error #:" . $err;
    //     } else {
    //         $res = json_decode($response);
    //         print_r($res); // Print response for debugging
    //         if (isset($res->success) && $res->success == '1') {
    //             $paymentCode = $res->code;
    //             $paymentMsg = $res->message;
    //             $payUrl = $res->data->instrumentResponse->redirectInfo->url;
    //             header('Location:' . $payUrl);
    //             exit(); // Ensure the script stops after redirect
    //         } else {
    //             // Handle the error case
    //             echo "Error in payment initialization: " . $res->message;
    //         }
    //     }
    // }

    public function PhonePay(Request $request)
    {

        // flightSuccess?BookingRef=TBB7V78R

        $request->validate([
            'BookingRef' => 'required|string',
            'amount' => 'required|numeric',
        ]);

    // Your PhonePe API integration code here
    $merchantId = 'M22FICRJ6HDSJ'; // live merchantId
    $apiKey = 'b783ea05-3d7b-436b-a176-a052357d7f40'; // live API key

    $bookingRef = $request->BookingRef;

    $redirectUrl = 'https://shubhangverma.com/success.php?BookingRef=' . urlencode($bookingRef);
    
    $order_id = uniqid();
    $name = "Launcherr Website";
    $email = "info@launcherr.co";
    $mobile = '9161760876'; 
    $amount = $request->amount; // Get the amount from form data
    $description = 'Payment for Product/Service';

    $paymentData = array(
        'merchantId' => $merchantId,
        'merchantTransactionId' => time(), 
        'merchantUserId' => 'MUID1223',
        'amount' => $amount * 100, 
        'redirectUrl' => $redirectUrl,
        'redirectMode' => 'POST',
        'callbackUrl' => $redirectUrl,
        'merchantOrderId' => $order_id,
        'mobileNumber' => $mobile,
        'message' => $description,
        'email' => $email,
        'shortName' => $name,
        'paymentInstrument' => array(
            'type' => 'PAY_PAGE',
        )
    );

    $jsonencode = json_encode($paymentData);
    $payloadMain = base64_encode($jsonencode);
    $salt_index = 1;
    $payload = $payloadMain . "/pg/v1/pay" . $apiKey;
    $sha256 = hash("sha256", $payload);
    $final_x_header = $sha256 . '###' . $salt_index;

    $request = json_encode(array('request' => $payloadMain));
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.phonepe.com/apis/hermes/pg/v1/pay',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $request,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-VERIFY: ' . $final_x_header,
            'accept: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return response()->json(['error' => 'Payment Error: ' . $err], 500);
    }

    $res = json_decode($response);

    if (isset($res->success) && $res->success == '1') {
        $payUrl = $res->data->instrumentResponse->redirectInfo->url;
        return redirect($payUrl);  // Redirect to the payment URL

    } else {
        return response()->json(['error' => 'Error in payment initialization: ' . $res->message], 500);
    }
}

}

