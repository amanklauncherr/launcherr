<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\UserFlightBooking;
use App\Mail\UserFlightTicketCancel;
use App\Models\AirlineCode;
use App\Models\iatacode;
use App\Models\TravelHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

use DateTime;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Constraint\IsFalse;

class DotMikController extends Controller
{
    /**
     * Search Flights
     * 
     * This endpoint allows users to search for flights based on their travel preferences, including one-way, round-trip, or multi-state travel.
     * 
     * @group Flights
     * 
     * @bodyParam TYPE string required The type of flight. Must be one of: `ONEWAY`, `ROUNDTRIP`, `MULTISTATE`. Example: ONEWAY
     * @bodyParam tripInfo array required An array of trip details.
     * @bodyParam tripInfo.*.origin string required The origin airport code. Example: DEL
     * @bodyParam tripInfo.*.destination string required The destination airport code. Example: BOM
     * @bodyParam tripInfo.*.travelDate date required The travel date in `m/d/Y` format. Example: 12/15/2024
     * @bodyParam tripInfo.*.tripId string The trip ID. Example: 0
     * @bodyParam travelType string required The travel type. `0` for domestic, `1` for international. Example: 1
     * @bodyParam adultCount string required The number of adults traveling. Example: 2
     * @bodyParam childCount string required The number of children traveling. Example: 1
     * @bodyParam infantCount string required The number of infants traveling. Example: 0
     * @bodyParam classOfTravel string required The class of travel. Must be one of: `0`, `1`, `2`, `3` (e.g., economy, business). Example: 1
     * @bodyParam airlineCode string Optional airline code for filtering flights. Example: AI
     * @bodyParam Arrival string Optional arrival time filter. One of: `12AM6AM`, `6AM12PM`, `12PM6PM`, `6PM12AM`. Example: 12AM6AM
     * @bodyParam Departure string Optional departure time filter. Example: 6AM12PM
     * @bodyParam Refundable boolean Optional filter for refundable flights. Example: true
     * @bodyParam Stops string Optional filter for the number of stops. One of: `0`, `1`, `2`. Example: 1
     * @bodyParam Price integer Optional filter for maximum price. Example: 5000
     * @bodyParam headersToken string required The secret token for the request header. Example: abc123
     * @bodyParam headersKey string required The secret key for the request header. Example: xyz456
     * 
     * 
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "The TYPE field is required."
     * }
    */
    public function SearchFlight(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "TYPE" => "required|string|in:ONEWAY,ROUNDTRIP,MULTISTATE",
            "tripInfo" => "required|array",
            "tripInfo.*.origin" => "required|string",
            "tripInfo.*.destination" => "required|string",
            "tripInfo.*.travelDate" => "required|date_format:m/d/Y",
            "tripInfo.*.tripId" => "nullable|string|in:0,1", 
            "travelType" => "required|string|in:0,1", // 0 for domestic, 1 for international
            // "bookingType" => "required|string|in:0,1,2,3", // 0 for one way, 1 for round trip
            "adultCount" => "required|string",
            "childCount" => "required|string", 
            "infantCount" => "required|string",
            "classOfTravel" => "required|in:0,1,2,3",         
            "airlineCode" => "nullable|string",
            "Arrival" => "nullable|string",
            "Departure" => "nullable|string",
            "Refundable" => "nullable|boolean",    
            "Stops" => "nullable|string|in:0,1,2",
            "Price" => "nullable|integer",
            "headersToken" => "required|string", 
            "headersKey" => "required|string",
        ]);
        
        // Check if validation fails
        if ($validator->fails()) {
            // Get all the error messages
            $errors = $validator->errors()->all(); // Capture all error messages
            return response()->json([
                'success' => false,
                'message' => $errors[0], // Return the first error message
            ], 422);
        }
        
        // If validation succeeds, continue with the validated data
        $data = $validator->validated();

        $tripInfo=[];

        $i=0;

        foreach ($data['tripInfo'] as $trip) {
            if (empty($trip['tripId'])) {
                $trip['tripId'] = "{$i}"; // Set default value
            }
            $tripInfo[]=$trip;
            $i++;
        }   

        $count=count($data['tripInfo']);

        if($data['TYPE'] === "ONEWAY")
        {
            $bookingType = "0";     
            if($count !=1)
            {
                return response()->json(['success'=>false,'message'=>'tripInfo should have 1 objects for One Way'],400);
            }
            $message = 'One Way Trip Flight Data';
        }
        else if($data['TYPE'] === "ROUNDTRIP")
        {
            $bookingType = "2";
            if($count != 2)
            {
                return response()->json(['success'=>false,'message'=>'tripInfo should have 2 objects for Round Trip'],400);
            }
            else if($data['tripInfo'][0]['origin'] != $data['tripInfo'][1]['destination'])
            {
                return response()->json(['success'=>false,'message'=>'Origin in first Object and Destination in second object should be same for ROUNDTRIP'],400);
            }
            else if($data['tripInfo'][0]['destination'] != $data['tripInfo'][1]['origin'])
            {
                return response()->json(['success'=>false,'message'=>'Destination in first Object and Origin in second object should be same for ROUNDTRIP'],400);
            }

            $message = 'Round Trip Flight Data';
        }
        else if($data['TYPE'] === "MULTISTATE")
        {
            $bookingType = "3";
            if($count < 2)
            {
                return response()->json(['success'=>false,'message'=>'tripInfo should have 2 or More objects'],400);
            }
            $message = 'Multi State Flight Data';
        }

        $data['tripInfo']=$tripInfo;
        
        
        $payload = [
            "deviceInfo" => [
                "ip" => "122.161.52.233",
                "imeiNumber" => "12384659878976879887"
            ],
            "travelType" => $data['travelType'],
            "bookingType" => $bookingType, 
            "tripInfo" => $data['tripInfo'],
            "adultCount" => $data['adultCount'],
            "childCount" => $data['childCount'],
            "infantCount" => $data['infantCount'],
            "classOfTravel" => $data['classOfTravel'],
            "filteredAirLine" => [
                "airlineCode" => ''
            ]
        ];

        // return response()->json($payload);

        $headers = [
            'D-SECRET-TOKEN' => $data['headersToken'],
            'D-SECRET-KEY' => $data['headersKey'],
            'CROP-CODE' => 'DOTMIK160614',
            'Content-Type' => 'application/json',
        ];

        $url = 'https://api.dotmik.in/api/flightBooking/v1/searchFlight';

        try 
        {
        $response = Http::withHeaders($headers)->timeout(60)->post($url, $payload);
        $result=$response->json();
        $statusCode = $response->status();

        // return response()->json($result);

        if($result['status'] === false)
        {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result
            ],$statusCode);   
        }
        else
        {   
            if ($response->successful()) 
            {
                $dataa = $result['payloads']['data']['tripDetails'];
                $AC = $result['payloads']['data']['tripDetails'][0]['Flights'];
                $Flights = $dataa[0]['Flights'];

                $transformedTripDetails = [];
                foreach ($dataa as $trip) 
                {
                    $trip['Flights'] = array_reduce($trip['Flights'], function ($carry,$flight) 
                    {
                        if (count($flight['Fares']) > 1) 
                        {
                            foreach ($flight['Fares'] as $fare) 
                            {
                                $clonedFlight = $flight; // Clone flight details
                                $clonedFlight['Fares'] = [$fare]; // Assign only this fare
                                $carry[] = $clonedFlight; // Add to the result array
                            }
                        } else
                        {
                         // If there's only one fare, return the flight as it is
                         $carry[] = $flight;
                        }
                       return $carry;
                    }, []);
                    $transformedTripDetails[] = $trip;
                }

                $Flights=$transformedTripDetails[0]['Flights'];

                $minTotalAmount = PHP_INT_MAX;
                
                $maxTotalAmount = PHP_INT_MIN;

                foreach($Flights as $Flight)
                {
                    $amount=$Flight['Fares'][0]['FareDetails'][0]['Total_Amount'];

                    if ($amount < $minTotalAmount) {
                        $minTotalAmount = $amount;
                    }
                    if ($amount > $maxTotalAmount) {
                        $maxTotalAmount = $amount;
                    }
                }

                if(isset($data['Stops']))
                {
                    $Filtered=[];
                    if($data['Stops'] === "0")
                    {
                        if($data['TYPE'] === 'ONEWAY')
                        {
                            foreach ($Flights as $filteration) {              
                                if($filteration['Segments'][0]['Origin'] === $data['tripInfo'][0]['origin'] && $filteration['Segments'][0]['Destination'] === $data['tripInfo'][0]['destination'])
                                {
                                    $Filtered[]=$filteration;
                                }
                            }
                            $Flights=$Filtered;
                        }
                        else if($data['TYPE'] === 'ROUNDTRIP')
                        {
                            foreach ($Flights as $filteration) {              
                                if($filteration['Segments'][0]['Origin'] === $data['tripInfo'][0]['origin'] && $filteration['Segments'][0]['Destination'] === $data['tripInfo'][0]['destination'] && $filteration['Segments'][1]['Origin'] === $data['tripInfo'][1]['origin'] && $filteration['Segments'][1]['Destination'] === $data['tripInfo'][1]['destination'])
                                {
                                    $Filtered[]=$filteration;
                                }
                            }
                            $Flights=$Filtered;
                        }
                        else if($data['TYPE'] === 'MULTISTATE')
                        {
                            foreach ($Flights as $filteration) {              
                                if($filteration['Segments'][0]['Origin'] === $data['tripInfo'][0]['origin'] && $filteration['Segments'][0]['Destination'] === $data['tripInfo'][0]['destination'] && $filteration['Segments'][1]['Origin'] === $data['tripInfo'][1]['origin'] && $filteration['Segments'][1]['Destination'] === $data['tripInfo'][1]['destination'] && $filteration['Segments'][2]['Origin'] === $data['tripInfo'][2]['origin'] && $filteration['Segments'][2]['Destination'] === $data['tripInfo'][2]['destination'])
                                {
                                    $Filtered[]=$filteration;
                                }
                            }
                            $Flights=$Filtered;
                        }
                    }
                    else if($data['Stops'] === "1")
                    {
                        if($data['TYPE'] === 'ONEWAY')
                        {
                            foreach ($Flights as $filteration) 
                            {        
                                if(count($filteration['Segments']) > 1)
                                {
                                    if($filteration['Segments'][0]['Origin'] === $data['tripInfo'][0]['origin'] && $filteration['Segments'][0]['Destination'] === $data['tripInfo'][0]['destination'] || $filteration['Segments'][1]['Origin'] === $data['tripInfo'][1]['origin'] || $filteration['Segments'][1]['Destination'] === $data['tripInfo'][1]['destination'])
                                    {
                                        $Filtered[]=$filteration;
                                    }
                                }        
                            }
                            $Flights=$Filtered; 
                        }
                        else if($data['TYPE'] === 'ROUNDTRIP')
                        {
                            foreach ($Flights as $filteration) {              
                                if(
                                    (
                                        $filteration['Segments'][0]['Origin'] === $data['tripInfo'][0]['origin'] && $filteration['Segments'][1]['Destination'] === $data['tripInfo'][0]['destination']
                                    ) 
                                    || 
                                    (
                                        $filteration['Segments'][1]['Origin'] === $data['tripInfo'][2]['origin'] &&
                                        $filteration['Segments'][2]['Destination'] === $data['tripInfo'][1]['destination'])
                                    )

                                {
                                    $Filtered[]=$filteration;
                                }
                            }
                            $Flights=$Filtered;
                        }
                        else if($data['TYPE'] === 'MULTISTATE')
                        {
                            foreach ($Flights as $filteration) {              
                                if
                                (
                                    (
                                        $filteration['Segments'][0]['Origin'] === $data['tripInfo'][0]['origin'] && $filteration['Segments'][1]['Destination'] === $data['tripInfo'][0]['destination']
                                    ) 
                                    || 
                                    (
                                        $filteration['Segments'][1]['Origin'] === $data['tripInfo'][1]['origin'] &&
                                        $filteration['Segments'][2]['Destination'] === $data['tripInfo'][1]['destination']
                                    )
                                    ||
                                    (
                                        $filteration['Segments'][2]['Origin'] === $data['tripInfo'][2]['origin'] && $filteration['Segments'][3]['Destination'] === $data['tripInfo'][2]['destination']
                                    ) 
                                )
                                {
                                    $Filtered[]=$filteration;
                                }
                            }
                            $Flights=$Filtered;
                        }
                    }
                }
                if (isset($data['Arrival'])) 
                {        
                    $Filtered=[];

                    if($data['Arrival'] === '12AM6AM')
                    {
                        $arrivalDateTimeLow = "00:00";
                        // return response()->json($arrivalDateTimeLow, 400);
                        $arrivalDateTimeHigh = "06:00";
                    }
                    else if($data['Arrival'] === '6AM12PM')
                    {
                        
                        $arrivalDateTimeLow = "06:00";
                        // return response()->json($arrivalDateTimeLow, 400);
                        $arrivalDateTimeHigh = "12:00";
                    }
                    else if($data['Arrival'] === '12PM6PM')
                    {   
                        $arrivalDateTimeLow = "12:00";
                        // return response()->json($arrivalDateTimeLow, 400);
                        $arrivalDateTimeHigh = "18:00";
                    }
                    else if($data['Arrival'] === '6PM12AM')
                    {
                        $arrivalDateTimeLow = "18:00";
                        // return response()->json($arrivalDateTimeLow, 400);
                        $arrivalDateTimeHigh = "24:00";
                    }
                    else{
                        return response()->json('Invalid Arrival time or condition.', 400);
                    }

                    foreach ($Flights as $filteration) {                
                        // $departure= array_filter();
                        foreach($filteration['Segments'] as $Segment)
                        {
                            if($data['tripInfo'][0]['destination'] === $Segment['Destination'])
                            {
                                $datetime = $Segment['Arrival_DateTime'];
                                $dateObj = new DateTime($datetime);
                                $time = $dateObj->format('H:i');
                                // return response()->json($time);
                                if($time >  $arrivalDateTimeLow && $time < $arrivalDateTimeHigh)
                                {
                                    $Filtered[]=$filteration;
                                }
                            }
                        }
                    }
                    $Flights=$Filtered;                    
                }
                
                if (isset($data['Departure'])) 
                {        

                    $Filtered=[];

                    if($data['Departure'] === '12AM6AM')
                    {
                        $departureDateTimeLow = "00:00";
                        $departureDateTimeHigh = "06:00";
                    }
                    else if($data['Departure'] === '6AM12PM')
                    {                        
                        $departureDateTimeLow = "06:00";
                        $departureDateTimeHigh = "12:00";
                    }
                    else if($data['Departure'] === '12PM6PM')
                    {   
                        $departureDateTimeLow = "12:00";
                        $departureDateTimeHigh = "18:00";
                    }
                    else if($data['Departure'] === '6PM12AM')
                    {
                        $departureDateTimeLow = "18:00";
                        $departureDateTimeHigh = "24:00";
                    }
                    else{
                        return response()->json('Invalid Arrival time or condition.', 400);
                    }
                    
                    foreach ($Flights as $filteration) {                
                        foreach($filteration['Segments'] as $Segment)
                        {
                            if($data['tripInfo'][0]['origin'] === $Segment['Origin'])
                            {
                                $datetime = $Segment['Departure_DateTime'];
                                $dateObj = new DateTime($datetime);
                                $time = $dateObj->format('H:i');
                                if($time >  $departureDateTimeLow && $time < $departureDateTimeHigh)
                                {
                                    $Filtered[]=$filteration;
                                }
                            }
                        }
                     }
                      $Flights=$Filtered;
                }

                if(isset($data['Refundable']))
                {       
                    $refundable = $data['Refundable'];
                    $filtered = array_filter($Flights, function($flight) use($refundable) {
                        return $flight['Fares'][0]['Refundable'] === $refundable ;
                    });
                    $Flights=array_values($filtered);
                }

                if(isset($data['Price']))
                {
                    $Filtered=[];
                    foreach($Flights as $Flight)
                    {
                        $amount=$Flight['Fares'][0]['FareDetails'][0]['Total_Amount'];
                        if($amount <= $data['Price'])
                        {
                            $Filtered[] = $Flight;
                        }
                    }
                    $Flights=$Filtered;
                }

                if(isset($data['airlineCode']))
                {         
                    $airlineCode = $data['airlineCode'];
                    $filtered = array_filter($Flights, function($flight) use($airlineCode) {
                        return $flight['Airline_Code'] === $airlineCode;
                    });
                    $Flights=array_values($filtered);
                }
                
                // Extract distinct airline codes
                $airlineCodes = array_map(function($flight) {
                    return $flight['Airline_Code'] ?? null; // Handle cases where 'Airline_Code' may not exist
                }, $AC);

                $distinctAirlineCodes = array_values(array_unique(array_filter($airlineCodes)));

                // foreach($distinctAirlineCodes as $DAC)
                // {
                //     $airport = AirlineCode::where('carrier_code', $DAC)->get();

                //     $ACName[]=
                //     [
                //         'AirlineCode' => $airport[0]['carrier_code'],
                //         'AirlineName' => $airport[0]['airline_name'],
                //         'AirlineLogo' => $airport[0]['logo']
                //     ];
                // }
                
                $airlines = AirlineCode::whereIn('carrier_code', $distinctAirlineCodes)
                ->get(['carrier_code', 'airline_name', 'logo'])
                ->keyBy('carrier_code'); 

                $ACName = array_map(function($code) use ($airlines) {
                    if (isset($airlines[$code])) {
                        return [
                            'AirlineCode' => $airlines[$code]->carrier_code,
                            'AirlineName' => $airlines[$code]->airline_name,
                            'AirlineLogo' => $airlines[$code]->logo
                        ];
                    }
                    return null; // Handle cases where airline code is not found
                }, $distinctAirlineCodes);

                $ACName = array_values(array_filter($ACName));

                $count = count($Flights);

                $payloads = [
                        'errors' => [],
                        'data' => [
                            'tripDetails' => [
                                [
                                    'Flights' => $Flights
                                ]
                            ]
                        ]
                ];

                if($count === 0)
                {
                    return response()->json([
                        'success' => false,
                        'message' => "No Flights Found",
                    ],$statusCode);
                }
                return response()->json([
                    'status' => true,
                    'message' => $message,
                    'count' => $count,
                    'minPrice' => $minTotalAmount,
                    'maxPrice' => $maxTotalAmount,
                    'status_code' => $result['status_code'],
                    'request_id' => $result['request_id'],
                    'SearchKey' => $result['payloads']['data']['searchKey'],
                    'AirlineCodes' => $ACName,
                    'payloads' => $payloads,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result
                ],$statusCode);
            }
        }
        } catch (\Exception $e) {
            // Handle exception (e.g. network issues)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }

    }
    
    public function fareRule(Request $request)
    {
            $validator = Validator::make($request->all(),[
                'SearchKey' => 'required|string',
                'FareID' => 'required|string',
                'FlightKey' => 'required|string',
                'headersToken' => 'required|string',
                'headersKey' => 'required|string'
            ]);     

            if ($validator->fails()) {
                $errors = $validator->errors()->all(); // Get all error messages
                $formattedErrors = [];
        
                foreach ($errors as $error) {
                    $formattedErrors[] = $error;
                }
        
                return response()->json([
                    'success' => false,
                    'message' => $formattedErrors[0]
                ], 422);
            }
            
            $data=$validator->validated();

            $payload = [
                "deviceInfo" => [
                    "ip" => "122.161.52.233",
                    "imeiNumber" => "12384659878976879888"
                ],
                "searchKey" => $data['SearchKey'],
                "fareId" => $data['FareID'],
                "flightKey" => $data['FlightKey']            
            ];

            // Headers
            $headers = [
                'D-SECRET-TOKEN' => $data['headersToken'],
                'D-SECRET-KEY' => $data['headersKey'],
                'CROP-CODE' => 'DOTMIK160614',
                'Content-Type' => 'application/json',
            ];

            // API URL
            $url = 'https://api.dotmik.in/api/flightBooking/v1/fareRule';

            try {
                // Make the POST request using Laravel HTTP Client
                $response = Http::withHeaders($headers)->post($url, $payload);
                $result = $response->json();            
                $statusCode = $response->status();
                if($result['status'] === false)
                {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'error' => $result
                    ],$statusCode);
                }
                else{
                    if($response->successful())
                    {
                        return response()->json([
                            'success' => true,
                            'data' => $result,
                        ], 200);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => $result['message'],
                            'error' => $result
                        ],$statusCode);
                    }
                }
                
                //code...
            } catch  (\Exception $e) {
                // Handle exception (e.g. network issues)
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
    }

   public function RePrice(Request $request)
   {
        $validator = Validator::make($request->all(),[
            'SearchKey' => 'required|string',
            'FareID' => 'required|string',
            'FlightKey' => 'required|string',
            'CustomerContact' => "required|string|min:10|max:10",
            'headersToken' => 'required|string',
            'headersKey' => 'required|string',
            'adultCount' => 'required|string',
            'childCount' => 'required|string',
            'infantCount' => 'required|string'
        ]);     

        if ($validator->fails()) {
            $errors = $validator->errors()->all(); // Get all error messages
            $formattedErrors = [];
    
            foreach ($errors as $error) {
                $formattedErrors[] = $error;
            }
    
            return response()->json([
                'success' => false,
                'message' => $formattedErrors[0]
            ], 422);
        }
        
        $data=$validator->validated();

        $payload = [
            "deviceInfo" => [
                "ip" => "122.161.52.233",
                "imeiNumber" => "12384659878976879888"
            ],
            "searchKey" => $data['SearchKey'],
            "reprice" => [
                [
                "Fare_Id" => $data['FareID'],
                "Flight_Key" => $data['FlightKey'],    
                ]
            ],
            "customerMobile" => $data['CustomerContact'],
            "GSTIN" => ""
        ];

        // Headers
        $headers = [
            'D-SECRET-TOKEN' => $data['headersToken'],
            'D-SECRET-KEY' => $data['headersKey'],
            'CROP-CODE' => 'DOTMIK160614',
            'Content-Type' => 'application/json',
        ];

        // API URL
        $url = 'https://api.dotmik.in/api/flightBooking/v1/rePrice';

        try {
            // Make the POST request using Laravel HTTP Client
            $response = Http::withHeaders($headers)->post($url, $payload);     
            $result=$response->json();
            $statusCode = $response->status();

            if($result['status'] === false)
            {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result
                ],$statusCode);   
            }
            else{
                if($response->successful())
                {
                    
                    $Segments=$result['payloads']['data']['rePrice'][0]['Flight']['Segments'];

                    $BookingType = 'Domestic'; // Default to 'Domestic' initially

                    foreach ($Segments as $Segment) {
                        $Origin = iatacode::where('iata_code', $Segment['Origin'])->first();
                        $Destination = iatacode::where('iata_code', $Segment['Destination'])->first();

                        if ($Origin && $Destination) { // Ensure both are found
                            if ($Origin->country !== 'India' || $Destination->country !== 'India') {
                                $BookingType = 'International';
                                break; // No need to continue if it's international
                            }
                        }
                    }

                    // return response()->json($result);

                    $PAX= $result['payloads']['data']['rePrice'][0]['Flight']['Fares'][0]['FareDetails'];

                    // return response()->json($PAX);

                    $adultAmount = 0;
                    $adultservice = 0;
                    $adultAirportFee=0;
                    $childAmount = 0;
                    $childservice = 0;
                    $childAirportFee=0;
                    $infantAmount = 0;
                    $infantservice = 0;
                    $infantAirportFee=0;

                    foreach($PAX as $pax) {
                        $total = $pax['Total_Amount'] + $pax['Trade_Markup_Amount'];

                        switch ($pax['PAX_Type']) {
                            case 0: // Adult
                                $adultAmount += $total * $data['adultCount'];
                                $adultservice += $pax['Service_Fee_Amount'] * $data['adultCount'];
                                $adultAirportFee += $pax['AirportTax_Amount']  * $data['adultCount'];
                                break;
                            case 1: // Child
                                $childAmount += $total * $data['childCount'];
                                $childservice += $pax['Service_Fee_Amount'] * $data['adultCount'];
                                $childAirportFee += $pax['AirportTax_Amount']  * $data['childCount'];
                                break;
                            case 2: // Infant
                                $infantAmount += $total * $data['infantCount'];
                                $infantservice += $pax['Service_Fee_Amount'] * $data['adultCount'];
                                $infantAirportFee += $pax['AirportTax_Amount']  * $data['infantCount'];
                                break;
                        }
                    }

                    $TotalAmount = $adultAmount + $childAmount + $infantAmount;

                    $LauncherAmount = null;
                    if( $BookingType === 'Domestic')
                    {
                        $LauncherAmount = ($TotalAmount * (20/100)) + $TotalAmount;
                    }
                    else if( $BookingType === 'International')
                    {
                        if($TotalAmount <= 20000)
                        {
                            $LauncherAmount = ($TotalAmount * (20/100)) + $TotalAmount;
                        }
                        elseif($TotalAmount >20000)
                        {
                            $LauncherAmount = ($TotalAmount * (25/100)) + $TotalAmount;   
                        }
                    }

                    $Totalservice = $adultservice + $childservice + $infantservice;

                    $TotalAirportFee = $adultAirportFee + $childAirportFee + $infantAirportFee;

                    return response()->json([
                        'success' => true,
                        'adultAmount' => $adultAmount,
                        'childAmount' => $childAmount,
                        'infantAmount' => $infantAmount,
                        'totalAmount'=> $TotalAmount,
                        'servicefee' => $Totalservice,
                        'airportTaxes' => $TotalAirportFee,
                        'launcherAmount' => ceil($LauncherAmount),
                        'data' => $result,
                    ], $statusCode);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'error' => $result
                    ],$statusCode);
                }
            }
            //code...
        } catch  (\Exception $e) {
            // Handle exception (e.g. network issues)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
   }

    public function TemporaryBooking(Request $request)
    {
        // Validation
      $validator = Validator::make($request->all(),[
            'totalCount' => 'required|string',
            'mobile' => 'required|string|max:10|min:10',
            'whatsApp' => 'required|string|max:10|min:10',
            'email' => 'required|string|email',
            'passenger_details.*.paxType' => 'required|integer|in:0,1,2', // 0-ADT/1-CHD/2-INF
            'passenger_details.*.title' => 'required|string|in:Mr,Mrs,Ms,Mstr,Miss', // MR, MRS, MS; MSTR, MISS for child/infant
            'passenger_details.*.firstName' => 'required|string',
            'passenger_details.*.lastName' => 'required|string',
            'passenger_details.*.age' => 'nullable|integer',
            'passenger_details.*.gender' => 'required|integer|in:0,1',  // 0-Male, 1-Female
            'passenger_details.*.dob' => 'required|date',
            'passenger_details.*.passportNumber' => 'nullable|string',
            'passenger_details.*.passportIssuingAuthority' => 'nullable|string',
            'passenger_details.*.passportExpire' => 'nullable|date',
            'passenger_details.*.nationality' => 'nullable|string',
            'passenger_details.*.pancardNumber' => 'nullable|string',
            'passenger_details.*.frequentFlyerDetails' => 'nullable|string',
            'gst.isGst' => 'required|string',
            'gst.gstNumber' => 'nullable|string',
            'gst.gstName' => 'nullable|string',
            'gst.gstAddress' => 'nullable|string',
            'searchKey' => 'required|string',
            'FlightKey' => 'required|string',
            'headersToken' => 'required|string',
            'headersKey' => 'required|string'
        ]);     

        if ($validator->fails()) {
            $errors = $validator->errors()->all(); // Get all error messages
            $formattedErrors = [];
    
            foreach ($errors as $error) {
                $formattedErrors[] = $error;
            }
    
            return response()->json([
                'success' => false,
                'message' => $formattedErrors[0]
            ], 422);
        }
        
        $data=$validator->validated();

        // Initialize paxDetails array
        $paxDetails = [];

        $padID=1;

        for ($i = 0; $i < $data['totalCount']; $i++) {
            $paxDetails[] = [
                "paxId" => $padID++,
                "paxType" => $data['passenger_details'][$i]['paxType'],
                "title" => $data['passenger_details'][$i]['title'],
                "firstName" => $data['passenger_details'][$i]['firstName'],
                "lastName" => $data['passenger_details'][$i]['lastName'],
                "gender" => $data['passenger_details'][$i]['gender'],
                "age" => $data['passenger_details'][$i]['age'] ?? null,
                // "dob" => $data['passenger_details'][$i]['dob'],
                "dob" => (new DateTime($data['passenger_details'][$i]['dob']))->format('m/d/Y'),
                "passportNumber" => $data['passenger_details'][$i]['passportNumber'] ?? null,
                "passportIssuingAuthority" => $data['passenger_details'][$i]['passportIssuingAuthority'] ?? null,
                "passportExpire" => (new DateTime($data['passenger_details'][$i]['passportExpire']))->format('m/d/Y') ?? null,
                "nationality" => $data['passenger_details'][$i]['nationality'] ?? null,
                "pancardNumber" => $data['passenger_details'][$i]['pancardNumber'] ?? null,
                "frequentFlyerDetails" => $data['passenger_details'][$i]['frequentFlyerDetails'] ?? null,
            ];
        }

        // return response()->json($paxDetails);

        // $passportNumbers = array_column($paxDetails, 'passportNumber');

        // if (count($passportNumbers) !== count(array_unique($passportNumbers))) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => "Passport numbers must be unique within the passenger details.",
        //     ], 400);
        // }
        
        // Payload creation
        $payload = [
        "deviceInfo" => [
            "ip" => "122.161.52.233",
            "imeiNumber" => "12384659878976879887"
        ],
        "passengers" => [
            "mobile" => $data['mobile'],
            "whatsApp" => $data['whatsApp'],
            "email" => $data['email'],
            "paxDetails" => $paxDetails
        ],
        "gst" => [
            "isGst" => $data['gst']['isGst'],
            "gstNumber" => $data['gst']['gstNumber'] ?? null,
            "gstName" => $data['gst']['gstName'] ?? null,
            "gstAddress" => $data['gst']['gstAddress'] ?? null
        ],
        "flightDetails" => [
            [
                "searchKey" => $data['searchKey'],
                "flightKey" => $data['FlightKey'],
                "ssrDetails" => [] // Empty SSR details
            ]
        ],
        "costCenterId" => 0,
        "projectId" => 0,
        "bookingRemark" => "Test booking with PAX details",
        "corporateStatus" => 0,
        "corporatePaymentMode" => 0,
        "missedSavingReason" => null,
        "corpTripType" => null,
        "corpTripSubType" => null,
        "tripRequestId" => null,
        "bookingAlertIds" => null
        ];

        // return response()->json($payload);

        // Headers
        $headers = [
            'D-SECRET-TOKEN' => $data['headersToken'],
            'D-SECRET-KEY' => $data['headersKey'],
            'CROP-CODE' => 'DOTMIK160614',
            'Content-Type' => 'application/json',
        ];
        
        // API URL
        $url = 'https://api.dotmik.in/api/flightBooking/v1/tempBooking';
        
        try {
            // Make POST request
            $response = Http::withHeaders($headers)->post($url, $payload);
            $result = $response->json();
            $statusCode = $response->status();
            
            if ($result['status'] === false) {
                return response()->json([
                    'success' => false,
                    'message' => $result['payloads']['data']['tempBooking']['innerException'],
                    'error' => $result
                ],$statusCode);
            } else {
                if ($response->successful()) {
                    TravelHistory::create([
                        'user_id' => Auth::guard('api')->id(),
                        'BookingType' => 'FLIGHT',
                        'BookingRef' => $result['payloads']['data']['bookingRef'],
                        'Status' => 'TEMPBOOKED'
                    ]);
                    return response()->json([
                        'success' => true,
                        'data' => $result,
                    ], $statusCode);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $result['payloads']['data']['tempBooking']['innerException'],
                        'error' => $result
                    ],$statusCode);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }        
    }

    public function CheckWallet(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'BookingRef' => 'required|string',
            "UserRef" => "required|string",
            'headersToken' => 'required|string',
            'headersKey' => 'required|string'
        ]);     

        if ($validator->fails()) {
            $errors = $validator->errors()->all(); // Get all error messages
            $formattedErrors = [];

            foreach ($errors as $error) {
                $formattedErrors[] = $error;
            }

            return response()->json([
                'success' => false,
                'message' => $formattedErrors[0]
            ], 422);
        }
        
        $data=$validator->validated();

        $payload = [
            "deviceInfo" => [
                "ip" => "122.161.52.233",
                "imeiNumber" => "12384659878976879887"
            ],
            "bookingRef" => $data['BookingRef'],
            "userRef" => $data['UserRef']
        ];
        
        // Headers
        $headers = [
            'D-SECRET-TOKEN' => $data['headersToken'],
            'D-SECRET-KEY' => $data['headersKey'],
            'CROP-CODE' => 'DOTMIK160614',
            'Content-Type' => 'application/json',
        ];

        // API URL
        $url = 'https://api.dotmik.in/api/flightBooking/v1/checkWallet';
        try {
            // Make the POST request using Laravel HTTP Client
            $response = Http::withHeaders($headers)->post($url, $payload);
            $result=$response->json();
            $statusCode = $response->status();

            if($result['status'] === false)
            {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result
                ],$statusCode);
            }
            else{
                if($response->successful())
                {
                    return response()->json([
                        'success' => true,
                        'data' => $result,
                    ], $statusCode);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'error' => $result
                    ],$statusCode);
                }
            }
            //code...
        } catch  (\Exception $e) {
            // Handle exception (e.g. network issues)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }   
    }

    public function generateTicketPdf($Cabin,$CheckIn,$Contact, $Email, $BaseFare, $TotalAmount, $CancelArray, $RescheduleChargesArray, $Tax, $paxDetails,$Segment,$flight_type)
    {

        $htmlCode = "<!DOCTYPE html>
            <html lang='en'>
            <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Flight Ticket</title>
            <link href='https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap' rel='stylesheet'>
            <style>
                body {
                    font-family: 'Roboto', sans-serif;
                    background-color: #f0f2f5;
                    margin: 0;
                    padding: 20px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                }
                .container {
                    max-width: 800px;
                    background-color: #ffffff;
                    border-radius: 10px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                    padding: 20px;
                }
                h2, h3 {
                    text-align: center;
                    color: #333;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                table, th, td {
                    border: 1px solid #e0e0e0;
                    padding: 15px;
                    text-align: left;
                }
                th {
                    background-color: #f7f7f7;
                    color: #555;
                    font-weight: bold;
                }
                td {
                    color: #333;
                }
                .info-section {
                    margin-bottom: 20px;
                }
                .fare-rules h4 {
                    margin-top: 0;
                    color: #555;
                }
                .fare-rules p {
                    font-size: 0.9em;
                    color: #777;
                }
                .badge {
                    display: inline-block;
                    padding: 5px 10px;
                    background-color: #4caf50;
                    color: white;
                    border-radius: 5px;
                    font-size: 0.85em;
                }
                .ticket-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 2px solid #e0e0e0;
                }
                .ticket-header img {
                    height: 50px;
                }
            </style>
        </head>
        <body>
        <div class='container'>
            <div class='ticket-header'>
                <h2>Flight Ticket</h2>
                <img src='https://via.placeholder.com/100x50?text=Logo' alt='Airline Logo'>
            </div>";

            foreach ($Segment as $Seg) {
                $htmlCode .= "<div class='info-section'>
                    <table>
                        <tr>
                            <th>Flight</th>
                            <td>{$Seg['Airline_Code']}-{$Seg['Flight_Number']}</td>
                            <th>Class</th>
                            <td>{$flight_type}</td>
                        </tr>
                        <tr>
                            <th>Aircraft Type</th>
                            <td>Airbus A{$Seg['Aircraft_Type']}</td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th>Depart</th>
                            <td>{$Seg['Origin_City']} ({$Seg['Origin']}) - {$Seg['Departure_DateTime']}, Terminal {$Seg['Origin_Terminal']}</td>
                            <th>Arrive</th>
                            <td>{$Seg['Destination_City']} ({$Seg['Destination']}) - {$Seg['Arrival_DateTime']}, Terminal {$Seg['Destination_Terminal']}</td>
                        </tr>
                        <tr>
                            <th>Duration/Stops</th>
                            <td>{$Seg['Duration']}</td>
                            <th>Status</th>
                            <td><span class='badge'>Confirmed</span></td>
                        </tr>
                        <tr>
                            <th>Cabin</th>
                            <td>{$Cabin}</td>
                            <th>Check-In</th>
                            <td>{$CheckIn}</td>
                        </tr>
                    </table>
                </div>";
            }

            $htmlCode .= "<h3>Passenger Details</h3>
            <div class='info-section'>
                <table>
                    <tr>
                        <th>Phone</th>
                        <td>{$Contact}</td>
                        <th>Email</th>
                        <td>{$Email}</td>
                    </tr>";

        foreach ($paxDetails as $pax) {
            $gen = $pax['Gender'] === 0 ? "Male" : "Female";
            $htmlCode .= "<tr>
                        <th>Ticket No.</th>
                        <td>{$pax['TicketDetails'][0]['Ticket_Number']}</td>
                        <th>Name</th>
                        <td>{$pax['First_Name']} {$pax['Last_Name']}</td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td>{$gen}</td>
                    </tr>";
        }

        $htmlCode .= "</table>
            </div>
            <h3>Payment Details</h3>
            <div class='info-section'>
                <table>
                    <tr>
                        <th>Base Fare</th>
                        <td>INR {$BaseFare}</td>
                    </tr>
                    <tr>
                        <th>Taxes and Fees</th>
                        <td>INR {$Tax}</td>
                    </tr>
                    <tr>
                        <th>Gross Fare</th>
                        <td>INR {$TotalAmount}</td>
                    </tr>
                </table>
            </div>
            <h3>Fare Rule - Onward Journey</h3>
            <div class='fare-rules'>
                <h4>Cancellation Charges Per Pax</h4>
                <table>
                    <tr>
                        <th>Timeline</th>
                        <th>Penalty (Airline Fee)</th>
                    </tr>";

        foreach ($CancelArray as $cancel) {
            $htmlCode .= "<tr>
                            <td>{$cancel['DurationFrom']} - {$cancel['DurationTo']}</td>
                            <td>" . ($cancel['value'] === 'Non Refundable' ? $cancel['value'] : 'INR ' . $cancel['value']) . "</td>
                        </tr>";
        }

        $htmlCode .= "</table>
            <h4>Reschedule Charges Per Pax</h4>
            <table>
                <tr>
                    <th>Timeline</th>
                    <th>Penalty (Airline Fee)</th>
                </tr>";

        foreach ($RescheduleChargesArray as $charges) {
            $htmlCode .= "<tr>
                            <td>{$charges['DurationFrom']} - {$charges['DurationTo']}</td>
                            <td>" . ($charges['value'] === 'Non Refundable' ? $charges['value'] : 'INR ' . $charges['value'] . ' + Difference in Fare') . "</td>
                        </tr>";
        }

        $htmlCode .= "</table>
            <p>
                The above timeframe mentioned is the time till which cancellation/reschedule is permitted from the Airline side, and can be canceled by you when performing an online cancellation/reschedule. For any offline cancellation (to be done from our support office), we will need at least 6 hrs of buffer time to process the cancellation/reschedule offline.
            </p>
            <p>
                The above fare rules are just a guideline for your convenience and are subject to changes by the Airline from time to time. The agent does not guarantee the accuracy of cancel/rescheduling fees.
            </p>
        </div>
        </div>
        </body>
        </html>";


            
        $directoryPath = storage_path('app/public/tickets');
        $fileName = 'ticket-' . uniqid() . '.pdf';
        $filePath = $directoryPath . '/' . $fileName;

        // Check if the directory exists, if not, create it
        if (!file_exists($directoryPath)) {
            mkdir($directoryPath, 0755, true); // Create directory with appropriate permissions
        }

        // Load HTML into PDF and save it to the specified path
        $pdf = Pdf::loadHTML($htmlCode);

        // return response()->json($pdf);
        $pdf->save($filePath);

        // Return the saved file path
        return 'tickets/' . $fileName; 
    }


    public function Ticketing(Request $request)
    {    
        $validator = Validator::make($request->all(), [
            'BookingRef' => 'required|string',
            'UserRef' => 'required|string',
            'TicketingType' => 'required|string',
            'headersToken' => 'required|string',
            'headersKey' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            // Return the first error message
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
        
        $data = $validator->validated();
        $user = Auth()->guard('api')->user();

        // Prepare payload for the first request
        $payload = [
            "deviceInfo" => [
                "ip" => "122.161.52.233",
                "imeiNumber" => "12384659878976879887"
            ],
            "bookingRef" => $data['BookingRef'],
            "userRef" => $data['UserRef']
        ];

        // If the wallet check succeeds, proceed to ticketing
        $payloadTicket = [
            "deviceInfo" => [
                "ip" => "122.161.52.233",
                "imeiNumber" => "12384659878976879887"
            ],
            "bookingRef" => $data['BookingRef'],
            "ticketingType" => $data['TicketingType']
        ];
        
        // Prepare headers
        $headers = [
            'D-SECRET-TOKEN' => $data['headersToken'],
            'D-SECRET-KEY' => $data['headersKey'],
            'CROP-CODE' => 'DOTMIK160614',
            'Content-Type' => 'application/json',
        ];
        
        // API URL
        $checkWalletUrl = 'https://api.dotmik.in/api/flightBooking/v1/checkWallet';
        $ticketingUrl = 'https://api.dotmik.in/api/flightBooking/v1/ticketing';
        $RePrintTicketurl = 'https://api.dotmik.in/api/flightBooking/v1/rePrintTicket';
        
        try {
            // Make the POST request to check wallet
            $response = Http::withHeaders($headers)->post($checkWalletUrl, $payload);
            $statusCode = $response->status();
            $result = $response->json();
        
            if ($response->failed() || !isset($result['status']) || !$result['status']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to check wallet',
                    'error' => $result
                ], $statusCode);
            }
        
            $ticketingResponse = Http::withHeaders($headers)->post($ticketingUrl, $payloadTicket);

            $ticketingResult = $ticketingResponse->json();

            $ticketingStatusCode = $ticketingResponse->status();
        
            if ($ticketingResponse->failed() || !isset($ticketingResult['status']) || $ticketingResult['status'] === false) {
                return response()->json([
                    'success' => false,
                    'message' => $ticketingResult['message'] ?? 'Failed to issue ticket',
                    'error' => $ticketingResult
                ], $ticketingStatusCode);
            }
            $History=TravelHistory::where('BookingRef',$data['BookingRef'])->first();
            $History->update([
                'PnrDetails' => $result['payloads']['data']['pnrDetails'],
                'Status' => "BOOKED",
            ]);
            
            $payloadRePrintTicket = [
                "deviceInfo" => [
                    "ip" => "122.161.52.233",
                    "imeiNumber" => "12384659878976879887"
                ],
                "bookingRef" => $data['bookingRef'],
                "pnr" => $ticketingResult['payloads']['data']['pnrDetails'][0]['AirlinePNRs'][0]['Airline_PNR']
            ];

            $responseRePrint = Http::withHeaders($headers)->post($RePrintTicketurl, $payloadRePrintTicket);
            // $statusCode = $response->status();
            $resultRePrint = $responseRePrint->json();

            if($responseRePrint->successful())
            {

                // $Aircraft= $resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Aircraft_Type'];
                // $Origin_terminal=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Origin_Terminal'];
                // $ArrivalDateTime=new DateTime($resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Arrival_DateTime']);
                // $DepartureDateTime=new DateTime($resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Departure_DateTime']);
                // $Destination_terminal=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Destination_Terminal'];
                // $DurationTime= $resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Duration'];
            
                // $FlightNO = $resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Flight_Number']; 
                // $AirlineCode = $resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Airline_Code'];

                $Segment=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments'];

                // $PNR= $resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Airline_PNR'];
                // $Origin_Code= $resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['PAXTicketDetails'][0]['TicketDetails'][0]['SegemtWiseChanges']['0']['Origin'];
                // $Destination_Code= $resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['PAXTicketDetails'][0]['TicketDetails'][0]['SegemtWiseChanges']['0']['Destination'];

                $paxDetails=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['PAXTicketDetails'];

                // $Origin=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Origin'];

                // $Destination=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Destination'];

                $type=$resultRePrint['payloads']['data']['rePrintTicket']['Class_of_Travel'];

                $Cabin=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['Free_Baggage']['Hand_Baggage'];
               
                $CheckIn=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['Free_Baggage']['Check_In_Baggage'];
               
                $Contact=$resultRePrint['payloads']['data']['rePrintTicket']['PAX_Mobile'];
               
                $Email=$resultRePrint['payloads']['data']['rePrintTicket']['PAX_EmailId'];
               
                $BaseFare=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['Basic_Amount'];     
               
                $TotalAmount=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['Total_Amount'];
               
                $Cancellation=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['CancellationCharges'];
               
                $RescheduleCharges=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['RescheduleCharges'];
               
                $Tax=$resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['AirportTax_Amount'] + $resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['Trade_Markup_Amount'] ;
                
                $CancelArray=[];
                
                foreach($Cancellation as $cancel)
                {
                    $value = [
                        'DurationFrom' => $cancel['DurationFrom'],
                        'DurationTo' => $cancel['DurationTo'],
                        'value' => ($cancel['ValueType'] === 1) ? 'Non Refundable' : $cancel['Value'],
                    ];
                
                    $CancelArray[] = $value;
                }
                
                $RescheduleChargesArray=[];
            
                foreach($RescheduleCharges as $charges)
                {
                    $value = [
                        'DurationFrom' => $charges['DurationFrom'],
                        'DurationTo' => $charges['DurationTo'],
                        'value' => ($charges['ValueType'] === 1) ? 'Non Refundable' : $charges['Value'],
                    ];
                
                    $RescheduleChargesArray[] = $value;
                }
                
                if($type === 0)
                {
                    $flight_type="Ecomony";
                }
                else if($type === 1) //  BUSINESS/ 2- FIRST/ 3- PREMIUM_ECONOMY
                {
                    $flight_type="Business";
                }
                else if($type === 2)
                {
                    $flight_type="First Class";
                }
                else if($type === 3)
                {
                    $flight_type="Premium Ecomomy";
                }
        
                // $ArrivalTime = $ArrivalDateTime->format('H:i'); // Outputs '16:25'
                // $DepartureTime= $DepartureDateTime->format('H:i');
                // $ArrivalDate = $ArrivalDateTime->format('D d M, Y');
                // $DepartureDate = $DepartureDateTime->format('D d M, Y');
        
                // $dateTime = DateTime::createFromFormat('H:i', $DurationTime);
        
                // // Extract hours and minutes
                // $hours = $dateTime->format('G'); // 'G' formats hours without leading zeros
                // $minutes = $dateTime->format('i'); // 'i' formats minutes with leading zeros
        
                // // Format as "1h 05m"
                // $Duration = $hours . 'h ' . $minutes . 'm';
                
                // Generate the PDF
                $pdfFilePath = $this->generateTicketPdf($Cabin,$CheckIn,$Contact, $Email, $BaseFare, $TotalAmount, $CancelArray, $RescheduleChargesArray, $Tax, $paxDetails,$Segment,$flight_type);
                // $first,$last,$Ticket,$gen,

                $pdf_url = asset('storage/' . $pdfFilePath);
                            
                $History=TravelHistory::where('BookingRef',$data['BookingRef'])->first();

                if($History)
                {
                    $History->update([
                        'PAXTicketDetails' => $resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['PAXTicketDetails'],
                        'TravelDetails' => $resultRePrint['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments'],
                        'Ticket_URL' => $pdf_url
                    ]);
                }
                
            }else {
                // DB::rollBack(); // Rollback transaction if checkTicket fails
                return response()->json([
                    'success' => false,
                    'message' => 'Bus Booked Successfully. Check ticket API failed',
                    'error' => $resultRePrint,
                ], $responseRePrint->status());
            }


            $BookingRef = $data['BookingRef'];
            
            $Pnr = $result['payloads']['data']['pnrDetails'][0]['AirlinePNRs'][0]['Airline_PNR'];

            Mail::to($user->email)->send(new UserFlightBooking($Pnr,$BookingRef,$pdf_url));

            // If ticketing is successful, return the success response
            return response()->json([
                'success' => true,
                'message' => 'Flight Booked Successfully',
                'data' => $ticketingResult
            ], $ticketingStatusCode);
        
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }     
    }

   
 
    public function RePrintTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'headersToken' => 'required|string',
            'headersKey' => 'required|string',
            "bookingRef" => "required|string", 
            "pnr" => "nullable|string"
        ]);     
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
        
        $data = $validator->validated();

        // if(!$data['bookingRef'] && !$data['pnr'])
        // {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Provide Either Bookin Ref or PNR'
        //     ], 400);
        // }
        
        $payload = [
            "deviceInfo" => [
                "ip" => "122.161.52.233",
                "imeiNumber" => "12384659878976879887"
            ],
            "bookingRef" => $data['bookingRef'],
            "pnr" => $data["pnr"]
        ];
        
        $headers = [
            'D-SECRET-TOKEN' => $data['headersToken'],
            'D-SECRET-KEY' => $data['headersKey'],
            'CROP-CODE' => 'DOTMIK160614',
            'Content-Type' => 'application/json',
        ];

        // API URL
        $url = 'https://api.dotmik.in/api/flightBooking/v1/rePrintTicket';

        try {
            // Make the POST request using Laravel HTTP Client
                $response = Http::withHeaders($headers)->post($url, $payload);
                $result=$response->json();
            
            //    $result=json_decode($result,true);

                $statusCode = $response->status();

                // return response()->json(['result'=>$result],$statusCode);
                
                if($result['status'] === false)
                {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'error' => $result
                    ],$statusCode);
                }
                else
                {
                    if($response->successful())
                    {    

                        // $Aircraft= $result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Aircraft_Type'];
                        // $Origin_terminal=$result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Origin_Terminal'];
                        // $ArrivalDateTime=new DateTime($result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Arrival_DateTime']);
                        // $DepartureDateTime=new DateTime($result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Departure_DateTime']);
                        // $Destination_terminal=$result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Destination_Terminal'];/

                        // $DurationTime= $result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Duration'];
                    
                        // $FlightNO = $result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Flight_Number']; 
                        // $AirlineCode = $result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments']['0']['Airline_Code'];

                        $Segment=$result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments'];

                        // $PNR= $result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Airline_PNR'];
                        // $Origin_Code= $result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['PAXTicketDetails'][0]['TicketDetails'][0]['SegemtWiseChanges']['0']['Origin'];
                        // $Destination_Code= $result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['PAXTicketDetails'][0]['TicketDetails'][0]['SegemtWiseChanges']['0']['Destination'];

                        $paxDetails=$result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['PAXTicketDetails'];

                        // $Origin=$result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Origin'];

                        // $Destination=$result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Destination'];

                        $type=$result['payloads']['data']['rePrintTicket']['Class_of_Travel'];

                        $Cabin=$result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['Free_Baggage']['Hand_Baggage'];
                        
                        $CheckIn=$result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['Free_Baggage']['Check_In_Baggage'];

                        $Contact=$result['payloads']['data']['rePrintTicket']['PAX_Mobile'];
                        
                        $Email=$result['payloads']['data']['rePrintTicket']['PAX_EmailId'];
                        
                        $BaseFare=$result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['Basic_Amount'];
                        
                        $TotalAmount=$result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['Total_Amount'];

                        $Cancellation=$result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['CancellationCharges'];

                        $RescheduleCharges=$result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['RescheduleCharges'];


                        $Tax= $result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['AirportTax_Amount'] + $result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Fares'][0]['FareDetails']['0']['Trade_Markup_Amount'] ;
                        

                        // https://api.launcherr.co/api/show/Airline?code=AI

                        $CancelArray=[];
                        
                        foreach($Cancellation as $cancel)
                        {
                            $value = [
                                'DurationFrom' => $cancel['DurationFrom'],
                                'DurationTo' => $cancel['DurationTo'],
                                'value' => ($cancel['ValueType'] === 1) ? 'Non Refundable' : $cancel['Value'],
                            ];
                            $CancelArray[] = $value;
                        }
                        
                        $RescheduleChargesArray=[];
                    
                        foreach($RescheduleCharges as $charges)
                        {
                            $value = [
                                'DurationFrom' => $charges['DurationFrom'],
                                'DurationTo' => $charges['DurationTo'],
                                'value' => ($charges['ValueType'] === 1) ? 'Non Refundable' : $charges['Value'],
                            ];
                        
                            $RescheduleChargesArray[] = $value;
                        }
                        
                        // if($Gender === 0)
                        // {
                        //     $gen = 'Male';
                        // }
                        // elseif ($Gender === 1)
                        // {
                        //     $gen = 'Female';
                        // }

                        if($type === 0)
                        {
                            $flight_type="Ecomony";
                        }
                        else if($type === 1) //  BUSINESS/ 2- FIRST/ 3- PREMIUM_ECONOMY
                        {
                            $flight_type="Business";
                        }
                        else if($type === 2)
                        {
                            $flight_type="First Class";
                        }
                        else if($type === 3)
                        {
                            $flight_type="Premium Ecomomy";
                        }
                
                        // $ArrivalTime = $ArrivalDateTime->format('H:i'); // Outputs '16:25'
                        // $DepartureTime= $DepartureDateTime->format('H:i');
                        // $ArrivalDate = $ArrivalDateTime->format('D d M, Y');
                        // $DepartureDate = $DepartureDateTime->format('D d M, Y');

                        // $dateTime = DateTime::createFromFormat('H:i', $DurationTime);
                
                        // // Extract hours and minutes
                        // $hours = $dateTime->format('G'); // 'G' formats hours without leading zeros
                        // $minutes = $dateTime->format('i'); // 'i' formats minutes with leading zeros
                
                        // // Format as "1h 05m"
                        // $Duration = $hours . 'h ' . $minutes . 'm';
                
                        // Generate the PDF
                        $pdfFilePath = $this->generateTicketPdf($Cabin,$CheckIn,$Contact, $Email, $BaseFare, $TotalAmount, $CancelArray, $RescheduleChargesArray, $Tax, $paxDetails,$Segment,$flight_type);
                        // $first,$last,$Ticket,$gen,
                        
                        $pdf_url= asset('storage/' . $pdfFilePath);

                        $History=TravelHistory::where('BookingRef',$data['bookingRef'])->first();

                        if($History)
                        {
                            $History->update([
                                'PAXTicketDetails' => $result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['PAXTicketDetails'],
                                'TravelDetails' => $result['payloads']['data']['rePrintTicket']['pnrDetails'][0]['Flights'][0]['Segments'],
                                'Ticket_URL' => $pdf_url
                            ]);
                        }


                        return response()->json([
                            'success' => true,
                            'pdf_url' => $pdf_url, // Return the URL for the PDF file
                            'data' => $result,
                            'history' => $History
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => $result['message'],
                            'error' => $result,
                        ],$statusCode);
                    }
                }
            // Assume successful response from the API
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errorline' => $e->getLine() 
            ], 500);
        }   
    }

    public function Cancellation(Request $request)
    {
        $validator = Validator::make($request->all(),[
            "headersToken" => 'required|string',
            "headersKey" => 'required|string',
            "bookingRef" => "required|string", 
            "pnr" => "required|string",
            "ticketCancelDetails" => "required|array",
            "ticketCancelDetails.*.FlightId" => "required|string",
            "ticketCancelDetails.*.PassengerId" => "required|string",
            "ticketCancelDetails.*.SegmentId" => "required|string",
            "cancelType" => "required|string", //0-Normal Cancel, 1-Full Refund, 2-No Show
            "cancelCode" => "required|string",
            "remark" => "required|string"
        ]);     
        
        if ($validator->fails()) {
            $errors = $validator->errors()->all(); // Get all error messages
            $formattedErrors = [];
            
            foreach ($errors as $error) {
                $formattedErrors[] = $error;
            }
        
            return response()->json([
                'success' => false,
                'message' => $formattedErrors[0]
            ], 422);
        }
        
        $data = $validator->validated();
        $user = Auth()->guard('api')->user();

        $payload = [
            "deviceInfo"=> [
                "ip"=> "122.161.52.233",
                "imeiNumber" => "12384659878976879887"
            ],
            "ticketCancelDetails" => $data["ticketCancelDetails"],
            "pnr" => $data["pnr"],
            "bookingRef" => $data["bookingRef"],
            "cancelType" => $data["cancelType"], //0-Normal Cancel, 1-Full Refund, 2-No Show
            "cancelCode" => $data["cancelCode"],
            "remark" => $data["remark"]
        ];

        // return response()->json($payload,200);

        // Headers
        $headers = [
            'D-SECRET-TOKEN' => $data['headersToken'], 
            'D-SECRET-KEY' => $data['headersKey'] ,
            'CROP-CODE' => 'DOTMIK160614',
            'Content-Type' => 'application/json',
        ];
                
        // API URL
        $url = 'https://api.dotmik.in/api/flightBooking/v1/cancellation';

        try {
            // Make the POST request using Laravel HTTP Client
            $response = Http::withHeaders($headers)->timeout(60)->post($url, $payload);
            $result = $response->json();
            $statusCode = $response->status();

            if($result['status'] === false)
            {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result
                ],$statusCode);
            }
            else{
                if($response->successful())
                {
                    $History=TravelHistory::where('BookingRef',$data['bookingRef'])->first();
                    if($History)
                    {
                        $History->update([
                            'Status' => 'CANCELLED'
                        ]);
                    }
                    
                    $BookingRef=$data["bookingRef"];

                    $pnr=$data["pnr"];

                    Mail::to($user->email)->send(new UserFlightTicketCancel($pnr,$BookingRef));

                    return response()->json([
                        'success' => true,
                        'message' => $result['message'] ?? 'Flight Ticket Cancelled',
                        'data' => $result,
                    ], $statusCode);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'error' => $result
                    ],$statusCode);
                }
            }
            //code...
        } catch  (\Exception $e) {
            // Handle exception (e.g. network issues)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }   
    }

    public function LowFare(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'headersToken' => 'required|string',
            'headersKey' => 'required|string',
                "origin" => "required|string",
            "destination" => "required|string",
            "month" => "required|string",
            "year" => "required|string",
        ]);     

        if ($validator->fails()) {
            $errors = $validator->errors()->all(); // Get all error messages
            $formattedErrors = [];

            foreach ($errors as $error) {
                $formattedErrors[] = $error;
            }

            return response()->json([
                'success' => false,
                'error' => $formattedErrors[0]
            ], 422);
        }
        
        $data=$validator->validated();

        $payload = [
            "deviceInfo" => [
                "ip" => "122.161.52.233",
                "imeiNumber" => "12384659878976879887"
            ],
            "origin" => $data['origin'],
            "destination" => $data['destination'],
            "month" => $data["month"],
            "year" => $data["year"]
        ];
        
        // Headers
        $headers = [
            'D-SECRET-TOKEN' => $data['headersToken'],
            'D-SECRET-KEY' => $data['headersKey'],
            'CROP-CODE' => 'DOTMIK160614',
            'Content-Type' => 'application/json',
        ];

        // API URL
        $url = 'https://api.dotmik.in/api/flightBooking/v1/lowFare';

        try {
            // Make the POST request using Laravel HTTP Client
            $response = Http::withHeaders($headers)->post($url, $payload);
            $result=$response->json();
            $statusCode = $response->status();

            if($result['status'] === false)
            {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result
                ],$statusCode);   
            }
            else{
                if($response->successful())
                {
                    return response()->json([
                        'success' => true,
                        'data' => $result,
                    ], $statusCode);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'error' => $result
                    ],$statusCode);
                }
            }
            //code...
        } catch  (\Exception $e) {
            // Handle exception (e.g. network issues)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }   
    }

    public function SectorAvalability(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'headersToken' => 'required|string',
            'headersKey' => 'required|string',
        ]);     

        if ($validator->fails()) {
            $errors = $validator->errors()->all(); // Get all error messages
            $formattedErrors = [];

            foreach ($errors as $error) {
                $formattedErrors[] = $error;
            }

            return response()->json([
                'success' => false,
                'message' => $formattedErrors[0]
            ], 422);
        }
        
        $data=$validator->validated();

        $payload = [
            "deviceInfo" => [
                "ip" => "122.161.52.233",
                "imeiNumber" => "12384659878976879887"
            ]
        ];
        
        // Headers
        $headers = [
            'D-SECRET-TOKEN' => $data['headersToken'],
            'D-SECRET-KEY' => $data['headersKey'],
            'CROP-CODE' => 'DOTMIK160614',
            'Content-Type' => 'application/json',
        ];

        // API URL
        $url = 'https://api.dotmik.in/api/flightBooking/v1/sectorAvailabilityPi';

        try {
            // Make the POST request using Laravel HTTP Client
            $response = Http::withHeaders($headers)->post($url, $payload);
            $result=$response->json();
            $statusCode = $response->status();

            if($result['status'] === false)
            {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result
                ],$statusCode);   
            }
            else{
                if($response->successful())
                {
                    return response()->json([
                        'success' => true,
                        'data' => $result,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'error' => $result
                    ],$statusCode);
                }
            }
            //code...
        } catch  (\Exception $e) {
            // Handle exception (e.g. network issues)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }   
    }

    public function ReleasePNR(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'headersToken' => 'required|string',
            'headersKey' => 'required|string',
            "travelType" => "required|string",
            "bookingType" => "required|string",
            "origin" => "required|string",
            "destination" => "required|string",
            "travelDate" => "required|date",
            "tripId" => "required|string",
            "adultCount" => "required|string",
            "childCount" => "required|string",
            "infantCount" => "required|string",
            "classOfTravel" => "required|string",
        ]);     

        if ($validator->fails()) {
            $errors = $validator->errors()->all(); // Get all error messages
            $formattedErrors = [];

            foreach ($errors as $error) {
                $formattedErrors[] = $error;
            }

            return response()->json([
                'success' => false,
                'messsage' => $formattedErrors[0]
            ], 422);
        }
        
        $data=$validator->validated();

        $payload = [
            "deviceInfo" => [
                "ip" => "122.161.52.233",
                "imeiNumber" => "12384659878976879887"
            ],
            "travelType" => $data['travelType'], // Domestic or International
            "bookingType" => $data['bookingType'], // One way
            "tripInfo" => [
                "origin" => $data['origin'],
                "destination" => $data['destination'],
                "travelDate" => $data['travelDate'], // MM/DD/YYYY
                "tripId" => $data['tripId'] // Ongoing trip
            ],
            "adultCount" => $data['adultCount'],
            "childCount" => $data['childCount'],
            "infantCount" => $data['infantCount'],
            "classOfTravel" => $data['classOfTravel'],// This requires the class of Travel. Possible values: 0- ECONOMY/ 1- BUSINESS/ 2- FIRST/ 3- PREMIUM_ECONOMY
        ];

        
        // Headers
        $headers = [
            'D-SECRET-TOKEN' => $data['headersToken'],
            'D-SECRET-KEY' => $data['headersKey'],
            'CROP-CODE' => 'DOTMIK160614',
            'Content-Type' => 'application/json',
        ];

        // API URL
        $url = 'https://api.dotmik.in/api/flightBooking/v1/releasePnr';

        try {
            // Make the POST request using Laravel HTTP Client
            $response = Http::withHeaders($headers)->post($url, $payload);
            $result=$response->json();
            $statusCode = $response->status();

            if($result['status'] === false)
            {
                return response()->json([
                    'success' => 0,
                    'message' => $result['message'],
                    'error' => $result
                ],$statusCode);
            }
            else{
                if($response->successful())
                {
                    return response()->json([
                        'success' => true,
                        'data' => $result,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'error' => $result
                    ],$statusCode);
                }
            }
            //code...
        } catch  (\Exception $e) {
            // Handle exception (e.g. network issues)
            return response()->json([
                'success' => false,
                'message' =>  $e->getMessage()
            ], 500);
        }   
    }

}


        // $validator = Validator::make($request->all(), [
        //     'mobile' => 'required|string',
        //     'whatsApp' => 'required|string',
        //     'email' => 'required|string',
        //     // 'passenger_details.paxId' => 'required|integer',
        //     // 'passenger_details.paxType' => 'required|integer', // 0-ADT/1-CHD/2-INF
        //     // 'passenger_details.title' => 'required|string',   // MR, MRS, MS; MSTR, MISS for child/infant
        //     // 'passenger_details.firstName' => 'required|string',
        //     // 'passenger_details.lastName' => 'required|string',
        //     // 'passenger_details.age' => 'nullable|integer',
        //     // 'passenger_details.gender' => 'required|integer',  // 0-Male, 1-Female
        //     // 'passenger_details.dob' => 'required|date',
        //     // 'passenger_details.passportNumber' => 'nullable|string',
        //     // 'passenger_details.passportIssuingAuthority' => 'required|string',
        //     // 'passenger_details.passportExpire' => 'required|date',
        //     // 'passenger_details.nationality' => 'required|string',
        //     // 'passenger_details.pancardNumber' => 'nullable|string',
        //     // 'passenger_details.frequentFlyerDetails' => 'nullable|string',
        //     'passenger_details' => 'required|array',  // Ensure it's an array
        //     'passenger_details.*.paxId' => 'required|integer',
        //     'passenger_details.*.paxType' => 'required|integer|in:0,1,2', // 0-ADT, 1-CHD, 2-INF
        //     'passenger_details.*.title' => [
        //         'required',
        //         'string',
        //         function($attribute, $value, $fail) {
        //             $paxType = request()->input(str_replace('.title', '.paxType', $attribute));
        //             if ($paxType == 0 && !in_array($value, ['Mr', 'Mrs', 'Ms'])) {
        //                 $fail("The {$attribute} must be one of Mr, Mrs, or Ms for adults.");
        //             } elseif (in_array($paxType, [1, 2]) && !in_array($value, ['MSTR', 'MISS'])) {
        //                 $fail("The {$attribute} must be one of MSTR or MISS for children/infants.");
        //             }
        //         }
        //     ],
        //     'passenger_details.*.firstName' => 'required|string',
        //     'passenger_details.*.lastName' => 'required|string',
        //     'passenger_details.*.age' => 'nullable|integer',
        //     'passenger_details.*.gender' => 'required|integer|in:0,1',  // 0-Male, 1-Female
        //     'passenger_details.*.dob' => 'required|date',
        //     'passenger_details.*.passportNumber' => 'nullable|string',
        //     'passenger_details.*.passportIssuingAuthority' => 'required|string',
        //     'passenger_details.*.passportExpire' => 'required|date',
        //     'passenger_details.*.nationality' => 'required|string',
        //     'passenger_details.*.pancardNumber' => 'nullable|string',
        //     'passenger_details.*.frequentFlyerDetails' => 'nullable|string',
        //     'gst.isGst' => 'required|string',
        //     'gst.gstNumber' => 'nullable|string',
        //     'gst.gstName' => 'nullable|string',
        //     'gst.gstAddress' => 'nullable|string',
        //     'searchKey' => 'required|string',
        //     'FlightKey' => 'required|string',
        //     'headersToken' => 'required|string',
        //     'headersKey' => 'required|string'
        // ]);
        
        // if ($validator->fails()) {
        //     $errors = $validator->errors()->all();
        //     return response()->json([
        //         'success' => false,
        //         'message' => $errors[0] // Return the first error
        //     ], 422);
        // }
        
        // $data = $validator->validated();


        // // return response()->json($data['passenger_details']['mobile']);

        // $payload = [
        //     "deviceInfo" => [
        //         "ip" => "122.161.52.233",
        //         "imeiNumber" => "12384659878976879887"
        //     ],
        //     "passengers" => [
        //         "mobile" => $data['mobile'],
        //         "whatsApp" => $data['whatsApp'],
        //         "email" => $data['email'],
        //         "paxDetails" => array_map(function ($passenger) {
        //             return [
        //                 "paxId" => $passenger['paxId'],
        //                 "paxType" => $passenger['paxType'],
        //                 "title" => $passenger['title'],
        //                 "firstName" => $passenger['firstName'],
        //                 "lastName" => $passenger['lastName'],
        //                 "gender" => $passenger['gender'],
        //                 "age" => $passenger['age'] ?? null,
        //                 "dob" => $passenger['dob'],
        //                 "passportNumber" => $passenger['passportNumber'],
        //                 "passportIssuingAuthority" => $passenger['passportIssuingAuthority'],
        //                 "passportExpire" => $passenger['passportExpire'],
        //                 "nationality" => $passenger['nationality'],
        //                 "pancardNumber" => $passenger['pancardNumber'] ?? null,
        //                 "frequentFlyerDetails" => $passenger['frequentFlyerDetails'] ?? null
        //             ];
        //         }, $data['passenger_details']) // map each passenger
        //     ],
        //     "gst" => [
        //         "isGst" => $data['gst']['isGst'],
        //         "gstNumber" => $data['gst']['gstNumber'],
        //         "gstName" => $data['gst']['gstName'],
        //         "gstAddress" => $data['gst']['gstAddress']
        //     ],
        //     "flightDetails" => [
        //         [
        //             "searchKey" => $data['searchKey'],
        //             "flightKey" => $data['FlightKey'],
        //             "ssrDetails" => [] // Empty SSR details
        //         ]
        //     ],
        //     "costCenterId" => 0,
        //     "projectId" => 0,
        //     "bookingRemark" => "Test booking with PAX details",
        //     "corporateStatus" => 0,
        //     "corporatePaymentMode" => 0,
        //     "missedSavingReason" => null,
        //     "corpTripType" => null,
        //     "corpTripSubType" => null,
        //     "tripRequestId" => null,
        //     "bookingAlertIds" => null
        // ];
        
        // // Headers
        // $headers = [
        //     'D-SECRET-TOKEN' => $data['headersToken'],
        //     'D-SECRET-KEY' => $data['headersKey'],
        //     'CROP-CODE' => 'DOTMIK160614',
        //     'Content-Type' => 'application/json',
        // ];
        
        // // API URL
        // $url = 'https://api.dotmik.in/api/flightBooking/v1/tempBooking';
        
        // try {
        //     // Make POST request
        //     $response = Http::withHeaders($headers)->post($url, $payload);
        //     $result = $response->json();
        //     $statusCode = $response->status();
            

        //     if ($result['status'] === false) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => $result['message'],
        //             'error' => $result
        //         ],$statusCode);
        //     } else {
        //         if ($response->successful()) {
        //             return response()->json([
        //                 'success' => true,
        //                 'data' => $result,
        //             ], $statusCode);
        //         } else {
        //             return response()->json([
        //                 'success' => false,
        //                 'message' => $result['message'],
        //                 'error' => $result
        //             ],$statusCode);
        //         }
        //     }
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => $e->getMessage()
        //     ], 500);
        // }        




