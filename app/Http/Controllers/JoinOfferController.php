<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\joinOffer;
use Illuminate\Support\Facades\Validator;

class JoinOfferController extends Controller
{
    //
    /**
     * @group Join Offer Management
     *
     * Add or Update Join Offer Section
     *
     * Adds a new Join Offer section or updates an existing one based on the section name. If the section already exists, it updates the record; otherwise, it creates a new one.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @bodyParam section string required The section name for the Join Offer. Example: "Discounts"
     * @bodyParam heading string required The main heading for the Join Offer section (required if section is new). Example: "Special Discounts for New Members"
     * @bodyParam sub_heading string optional The sub-heading for the Join Offer section, limited to 1500 characters. Example: "Join now and enjoy exclusive member discounts up to 50%."
     *
     * @response 201 {
     *   "success": 1,
     *   "message": "Join Offer Section created"
     * }
     * @response 200 {
     *   "success": 1,
     *   "message": "Join Offer Section updated"
     * }
     * @response 422 {
     *   "success": 0,
     *   "error": "The section field is required."
     * }
     * @response 500 {
     *   "success": 0,
     *   "message": "An error occurred while Adding or Updating Join Offer Section",
     *   "error": "Detailed error message here"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function addJoinOffer(Request $request){

        $sectionExists = joinOffer::where('section', $request->section)->exists();
       
        $validator = Validator::make(
            $request->all(),[
                'section' => $sectionExists ? 'required|string' : 'required|string|unique:join_offers,section',
                'heading' => $sectionExists ? 'nullable|string' : 'required|string',
                'sub_heading' =>'nullable|string|max:1500',
            ]);

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return response()->json([
                    'success' => 0,
                    'error' => $error
                ], 422);
            }

            try {
                //code...
                $data=$validator->validated();

                $sections= joinOffer::where('section',$request->section)->first();

                if($sections)
                {
                    $sections->update($data);
                    return response()->json(['message' => 'Join Offer Section updated'], 200);
                }else{
                    // $data['sub_heading'] = $request->input('sub_heading', " ");
                    joinOffer::create($data);
                    return response()->json(['message' => 'Join Offer Section created'], 201);
                }
            }catch (\Exception $e) {
                // Return a custom error response in case of an exception
                return response()->json([
                    "success" => 0,
                    'message' => 'An error occurred while Adding or Updating Join Offer Section',
                    'error' => $e->getMessage()
                ], 500);
            }

    }

    /**
     * @group Join Offer Management
     *
     * Get Join Offer Sections in a specific manner for front 
     *
     * Retrieves all Join Offer sections, including the main heading and its associated cards.
     *
     * The response contains a list of sections, with each section containing its heading and sub-heading. The "MainHeading" section will include additional card details.
     *
     * @response 200 {
     *   "heading": "Main Heading Example",
     *   "sub_heading": "Sub-heading for Main Heading",
     *   "Cards": [
     *     {
     *       "heading": "Card 1 Heading",
     *       "subheading": "Card 1 Sub-heading"
     *     },
     *     {
     *       "heading": "Card 2 Heading",
     *       "subheading": "Card 2 Sub-heading"
     *     }
     *   ]
     * }
     * @response 404 {
     *   "message": "No sections found"
     * }
     * @response 500 {
     *   "message": "An error occurred while fetching sections",
     *   "error": "Detailed error message here"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showJoinOffer()
    {
        try 
        {
            // Fetch all sections
            $sections = joinOffer::all();
    
            // Check if sections exist
            if ($sections->isEmpty()) {
                return response()->json(['success' => 0, 'message' => 'No sections found'], 404);
            }
    
            $sectionsArray = [];
            $mainHeading = null;
            
            foreach ($sections as $section) {
                if ($section->section === 'MainHeading') {
                    $mainHeading = [
                        'heading' => $section->{'heading'} === null ? "" : $section->{'heading'},
                        'sub_heading' => $section->{'sub_heading'} === null ? "" : $section->{'sub_heading'},
                        'Cards' => []
                    ];
                } else {
                    $sectionsArray[$section->section] = [
                        'heading' => $section->heading,
                        'sub_heading' => $section->{'sub_heading'} === null ? "" : $section->{'sub_heading'},
                    ];
                }
            }
            
            // Convert the sectionsArray to the desired Cards format
            $cards = [];
            foreach ($sectionsArray as $key => $value) {
                $cards[] = [
                    'heading' => $value['heading'],
                    'subheading' => $value['sub_heading']
                ];
            }
            
            // Add the cards to the mainHeading
            if ($mainHeading) {
                $mainHeading['Cards'] = $cards;
            }
            
            return response()->json($mainHeading,200);
                
            // return response()->json($sectionsArray, 200);    
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => 'An error occurred while fetching sections',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @group Join Offer Management
     *
     * Retrieve All Join Offer Cards for Admin 
     *
     * This endpoint retrieves all the Join Offer cards for administrative purposes. It returns all records in the join offers table.
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * 
     * @response 200 {
     *   'success': 0
     *   "Cards": [
     *     {
     *       "id": 1,
     *       "section": "Main Section",
     *       "heading": "Exclusive Offer",
     *       "sub_heading": "Get 50% off on your first purchase",
     *       "created_at": "2024-11-12T10:00:00.000000Z",
     *       "updated_at": "2024-11-12T10:00:00.000000Z"
     *     },
     *     {
     *       "id": 2,
     *       "section": "Seasonal Offer",
     *       "heading": "Winter Sale",
     *       "sub_heading": "Enjoy discounts this winter season",
     *       "created_at": "2024-11-12T10:00:00.000000Z",
     *       "updated_at": "2024-11-12T10:00:00.000000Z"
     *     }
     *   ]
     * }
     *
     * @response 404 {
     *   'success" " 0
     *   "Message": "No Join Card Found"
     * }
     *
     * @response 500 {
     *   'success" " 0
     *   "message": "An error occurred while fetching Join Offer cards",
     *   "error": "Detailed error message here"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function showJoinOfferAdmin()
    {
        try {
            //code...
            $cards=joinOffer::all();
            if($cards->isEmpty()){
                return response()->json(['success'=>0, 'Message'=>'No Join Card Found'],404);
            }
            return response()->json(['success'=> 1, 'Cards'=>$cards],200);
        } catch (\Exception $e) {
            return response()->json([
                'success'=> 0,
                "message" => "An error occurred while fetching Join Offer cards",
                'error' => $e->getMessage()
            ], 500);
        }

    }

}
