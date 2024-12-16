<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\NewsletterSubscriptionConfirmation;
use App\Models\QuizResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuizResponseController extends Controller
{
    //
    /**
     * @group Quiz Management
     *
     * API to add a quiz response. Validates the user input, saves the data, and sends a confirmation email.
     *
     * @bodyParam name string required Name of the user. Example: John Doe
     * @bodyParam email string required Email address of the user. Must be unique. Example: johndoe@example.com
     * @bodyParam phone string required Phone number of the user. Must be unique. Example: +1234567890
     * @bodyParam answer1 string required Answer to the first quiz question. Example: Option A
     * @bodyParam answer2 string required Answer to the second quiz question. Example: Option B
     * @bodyParam answer3 string required Answer to the third quiz question. Example: Option C
     *
     * @response 201 {
     *  'success' : 0,
     *  "message": "Quiz Answered Properly",
     *  "User": {
     *      "name": "John Doe",
     *      "email": "johndoe@example.com",
     *      "phone": "+1234567890"
     *  },
     *  "Answer": {
     *      "Answer1": "Option A",
     *      "Answer2": "Option B",
     *      "Answer3": "Option C"
     *  }
     * }
     * 
     * @response 422 {
     *   "success": 0,
     *   "error": "The email has already been taken."
     * }
     *
     * @response 500 {
     *  'success' : 0,
     *  "message": "An error occurred while Adding or Updating About info",
     *  "error": "Error message details"
     * }
     */
    public function AddQuiz(Request $request)
    {
        $validator= Validator::make($request->all(),[
            'name' => 'required|string',
            'email' => 'required|email|unique:quiz_responses,email',
            'phone' => 'required|string|unique:quiz_responses,phone',
            'answer1' => 'required|string',
            'answer2' => 'required|string',
            'answer3' => 'required|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all(); // Get all error messages
            $formattedErrors = [];
            foreach ($errors as $error) {
                $formattedErrors[] = $error;
            }
            return response()->json([
                'success' => 0,
                'error' => $formattedErrors[0]
            ], 422);
        }
        
        try {
            //code...
            $data=$validator->validated();
            $savedAns=QuizResponse::create($data);        
            if(!$savedAns)
            {
                return response()->json(['success' => 0,'message'=>'Answer Not saved. Some Error might occur'],400);
            }else{
                Mail::to($request->email)->send(new NewsletterSubscriptionConfirmation($request->name));
                return response()->json([
                    'success' => 1,
                    'message' => 'Quiz Answered Properly ',
                    'User'=>[
                        'name'=>$savedAns->name,
                        'email'=>$savedAns->email,
                        'phone'=>$savedAns->phone],
                    'Answer'=>[
                        'Answer1'=>$savedAns->answer1,
                        'Answer2'=>$savedAns->answer2,
                        'Answer3'=>$savedAns->answer3]], 201);
            }
        } catch (\Exception $e) {
            //throw $th;
            return response()->json([
                'success' => 0,
                'message' => 'An error occurred while Adding or Updating About info',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @group Quiz Management
     *
     * API to retrieve all quiz responses. Returns an array of responses.
     *
     * @response 200 [
     *  {
     *      "id": 1,
     *      "name": "John Doe",
     *      "email": "johndoe@example.com",
     *      "phone": "+1234567890",
     *      "answer1": "Option A",
     *      "answer2": "Option B",
     *      "answer3": "Option C",
     *      "created_at": "2024-11-14T12:00:00.000Z",
     *      "updated_at": "2024-11-14T12:00:00.000Z"
     *  }
     * ]
     *
     * @response 404 {
     *   'success'=>0
     *   "message": "No Quiz Response found"
     * }
     */

    public function ShowQuiz()
    {
        $result=QuizResponse::all();
        
        if($result->isEmpty())
        {
            return response()->json(['success'=>0,'message' => 'No Quiz Response found'], 404);        
        }
        return response()->json($result,200);
    }
}