// One Way Search Flight
// $validator=Validator::make($request->all(),[
//     "origin" => "required|string",
//     "destination" => "required|string",
//     "travelDate" => "required|date",
//     "travelType" => "required|string",
//     "bookingType" => "required|string",
//     "tripId" => "required|string",
//     "adultCount" => "required|string",
//     "childCount" => "required|string",
//     "infantCount" => "required|string",
//     "classOfTravel" => "required|string",
//     "airlineCode" => "nullable|string",

//     // for Header  
//     "headersToken" => "required|string",
//     "headersKey" => "required|string",

//     // for Filter
//     "Refundable" => "nullable|boolean",
//     'Arrival' => 'nullable|string',
//     'Departure' => 'nullable|string',
// ]);

// if ($validator->fails()) {
//     $errors = $validator->errors()->all(); // Get all error messages
//     $formattedErrors = [];

//     foreach ($errors as $error) {
//         $formattedErrors[] = $error;
//     }

//     return response()->json([
//         'success' => false,
//         'message' => $formattedErrors[0]
//     ], 422);
// }

// $data=$validator->validated();

// $payload = [
//     "deviceInfo" => [
//         "ip" => "122.161.52.233",
//         "imeiNumber" => "12384659878976879888"
//     ],
//     "travelType" => $data['travelType'], // Domestic or International
//     "bookingType" => $data['bookingType'], // One way
//     "tripInfo" => [
//         "origin" => $data['origin'],
//         "destination" => $data['destination'],
//         "travelDate" => $data['travelDate'], // MM/DD/YYYY
//         "tripId" => $data['tripId'] // Ongoing trip
//     ],
//     "adultCount" => $data['adultCount'],
//     "childCount" => $data['childCount'],
//     "infantCount" => $data['infantCount'],
//     "classOfTravel" => $data['classOfTravel'], // Economy
//     "filteredAirLine" => [
//          "airlineCode" => $data['airlineCode'] ?? ''
//     ]
// ];

