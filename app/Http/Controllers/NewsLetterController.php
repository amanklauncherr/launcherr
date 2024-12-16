<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\NewsletterSubscriptionConfirmation;
use App\Http\Controllers\Controller;
use App\Models\NewsLetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsLetterController extends Controller
{
    //
    /**
     * @group Newsletter Management
     *
     * API to add a new email address to the newsletter subscription list. A confirmation email is sent upon successful addition.
     *
     * @bodyParam email string required Email address to be subscribed to the newsletter. Must be unique. Example: johndoe@example.com
     *
     * @response 201 {
     *  'success' : 1,
     *  "message": "Email Added Successfully"
     * }
     *
     * @response 422 {
     *   "errors": {
     *       "email": [
     *           "The email field is required.",
     *           "The email must be a valid email address.",
     *           "The email has already been taken."
     *       ]
     *   }
     * }
     *
     * @response 500 {
     *   'success' : 0,
     *   "message": "An error occurred while Adding Email",
     *   "error": "Error message details"
     * }
     */
    public function AddEmail(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'email'=> 'required|email|max:35|unique:news_letters'
        ]);
        if($validator->fails())
        {
            return response()->json([
                'errors' => $validator->errors()
                ], 422);
        }
        try {
            $data = $validator->validated();
    
            // Send confirmation email (using a try-catch block for potential exceptions)
            Mail::to($request->email)->send(new NewsletterSubscriptionConfirmation());

            NewsLetter::create($data);
    
            return response()->json([
                'success' => 1,
                'message' => 'Email Added Successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => 'An error occurred while Adding Email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @group Newsletter Management
     *
     * API to retrieve all email addresses subscribed to the newsletter.
     *
     * @response 200 [
     * 
     *  {
     *      "id": 1,
     *      "email": "johndoe@example.com",
     *      "created_at": "2024-11-14T12:00:00.000Z",
     *      "updated_at": "2024-11-14T12:00:00.000Z"
     *  }
     * ]
     *
     * @response 404 {
     *  'success': 0
     *   "message": "No Quiz Response found"
     * }
     */

    public function ShowEmail(Request $request)
    {
        $result =NewsLetter::all();
        if($result->isEmpty())
        {
            return response()->json(['success'=>0,'message' => 'No Quiz Response found'], 404);        
        }
        return response()->json($result,200);
    }
}
