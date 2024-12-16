<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Models\Card;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class AboutController extends Controller
{
    //
    /**
     * @group About Section Management
     *
     * Add or Update About Section
     *
     * This endpoint allows you to add or update the About section. If an About section already exists, it updates the existing record; otherwise, it creates a new one.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @bodyParam heading string required The heading of the About section. Required only if About section does not exist. Example: "Welcome to Our Company"
     * @bodyParam content string required The content of the About section. Required only if About section does not exist. Example: "Our company was founded to provide exceptional service."
     *
     * @response 201 {
     *   'success': 1
     *   "message": "About Created",
     *   "About": {
     *     "id": 1,
     *     "heading": "Welcome to Our Company",
     *     "content": "Our company was founded to provide exceptional service.",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     *
     * @response 422 {
     *   "errors": {
     *     "heading": [
     *       "The heading field is required."
     *     ],
     *     "content": [
     *       "The content field is required."
     *     ]
     *   }
     * }
     *
     * @response 500 {
     *   'success': 0
     *   "message": "An error occurred while Adding or Updating About info",
     *   "error": "Detailed error message here"
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addAbout(Request $request)
    {
       $about=About::first();
       // return response()->json(['about'=>$about]);
       $validator=Validator::make($request->all(),[
            'heading' => $about ? 'nullable|string' : 'required|string',
            'content' => $about ? 'nullable|string' : 'required|string',        
            // 'url' =>  $about ? 'nullable|url' : 'required|url',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data=$validator -> validated();
            // $about = About::first();
    
            if($about){
                $about->update($data);
                return response()->json(['success'=>1,'message' => 'About updated','About'=>$about], 201);
            }else{
                $aboutCreated=About::create($data);
                return response()->json(['success'=>1,'message' => 'About Created','About'=>$aboutCreated], 201);
            }
        } catch (\Exception $e) {
            //throw $th;
            return response()->json([
                'message' => 'An error occurred while Adding or Updating About info',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @group About Section Management
     *
     * Show About Section
     *
     * This endpoint retrieves the About section information, including cards with images.
     *
     * @response 200 {
     *   "heading": "Welcome to Our Company",
     *   "content": "Our company was founded to provide exceptional service.",
     *   "url": "https://example.com/about",
     *   "Cards": [
     *     {
     *       "Card_No": 1,
     *       "Card_Heading": "Our Mission",
     *       "Card_Subheading": "Delivering Excellence",
     *       "Card_Image": "https://res.cloudinary.com/douuxmaix/image/upload/v1720289812/m7pzdvuezcuetbrzqlek.png"
     *     },
     *     {
     *       "Card_No": 2,
     *       "Card_Heading": "Our Vision",
     *       "Card_Subheading": "Innovate for the Future",
     *       "Card_Image": "https://res.cloudinary.com/douuxmaix/image/upload/v1720289779/vz2x9n2ualplwvlg0f9m.png"
     *     }
     *   ]
     * }
     *
     * @response 404 {
     *   "message": "No About section found"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function showAbout()
    {
        $terms =About::first();
        $cards = Card::get();
        $url=
        [
            "https://res.cloudinary.com/douuxmaix/image/upload/v1720289812/m7pzdvuezcuetbrzqlek.png",
            "https://res.cloudinary.com/douuxmaix/image/upload/v1720289779/vz2x9n2ualplwvlg0f9m.png",
            "https://res.cloudinary.com/douuxmaix/image/upload/v1720289651/jvmktrilyvzbl37mucxd.png",
        ];


        if ($terms && $cards) {
            $cardArray = [];
    
            // Loop through cards and assign URLs sequentially
            foreach ($cards as $index => $card) {
                $cardArray[] = [
                    "Card_No" => $card['Card_No'],
                    "Card_Heading" => $card['Card_Heading'],
                    "Card_Subheading" => $card['Card_Subheading'],
                    "Card_Image" => isset($url[$index]) ? $url[$index] : null,
                ];
            }
    
            return response()->json([
                'heading' => $terms->heading,
                'content' => $terms->content,
                'url' => $terms->url,
                'Cards' => $cardArray
            ], 200);
        } else {
            return response()->json(['message' => 'No About section found'], 404);
        }
    }
}