// // Headers
// $headers = [
//     'D-SECRET-TOKEN' => $data['headersToken'],
//     'D-SECRET-KEY' => $data['headersKey'],
//     'CROP-CODE' => 'DOTMIK160614',
//     'Content-Type' => 'application/json',
// ];

// // API URL
// $url = 'https://api.dotmik.in/api/flightBooking/v1/searchFlight';

// try {
//     // Make the POST request using Laravel HTTP Client
//     $response = Http::withHeaders($headers)->post($url, $payload);
//     $result=$response->json();
//     $statusCode = $response->status();

//     if($result['status'] === false)
//     {
//         return response()->json([
//             'success' => false,
//             'message' => $result['message'],
//             'error' => $result
//         ],$statusCode);   
//     }
//     else
//     {   
//         if ($response->successful()) 
//         {

//             $dataa = $result['payloads']['data']['tripDetails'];
            
//             // return response()->json($dataa);

//             $flights = $dataa[0]['Flights']; // Flights is already an array
            
//             // Filter flights based on Departure and Arrival times
//             if(isset($data['Arrival']) && isset($data['Departure']))
//             {

//                 if($data['Arrival'] === '12AM6AM')
//                 {  
//                     $arrivalDateTimeLow = "{$data['travelDate']} 00:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $arrivalDateTimeHigh = "{$data['travelDate']} 06:00";
//                 }
//                 else if($data['Arrival'] === '6AM12PM')
//                 {  
//                     $arrivalDateTimeLow = "{$data['travelDate']} 06:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $arrivalDateTimeHigh = "{$data['travelDate']} 12:00";
//                 }
//                 else if($data['Arrival'] === '12PM6PM')
//                 {   
//                     $arrivalDateTimeLow = "{$data['travelDate']} 12:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $arrivalDateTimeHigh = "{$data['travelDate']} 18:00";
//                 }
//                 else if($data['Arrival'] === '6PM12AM')
//                 {
//                     $arrivalDateTimeLow = "{$data['travelDate']} 18:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $arrivalDateTimeHigh = "{$data['travelDate']} 24:00";
//                 }
//                 else{
//                     return response()->json('Invalid Arrival time or condition.', 400);
//                 }

//                 if($data['Departure'] === '12AM6AM')
//                 {
//                     $departureDateTimeLow = "{$data['travelDate']} 00:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $departureDateTimeHigh = "{$data['travelDate']} 06:00";
//                 }
//                 else if($data['Departure'] === '6AM12PM')
//                 {
                    
//                     $departureDateTimeLow = "{$data['travelDate']} 06:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $departureDateTimeHigh = "{$data['travelDate']} 12:00";
//                 }
//                 else if($data['Departure'] === '12PM6PM')
//                 {   
//                     $departureDateTimeLow = "{$data['travelDate']} 12:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $departureDateTimeHigh = "{$data['travelDate']} 18:00";
//                 }
//                 else if($data['Departure'] === '6PM12AM')
//                 {
//                     $departureDateTimeLow = "{$data['travelDate']} 18:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $departureDateTimeHigh = "{$data['travelDate']} 24:00";
//                 }
//                 else{
//                     return response()->json('Invalid Arrival time or condition.', 400);
//                 }
                
//                 $filteredSegments = collect($flights)->filter(function ($flight) use ( $arrivalDateTimeLow,$arrivalDateTimeHigh,$departureDateTimeLow,$departureDateTimeHigh) {
//                     return $flight['Segments'][0]['Arrival_DateTime'] >= $arrivalDateTimeLow && $arrivalDateTimeHigh >= $flight['Segments'][0]['Arrival_DateTime'] &&  $flight['Segments'][0]['Departure_DateTime'] >= $departureDateTimeLow &&  $flight['Segments'][0]['Departure_DateTime'] <= $departureDateTimeHigh;
//                 });

//                 $filteredFlights = $filteredSegments->all();

//                 $filteredFlights = array_values($filteredFlights); 
//             }
//             else if (isset($data['Arrival'])) 
//             {                       
//                 if($data['Arrival'] === '12AM6AM')
//                 {
//                     $arrivalDateTimeLow = "{$data['travelDate']} 00:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $arrivalDateTimeHigh = "{$data['travelDate']} 06:00";
//                 }
//                 else if($data['Arrival'] === '6AM12PM')
//                 {
                    
//                     $arrivalDateTimeLow = "{$data['travelDate']} 06:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $arrivalDateTimeHigh = "{$data['travelDate']} 12:00";
//                 }
//                 else if($data['Arrival'] === '12PM6PM')
//                 {   
//                     $arrivalDateTimeLow = "{$data['travelDate']} 12:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $arrivalDateTimeHigh = "{$data['travelDate']} 18:00";
//                 }
//                 else if($data['Arrival'] === '6PM12AM')
//                 {
//                     $arrivalDateTimeLow = "{$data['travelDate']} 18:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $arrivalDateTimeHigh = "{$data['travelDate']} 24:00";
//                 }
//                 else{
//                     return response()->json('Invalid Arrival time or condition.', 400);
//                 }

//                 $filteredSegments = collect($flights)->filter(function ($flight) use ( $arrivalDateTimeLow,$arrivalDateTimeHigh) {
//                 return $flight['Segments'][0]['Arrival_DateTime'] >= $arrivalDateTimeLow && $arrivalDateTimeHigh >= $flight['Segments'][0]['Arrival_DateTime'];
//                 });

//                 $filteredFlights = $filteredSegments->all();

//                 $filteredFlights=array_values($filteredFlights);                    
//             }
//             else if (isset($data['Departure'])) 
//             {                       
//                 if($data['Departure'] === '12AM6AM')
//                 {
//                     $departureDateTimeLow = "{$data['travelDate']} 00:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $departureDateTimeHigh = "{$data['travelDate']} 06:00";
//                 }
//                 else if($data['Departure'] === '6AM12PM')
//                 {
                    
//                     $departureDateTimeLow = "{$data['travelDate']} 06:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $departureDateTimeHigh = "{$data['travelDate']} 12:00";
//                 }
//                 else if($data['Departure'] === '12PM6PM')
//                 {   
//                     $departureDateTimeLow = "{$data['travelDate']} 12:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $departureDateTimeHigh = "{$data['travelDate']} 18:00";
//                 }
//                 else if($data['Departure'] === '6PM12AM')
//                 {
//                     $departureDateTimeLow = "{$data['travelDate']} 18:00";
//                     // return response()->json($arrivalDateTimeLow, 400);
//                     $departureDateTimeHigh = "{$data['travelDate']} 24:00";
//                 }
//                 else{
//                     return response()->json('Invalid Arrival time or condition.', 400);
//                 }

//                 $filteredSegments = collect($flights)->filter(function ($flight) use ( $departureDateTimeLow,$departureDateTimeHigh) {
//                 return $flight['Segments'][0]['Departure_DateTime'] >= $departureDateTimeLow && $departureDateTimeHigh >= $flight['Segments'][0]['Departure_DateTime'];
//                 });

//                 $filteredFlights = $filteredSegments->all();
//                 $filteredFlights=array_values($filteredFlights);

//             } 
//             else {
//                 $filteredFlights = $flights; // No filter, return all flights
//                 // return response()->json($filteredFlights[0]['Fares'][0]['Refundable'],400);
//             }

//             if(isset($data['Refundable']))
//             {
//                 // return response()->json($filteredFlights['Fare']);
//                 $refundable = $data['Refundable'];
//                 $filtered = array_filter($filteredFlights, function($flight) use($refundable) {
//                     return $flight['Fares'][0]['Refundable'] === $refundable ;
//                 });
//                 $filtered=array_values($filtered);
//             }
//             else{
//                 // return response()->json('Helloji');
//                 $filtered = $filteredFlights;
//             }

//             // Extract distinct airline codes
//             $airlineCodes = array_map(function($flight) {
//                 return $flight['Airline_Code'] ?? null; // Handle cases where 'Airline_Code' may not exist
//             }, $filtered);

//             // Remove null values and get distinct airline codes
//             $distinctAirlineCodes = array_unique(array_filter($airlineCodes));

//             // Re-index array to remove numeric keys
//             $distinctAirlineCodes = array_values($distinctAirlineCodes);

//             $count = count($filtered);

//             $payloads = [
//                     'errors' => [],
//                     'data' => [
//                         'tripDetails' => [
//                             [
//                                 'Flights' => $filtered
//                             ]
//                         ]
//                     ]
//             ];
            
//             return response()->json([
//                 'status' => true,
//                 'count' => $count,
//                 'status_code' => $result['status_code'],
//                 'request_id' => $result['request_id'],
//                 'SearchKey' => $result['payloads']['data']['searchKey'],
//                 'AirlineCodes' =>  $distinctAirlineCodes,
//                 'payloads' => $payloads,
//             ], 200);
//         } else {
//             return response()->json([
//                 'success' => false,
//                 'message' => $result['message'],
//                 'error' => $result
//             ],$statusCode);
//         }
//     }
// } catch (\Exception $e) {
//     // Handle exception (e.g. network issues)
//     return response()->json([
//         'success' => false,
//         'message' => $e->getMessage()
//     ], 500);
// }    


        // Custom validation rules based on bookingType


        // $validator->after(function ($validator) use ($request) {

            // $bookingType = $request->input('bookingType');

            // return response()->json($boo);

        //             if ($bookingType === "0") {
        //                 // Additional validation for bookingType 0
        // /            }
        //  else
            // if ($bookingType == "1") {
                // Additional validation for bookingType 1
                // $tripInfo = $request->input('tripInfo');
                // if (is_array($tripInfo)) {
                //     foreach ($tripInfo as $key => $trip) {
                //         if (!isset($trip['origin']) || !isset($trip['destination']) || !isset($trip['travelDate']) || !isset($trip['tripId'])) {
                //             $validator->errors()->add("tripInfo.$key", 'Each trip in tripInfo must have origin, destination, travelDate, and tripId.');
                //         }
                //     }
                //     if (!$request->has('returnTravelDate')) {
                //         $validator->errors()->add('returnTravelDate', 'returnTravelDate is required for bookingType 1.');
                //     }
                // } else {
                // if()
                //     $validator->errors()->add('tripInfo must be an array for bookingType 1.');
                // }
            //     if (!$request->has('returnTravelDate')) {
            //         $validator->errors()->add('returnTravelDate', 'returnTravelDate is required for bookingType 1.');
            //     }

            //     $message = 'Round Trip Search Flights';
            // }
        // });

        // $bookingType = $request->input('bookingType');
       


    //     public function SearchFlight(Request $request)
    //     {
    //         $validator = Validator::make($request->all(), [
    
    //             "tripInfo" => "required|array",
    //             // Trip Information Validation
    //             "tripInfo.*.origin" => "required|string",
    //             "tripInfo.*.destination" => "required|string",
    //             "tripInfo.*.travelDate" => "required|date_format:m/d/Y",
    //             "tripInfo.*.tripId" => "nullable|string|in:0,1", 
    //             "travelType" => "required|string|in:0,1", // 0 for domestic, 1 for international
    //             "bookingType" => "required|string|in:0,1", // 0 for one way, 1 for round trip
    //             "adultCount" => "required|string",
    //             "childCount" => "required|string", 
    //             "infantCount" => "required|string",
    //             "classOfTravel" => "required|in:0,1,2,3",         
    //             "airlineCode" => "nullable|string",         
    //             "headersToken" => "required|string", 
    //             "headersKey" => "required|string",
    //         ]);
            
    //         // Check if validation fails
    //         if ($validator->fails()) {
    //             // Get all the error messages
    //             $errors = $validator->errors()->all(); // Capture all error messages
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => $errors[0], // Return the first error message
    //             ], 422);
    //         }
            
    //         // If validation succeeds, continue with the validated data
    //         $data = $validator->validated();
    
    //         $tripInfo=[];
    
    //         foreach ($data['tripInfo'] as $trip) {
    //             if (empty($trip['tripId'])) {
    //                 $trip['tripId'] = "0"; // Set default value
    //             }
    //             $tripInfo[]=$trip;
    //         }        
    
    //         $count=count($data['tripInfo']);
    //         $message = ($count === 1) ? 'One Way Trip Data' : 
    //         (($count === 2) ? 'Round Trip Data' : (($count > 2) ? 'Multi State Data' : 'Unknown Trip Data'));   
    
    //         if($data['bookingType'] === "1")
    //         {
    
    //             if($count != 2)
    //             {
    //                 return response()->json(['success'=>false,'message'=>'tripInfo should have 2 objects for bookingType 1'],400);
    //             }
    //             $tripInfo[1]['tripId'] = "1";
    //         }
            
    //         $data['tripInfo']=$tripInfo;
    //         // Return the validated 'tripInfo' data
            
    //         $payload = [
    //             "deviceInfo" => [
    //                 "ip" => "122.161.52.233",
    //                 "imeiNumber" => "12384659878976879887"
    //             ],
    //             "travelType" => $data['travelType'], // 0 for domestic, 1 for international
    //             "bookingType" => $data['bookingType'], // 0 for one way, 1 for round trip
    //             "tripInfo" => $data['tripInfo'],
    //             "adultCount" => $data['adultCount'],
    //             "childCount" => $data['childCount'],
    //             "infantCount" => $data['infantCount'],
    //             "classOfTravel" => $data['classOfTravel'], // Possible values: 0-ECONOMY/1-BUSINESS/2-FIRST/3-PREMIUM_ECONOMY
    //             "filteredAirLine" => [
    //                 "airlineCode" => $data['airlineCode'] ?? ''
    //             ]
    //         ];
    
    //         // return response()->json($payload);
    
    
    //         // Headers
    //         $headers = [
    //             'D-SECRET-TOKEN' => $data['headersToken'],
    //             'D-SECRET-KEY' => $data['headersKey'],
    //             'CROP-CODE' => 'DOTMIK160614',
    //             'Content-Type' => 'application/json',
    //         ];
    
    //         // API URL
    //         $url = 'https://api.dotmik.in/api/flightBooking/v1/searchFlight';
    
    //     try {
    //     // Make the POST request using Laravel HTTP Client
    //         $response = Http::withHeaders($headers)->post($url, $payload);
    //         $result=$response->json();
    //         $statusCode = $response->status();
    
    //         // return response()->json($result);
    
    
    //         if($result['status'] === false)
    //         {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => $result['message'],
    //                 'error' => $result
    //             ],$statusCode);   
    //         }
    //         else
    //         {   
    //             if ($response->successful()) 
    //             {
    //                 $dataa = $result['payloads']['data']['tripDetails'];
    //                 $payloads = [];  // Initialize an empty array for flights
    //                 $i = 1;  // Initialize the counter variable
                    
    //                 if($count > 2)
    //                 {
    //                     $dataa = $result['payloads']['data']['tripDetails'][0]['Flights'];
    
    //                     foreach($data['tripInfo'] as $trip)
    //                     {
    //                         $DATA=[];
    //                         $flightVar = "Flight" . $i;  
    
    //                         foreach($dataa as $flight)
    //                         {
    //                             if($trip['destination'] === $flight['Destination'])
    //                             {
    //                                 $DATA[]= $flight;
    //                             }
    //                         }
    //                         $payloads[$flightVar] = $DATA;  
    //                         $i++; 
    //                     }
    //                 }
    //                 else 
    //                 {
    //                     foreach($dataa as $data) {
    //                         $flightVar = "Flight" . $i;  
    //                         $payloads[$flightVar] = $data['Flights'];  
    //                         $i++;
    //                     }    
    //                 }
                    
                    
    //                 return response()->json([
    //                     'status' => true,
    //                     'message' => $message,
    //                     // 'count' => $count,
    //                     'status_code' => $result['status_code'],
    //                     'request_id' => $result['request_id'],
    //                     'SearchKey' => $result['payloads']['data']['searchKey'],
    //                     'payloads' => $payloads,
    //                 ], 200);
     
    //                 // return response()->json([
    //                 //     'status' => true,
    //                 //     'message' => 'Search Flight data',
    
    //                 //     'data' => $result
    //                 // ], 200);
    
    //             } else {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => $result['message'],
    //                     'error' => $result
    //                 ],$statusCode);
    //             }
    //     }
    // } catch (\Exception $e) {
    //     // Handle exception (e.g. network issues)
    //     return response()->json([
    //         'success' => false,
    //         'message' => $e->getMessage()
    //     ], 500);
    // }
    
    // }



//     public function SearchFlight(Request $request)
//     {
//         $validator = Validator::make($request->all(), [
//             "origin" => "required|string",
//             "destination" => "required|string",
//             "travelDate" => "required|date_format:m/d/Y", // Date format should be MM/DD/YYYY
//             "returnTravelDate" => "nullable|date_format:m/d/Y", // New field for return date
//             "travelType" => "required|in:0,1", // 0 for domestic and 1 for international
//             "bookingType" => "required|in:0,1,2", // 0 for one way, 1 for round trip
//             "adultCount" => "required|integer|min:0", // Change to integer for counts
//             "childCount" => "required|integer|min:0",
//             "infantCount" => "required|integer|min:0",
//             "classOfTravel" => "required|in:0,1,2,3", // Possible values: 0-ECONOMY/1-BUSINESS/2-FIRST/3-PREMIUM_ECONOMY
//             "airlineCode" => "nullable|string",

//             // for Header  
//             "headersToken" => "required|string",
//             "headersKey" => "required|string",

//             // for Filter
//             "Refundable" => "nullable|boolean",
//             'Arrival' => 'nullable|string',
//             'Departure' => 'nullable|string',
//         ]);

//         if ($validator->fails()) {
//             $errors = $validator->errors()->all(); // Get all error messages
//             $formattedErrors = [];

//             foreach ($errors as $error) {
//                 $formattedErrors[] = $error;
//             }

//             return response()->json([
//                 'success' => false,
//                 'message' => $formattedErrors[0]
//             ], 422);
//         }
        
//         $data = $validator->validated();

//         if ($data['bookingType'] === "1") {
//             if (!$request->input('returnTravelDate')) {
//                 // $validator->errors()->add('returnTravelDate', 'returnTravelDate is required for bookingType 1.');
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Please Provide returnTravelDate'
//                 ], 422);
//             }
//             $message = 'Round Trip Search Flights';
//         }

//         $payload = [
//             "deviceInfo" => [
//                 "ip" => "122.161.52.233",
//                 "imeiNumber" => "12384659878976879888"
//             ],
//             "travelType" => $data['travelType'], // 0 for domestic, 1 for international
//             "bookingType" => $data['bookingType'], // 0 for one way, 1 for round trip
//             "tripInfo" => $data['bookingType'] === "1" ? [
//                 [
//                     "origin" => $data['origin'],
//                     "destination" => $data['destination'],
//                     "travelDate" => $data['travelDate'],
//                     "tripId" => "0" // For the first trip
//                 ],
//                 [
//                     "origin" => $data['destination'], // Reverse trip
//                     "destination" => $data['origin'],
//                     "travelDate" => $data['returnTravelDate'], // Use return date if provided
//                     "tripId" => "1" // For return trip
//                 ]
//             ] : [
//                    [ "origin" => $data['origin'],
//                     "destination" => $data['destination'],
//                     "travelDate" => $data['travelDate'],
//                     "tripId" => "0" // Ongoing trip
//                    ]
//             ],
//             "adultCount" => $data['adultCount'],
//             "childCount" => $data['childCount'],
//             "infantCount" => $data['infantCount'],
//             "classOfTravel" => $data['classOfTravel'], // Possible values: 0-ECONOMY/1-BUSINESS/2-FIRST/3-PREMIUM_ECONOMY
//             "filteredAirLine" => [
//                 "airlineCode" => $data['airlineCode'] ?? ''
//             ]
//         ];

//         // return response()->json($payload);


//         // Headers
//         $headers = [
//             'D-SECRET-TOKEN' => $data['headersToken'],
//             'D-SECRET-KEY' => $data['headersKey'],
//             'CROP-CODE' => 'DOTMIK160614',
//             'Content-Type' => 'application/json',
//         ];

//         // API URL
//         $url = 'https://api.dotmik.in/api/flightBooking/v1/searchFlight';

//     try {
//     // Make the POST request using Laravel HTTP Client
//         $response = Http::withHeaders($headers)->post($url, $payload);
//         $result=$response->json();
//         $statusCode = $response->status();

//         if($result['status'] === false)
//         {
//             return response()->json([
//                 'success' => false,
//                 'message' => $result['message'],
//                 'error' => $result
//             ],$statusCode);   
//         }
//         else
//         {   
//             if ($response->successful()) 
//             {

//                 $dataa = $result['payloads']['data']['tripDetails'];
                
//                 // return response()->json($dataa);

//                 $flights = $dataa[0]['Flights']; // Flights is already an array
                
//                 // Filter flights based on Departure and Arrival times
//                 if(isset($data['Arrival']) && isset($data['Departure']))
//                 {

//                     if($data['Arrival'] === '12AM6AM')
//                     {  
//                         $arrivalDateTimeLow = "{$data['travelDate']} 00:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $arrivalDateTimeHigh = "{$data['travelDate']} 06:00";
//                     }
//                     else if($data['Arrival'] === '6AM12PM')
//                     {  
//                         $arrivalDateTimeLow = "{$data['travelDate']} 06:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $arrivalDateTimeHigh = "{$data['travelDate']} 12:00";
//                     }
//                     else if($data['Arrival'] === '12PM6PM')
//                     {   
//                         $arrivalDateTimeLow = "{$data['travelDate']} 12:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $arrivalDateTimeHigh = "{$data['travelDate']} 18:00";
//                     }
//                     else if($data['Arrival'] === '6PM12AM')
//                     {
//                         $arrivalDateTimeLow = "{$data['travelDate']} 18:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $arrivalDateTimeHigh = "{$data['travelDate']} 24:00";
//                     }
//                     else{
//                         return response()->json('Invalid Arrival time or condition.', 400);
//                     }

//                     if($data['Departure'] === '12AM6AM')
//                     {
//                         $departureDateTimeLow = "{$data['travelDate']} 00:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $departureDateTimeHigh = "{$data['travelDate']} 06:00";
//                     }
//                     else if($data['Departure'] === '6AM12PM')
//                     {
                        
//                         $departureDateTimeLow = "{$data['travelDate']} 06:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $departureDateTimeHigh = "{$data['travelDate']} 12:00";
//                     }
//                     else if($data['Departure'] === '12PM6PM')
//                     {   
//                         $departureDateTimeLow = "{$data['travelDate']} 12:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $departureDateTimeHigh = "{$data['travelDate']} 18:00";
//                     }
//                     else if($data['Departure'] === '6PM12AM')
//                     {
//                         $departureDateTimeLow = "{$data['travelDate']} 18:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $departureDateTimeHigh = "{$data['travelDate']} 24:00";
//                     }
//                     else{
//                         return response()->json('Invalid Arrival time or condition.', 400);
//                     }
                    
//                     $filteredSegments = collect($flights)->filter(function ($flight) use ( $arrivalDateTimeLow,$arrivalDateTimeHigh,$departureDateTimeLow,$departureDateTimeHigh) {
//                         return $flight['Segments'][0]['Arrival_DateTime'] >= $arrivalDateTimeLow && $arrivalDateTimeHigh >= $flight['Segments'][0]['Arrival_DateTime'] &&  $flight['Segments'][0]['Departure_DateTime'] >= $departureDateTimeLow &&  $flight['Segments'][0]['Departure_DateTime'] <= $departureDateTimeHigh;
//                     });

//                     $filteredFlights = $filteredSegments->all();

//                     $filteredFlights = array_values($filteredFlights); 
//                 }
//                 else if (isset($data['Arrival'])) 
//                 {                       
//                     if($data['Arrival'] === '12AM6AM')
//                     {
//                         $arrivalDateTimeLow = "{$data['travelDate']} 00:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $arrivalDateTimeHigh = "{$data['travelDate']} 06:00";
//                     }
//                     else if($data['Arrival'] === '6AM12PM')
//                     {
                        
//                         $arrivalDateTimeLow = "{$data['travelDate']} 06:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $arrivalDateTimeHigh = "{$data['travelDate']} 12:00";
//                     }
//                     else if($data['Arrival'] === '12PM6PM')
//                     {   
//                         $arrivalDateTimeLow = "{$data['travelDate']} 12:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $arrivalDateTimeHigh = "{$data['travelDate']} 18:00";
//                     }
//                     else if($data['Arrival'] === '6PM12AM')
//                     {
//                         $arrivalDateTimeLow = "{$data['travelDate']} 18:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $arrivalDateTimeHigh = "{$data['travelDate']} 24:00";
//                     }
//                     else{
//                         return response()->json('Invalid Arrival time or condition.', 400);
//                     }

//                     $filteredSegments = collect($flights)->filter(function ($flight) use ( $arrivalDateTimeLow,$arrivalDateTimeHigh) {
//                     return $flight['Segments'][0]['Arrival_DateTime'] >= $arrivalDateTimeLow && $arrivalDateTimeHigh >= $flight['Segments'][0]['Arrival_DateTime'];
//                     });

//                     $filteredFlights = $filteredSegments->all();

//                     $filteredFlights=array_values($filteredFlights);                    
//                 }
//                 else if (isset($data['Departure'])) 
//                 {                       
//                     if($data['Departure'] === '12AM6AM')
//                     {
//                         $departureDateTimeLow = "{$data['travelDate']} 00:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $departureDateTimeHigh = "{$data['travelDate']} 06:00";
//                     }
//                     else if($data['Departure'] === '6AM12PM')
//                     {
                        
//                         $departureDateTimeLow = "{$data['travelDate']} 06:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $departureDateTimeHigh = "{$data['travelDate']} 12:00";
//                     }
//                     else if($data['Departure'] === '12PM6PM')
//                     {   
//                         $departureDateTimeLow = "{$data['travelDate']} 12:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $departureDateTimeHigh = "{$data['travelDate']} 18:00";
//                     }
//                     else if($data['Departure'] === '6PM12AM')
//                     {
//                         $departureDateTimeLow = "{$data['travelDate']} 18:00";
//                         // return response()->json($arrivalDateTimeLow, 400);
//                         $departureDateTimeHigh = "{$data['travelDate']} 24:00";
//                     }
//                     else{
//                         return response()->json('Invalid Arrival time or condition.', 400);
//                     }

//                     $filteredSegments = collect($flights)->filter(function ($flight) use ( $departureDateTimeLow,$departureDateTimeHigh) {
//                     return $flight['Segments'][0]['Departure_DateTime'] >= $departureDateTimeLow && $departureDateTimeHigh >= $flight['Segments'][0]['Departure_DateTime'];
//                     });

//                     $filteredFlights = $filteredSegments->all();
//                     $filteredFlights=array_values($filteredFlights);

//                 } 
//                 else {
//                     $filteredFlights = $flights; // No filter, return all flights
//                     // return response()->json($filteredFlights[0]['Fares'][0]['Refundable'],400);
//                 }

//                 if(isset($data['Refundable']))
//                 {
//                     // return response()->json($filteredFlights['Fare']);
//                     $refundable = $data['Refundable'];
//                     $filtered = array_filter($filteredFlights, function($flight) use($refundable) {
//                         return $flight['Fares'][0]['Refundable'] === $refundable ;
//                     });
//                     $filtered=array_values($filtered);
//                 }
//                 else{
//                     // return response()->json('Helloji');
//                     $filtered = $filteredFlights;
//                 }

//                 // Extract distinct airline codes
//                 $airlineCodes = array_map(function($flight) {
//                     return $flight['Airline_Code'] ?? null; // Handle cases where 'Airline_Code' may not exist
//                 }, $filtered);

//                 // Remove null values and get distinct airline codes
//                 $distinctAirlineCodes = array_unique(array_filter($airlineCodes));

//                 // Re-index array to remove numeric keys
//                 $distinctAirlineCodes = array_values($distinctAirlineCodes);

//                 $count = count($filtered);

//                 $payloads = [
//                         'errors' => [],
//                         'data' => [
//                             'tripDetails' => [
//                                 [
//                                     'Flights' => $filtered
//                                 ]
//                             ]
//                         ]
//                 ];
                
//                 return response()->json([
//                     'status' => true,
//                     'message' => $message ?? 'One Way Flight Search',
//                     'count' => $count,
//                     'status_code' => $result['status_code'],
//                     'request_id' => $result['request_id'],
//                     'SearchKey' => $result['payloads']['data']['searchKey'],
//                     'AirlineCodes' =>  $distinctAirlineCodes,
//                     'payloads' => $payloads,
//                 ], 200);
//             } else {
//                 return response()->json([
//                     'success' => false,
//                     'message' => $result['message'],
//                     'error' => $result
//                 ],$statusCode);
//             }
//     }
// } catch (\Exception $e) {
//     // Handle exception (e.g. network issues)
//     return response()->json([
//         'success' => false,
//         'message' => $e->getMessage()
//     ], 500);
// }

// }



///

 // Filter flights based on Departure and Arrival times
                // if(isset($data['Arrival']) && isset($data['Departure']))
                // {
                //     $Filtered=[];

                //     if($data['Arrival'] === '12AM6AM')
                //     {  
                //         $arrivalDateTimeLow = "00:00";
                //         $arrivalDateTimeHigh = "06:00";
                //     }
                //     else if($data['Arrival'] === '6AM12PM')
                //     {  
                //         $arrivalDateTimeLow = "06:00";
                //         $arrivalDateTimeHigh = "12:00";
                //     }
                //     else if($data['Arrival'] === '12PM6PM')
                //     {   
                //         $arrivalDateTimeLow = "12:00";
                //         $arrivalDateTimeHigh = "18:00";
                //     }
                //     else if($data['Arrival'] === '6PM12AM')
                //     {
                //         $arrivalDateTimeLow = "18:00";
                //         $arrivalDateTimeHigh = "24:00";
                //     }
                //     else{
                //         return response()->json('Invalid Arrival time or condition.', 400);
                //     }
            
                //     if($data['Departure'] === '12AM6AM')
                //     {
                //         $departureDateTimeLow = "00:00";
                //         $departureDateTimeHigh = "06:00";
                //     }
                //     else if($data['Departure'] === '6AM12PM')
                //     {
                        
                //         $departureDateTimeLow = "06:00";
                //         $departureDateTimeHigh = "12:00";
                //     }
                //     else if($data['Departure'] === '12PM6PM')
                //     {   
                //         $departureDateTimeLow = "12:00";
                //         $departureDateTimeHigh = "18:00";
                //     }
                //     else if($data['Departure'] === '6PM12AM')
                //     {
                //         $departureDateTimeLow = "18:00";
                //         $departureDateTimeHigh = "24:00";
                //     }
                //     else{
                //         return response()->json('Invalid Arrival time or condition.', 400);
                //     }
                    
                //     foreach ($Flights as $filteration) {   
                //         // $lastSegmentDeparture = reset($filteration['Segments']);
                //         // $lastSegmentArrival = end($filteration['Segments']); // 
            
                //         $Departuredatetime = $lastSegmentDeparture['Departure_DateTime'];
                //         $dateObjD = new DateTime($Departuredatetime);
                //         $Dtime = $dateObjD->format('H:i');
            
                //         $Arrivaldatetime = $lastSegmentArrival['Arrival_DateTime'];
                //         $dateObjA = new DateTime($Arrivaldatetime);
                //         $Atime = $dateObjA->format('H:i');
                //         // return response()->json($time);
                //         if( $Dtime > $departureDateTimeLow && $Dtime < $departureDateTimeHigh && $Atime >  $arrivalDateTimeLow && $Atime < $arrivalDateTimeHigh  )
                //         {
                //             $Filtered[]=$filteration;
                //         }
                //     }
                //     $Flights=$Filtered;
             
                // }
                // else 









                // public function Filter(Request $request)
// {
//     $validator = Validator::make($request->all(),[
//         'DATA' => "required|array",
//         'Refundable' => "nullable|boolean",
//         'Arrival' => 'nullable|string',
//         'Stop' => 'nullable|string',
//         'Departure' => 'nullable|string',
//         'Airline' => 'nullable|string'
//     ]);   

//     if ($validator->fails()) {
//         $errors = $validator->errors()->all(); // Get all error messages
//         $formattedErrors = [];

//         foreach ($errors as $error) {
//             $formattedErrors[] = $error;
//         }

//         return response()->json([
//             'success' => false,
//             'message' => $formattedErrors[0]
//         ], 422);
//     }
    
//     $data=$validator->validated();

//     $FilteredFlights=$data['DATA'];

//     if(isset($data['Stop']))
//     {
//         $Filtered=[];

//         if($data['Stop'] === "0")
//         {
//           foreach ($FilteredFlights as $filteration) {                
  
//               $count = count($filteration['Segments']); // 
//               if($count === 1)
//               {
//                   $Filtered[]=$filteration; 
//               }
//           }
//           $FilteredFlights=$Filtered;
//         }
//         else if($data['Stop'] === "1")
//         {
//           foreach ($FilteredFlights as $filteration) {                
//               $count = count($filteration['Segments']); // 
//               if($count === 2)
//               {
//                   $Filtered[]=$filteration; 
//               }
//           }
//           $FilteredFlights=$Filtered;
//         }
//         else if($data['Stop'] === "2")
//         {
//           foreach ($FilteredFlights as $filteration) {                
//               $count = count($filteration['Segments']); // 
//               if($count > 2)
//               {
//                   $Filtered[]=$filteration; 
//               }
//           }
//           $FilteredFlights=$Filtered;
//         }  
//     }


//     if(isset($data['Arrival']) && isset($data['Departure']))
//     {
//         $Filtered=[];

//         if($data['Arrival'] === '12AM6AM')
//         {  
//             $arrivalDateTimeLow = "00:00";
//             $arrivalDateTimeHigh = "06:00";
//         }
//         else if($data['Arrival'] === '6AM12PM')
//         {  
//             $arrivalDateTimeLow = "06:00";
//             $arrivalDateTimeHigh = "12:00";
//         }
//         else if($data['Arrival'] === '12PM6PM')
//         {   
//             $arrivalDateTimeLow = "12:00";
//             $arrivalDateTimeHigh = "18:00";
//         }
//         else if($data['Arrival'] === '6PM12AM')
//         {
//             $arrivalDateTimeLow = "18:00";
//             $arrivalDateTimeHigh = "24:00";
//         }
//         else{
//             return response()->json('Invalid Arrival time or condition.', 400);
//         }

//         if($data['Departure'] === '12AM6AM')
//         {
//             $departureDateTimeLow = "00:00";
//             $departureDateTimeHigh = "06:00";
//         }
//         else if($data['Departure'] === '6AM12PM')
//         {
            
//             $departureDateTimeLow = "06:00";
//             $departureDateTimeHigh = "12:00";
//         }
//         else if($data['Departure'] === '12PM6PM')
//         {   
//             $departureDateTimeLow = "12:00";
//             $departureDateTimeHigh = "18:00";
//         }
//         else if($data['Departure'] === '6PM12AM')
//         {
//             $departureDateTimeLow = "18:00";
//             $departureDateTimeHigh = "24:00";
//         }
//         else{
//             return response()->json('Invalid Arrival time or condition.', 400);
//         }
        
//         foreach ($FilteredFlights as $filteration) {   
//             $lastSegmentDeparture = reset($filteration['Segments']);
//             $lastSegmentArrival = end($filteration['Segments']); // 

//             $Departuredatetime = $lastSegmentDeparture['Departure_DateTime'];
//             $dateObjD = new DateTime($Departuredatetime);
//             $Dtime = $dateObjD->format('H:i');

//             $Arrivaldatetime = $lastSegmentArrival['Arrival_DateTime'];
//             $dateObjA = new DateTime($Arrivaldatetime);
//             $Atime = $dateObjA->format('H:i');
//             // return response()->json($time);
//             if( $Dtime > $departureDateTimeLow && $Dtime < $departureDateTimeHigh && $Atime >  $arrivalDateTimeLow && $Atime < $arrivalDateTimeHigh  )
//             {
//                 $Filtered[]=$filteration;
//             }
//         }
//         $FilteredFlights=$Filtered;

//     }
//     else if (isset($data['Arrival'])) 
//     {     
//         $Filtered=[];

//         if($data['Arrival'] === '12AM6AM')
//         {
//             $arrivalDateTimeLow = "00:00";
//             // return response()->json($arrivalDateTimeLow, 400);
//             $arrivalDateTimeHigh = "06:00";
//         }
//         else if($data['Arrival'] === '6AM12PM')
//         {
            
//             $arrivalDateTimeLow = "06:00";
//             // return response()->json($arrivalDateTimeLow, 400);
//             $arrivalDateTimeHigh = "12:00";
//         }
//         else if($data['Arrival'] === '12PM6PM')
//         {   
//             $arrivalDateTimeLow = "12:00";
//             // return response()->json($arrivalDateTimeLow, 400);
//             $arrivalDateTimeHigh = "18:00";
//         }
//         else if($data['Arrival'] === '6PM12AM')
//         {
//             $arrivalDateTimeLow = "18:00";
//             // return response()->json($arrivalDateTimeLow, 400);
//             $arrivalDateTimeHigh = "24:00";
//         }
//         else{
//             return response()->json('Invalid Arrival time or condition.', 400);
//         }

//         foreach ($FilteredFlights as $filteration) {                
//             $lastSegment = end($filteration['Segments']); // 
//             $datetime = $lastSegment['Arrival_DateTime'];
//             $dateObj = new DateTime($datetime);
//             $time = $dateObj->format('H:i');
//             // return response()->json($time);
//             if($time >  $arrivalDateTimeLow && $time < $arrivalDateTimeHigh)
//             {
//                 $Filtered[]=$filteration;
//             }
//         }

//         $FilteredFlights=$Filtered;        
//         //   return response()->json($filtered);

//     }
//     else if (isset($data['Departure'])) 
//     {       
//         $Filtered=[];
                
//         if($data['Departure'] === '12AM6AM')
//         {
//             $departureDateTimeLow = "00:00";
//             // return response()->json($arrivalDateTimeLow, 400);
//             $departureDateTimeHigh = "06:00";
//         }
//         else if($data['Departure'] === '6AM12PM')
//         {
            
//             $departureDateTimeLow = "06:00";
//             // return response()->json($arrivalDateTimeLow, 400);
//             $departureDateTimeHigh = "12:00";
//         }
//         else if($data['Departure'] === '12PM6PM')
//         {   
//             $departureDateTimeLow = "12:00";
//             // return response()->json($arrivalDateTimeLow, 400);
//             $departureDateTimeHigh = "18:00";
//         }
//         else if($data['Departure'] === '6PM12AM')
//         {
//             $departureDateTimeLow = "18:00";
//             // return response()->json($arrivalDateTimeLow, 400);
//             $departureDateTimeHigh = "24:00";
//         }
//         else{
//             return response()->json('Invalid Arrival time or condition.', 400);
//         }

//         foreach ($FilteredFlights as $filteration) {                
//             $firstSegment = reset($filteration['Segments']); // 
//             $datetime = $firstSegment['Departure_DateTime'];
//             $dateObj = new DateTime($datetime);
//             $time = $dateObj->format('H:i');
//             // return response()->json($time);
//             if($time >  $departureDateTimeLow && $time < $departureDateTimeHigh)
//             {
//                 $Filtered[]=$filteration;
//             }
//         }
//         $FilteredFlights=$Filtered;
//     } 
    

//     if(isset($data['Refundable']))
//     {
//         $Filtered=[];
//         // return response()->json($filteredFlights['Fare']);
//         $refundable = $data['Refundable'];
//         $filtered = array_filter($FilteredFlights, function($flight) use($refundable) {
//             return $flight['Fares'][0]['Refundable'] === $refundable ;
//         });
//         $FilteredFlights=array_values($filtered);
//     }

//     // Extract distinct airline codes
//     $airlineCodes = array_map(function($flight) {
//         return $flight['Airline_Code'] ?? null; // Handle cases where 'Airline_Code' may not exist
//     }, $FilteredFlights);

//     // Remove null values and get distinct airline codes
//     $distinctAirlineCodes = array_unique($airlineCodes);

//     // Re-index array to remove numeric keys
//     $distinctAirlineCodes = array_values($distinctAirlineCodes);

//     $count=count($FilteredFlights);

//     return response()->json(
//         [
//             'success' => true,
//             'count' => $count,
//             'AirlineCode' =>  $distinctAirlineCodes,
//             'Flights' => $FilteredFlights
//         ],
//         200
//     );
// }

 // public function 

    // public function History(Request $request)
    // {
    //     $validator = Validator::make($request->all(),[
    //         'headersToken' => 'required|string',
    //         'headersKey' => 'required|string',
    //         "fromDate" => "required|string", //date in (MM/dd/YYYY)
    //         "toDate" => "required|string",  //date in (MM/dd/YYYY)
    //         "month" => "required|string", // Month of booking for July (eg: 07)
    //         "year" => "required|string", //year of booking(eg:2024)
    //         "type" => "required|string" //Possible values are 0 - BOOKING_DATE/ 1 - BOOKING_DATE_LIVE/ 2 - BOOKING_DATE_CANCELLED/ 3-BLOCKED
    //     ]);     

    //     if ($validator->fails()) {
    //         $errors = $validator->errors()->all(); // Get all error messages
    //         $formattedErrors = [];
    
    //         foreach ($errors as $error) {
    //             $formattedErrors[] = $error;
    //         }
    
    //         return response()->json([
    //             'success' => false,
    //             'message' => $formattedErrors[0]
    //         ], 422);
    //     }
        
    //     $data=$validator->validated();

    //     $payload = [
    //         "deviceInfo" => [
    //             "ip" => "122.161.52.233",
    //             "imeiNumber" => "12384659878976879887"
    //         ],
    //         "fromDate" => $data['fromDate'], //date in (MM/dd/YYYY)
    //         "todate" => $data['toDate'],  //date in (MM/dd/YYYY)
    //         "month" => $data['month'], // Month of booking for July (eg: 07)
    //         "year" => $data['year'], //year of booking(eg:2024)
    //         "type" => $data['type'] //
    //     ];
        
    //     // Headers
    //     $headers = [
    //         'D-SECRET-TOKEN' => $data['headersToken'],
    //         'D-SECRET-KEY' => $data['headersKey'],
    //         'CROP-CODE' => 'DOTMIK160614',
    //         'Content-Type' => 'application/json',
    //     ];

    //     // API URL
    //     $url = 'https://api.dotmik.in/api/flightBooking/v1/history';

    //     try {
    //         // Make the POST request using Laravel HTTP Client
    //         $response = Http::withHeaders($headers)->post($url, $payload);
    //         $result=$response->json();
    //         $statusCode = $response->status();

    //         if($result['status'] === false)
    //         {
    //             return response()->json($result,$statusCode);   
    //         }
    //         else{
    //             if($response->successful())
    //             {
    //                 return response()->json([
    //                     'success' => true,
    //                     'data' => $result,
    //                 ], 200);
    //             } else {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Failed to fetch flight data',
    //                     'error' => $response->json()
    //                 ], $response->status());
    //             }
    //         }
    //         //code...
    //     } catch  (\Exception $e) {
    //         // Handle exception (e.g. network issues)
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'An error occurred',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }   
    // }


 