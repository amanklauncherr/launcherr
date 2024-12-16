<?php

namespace App\Http\Controllers;

use App\Models\QueAndAns;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class QueAndAnsController extends Controller
{
    //
    /**
     * @group Question and Answer Management
     *
     * Add a Question and Answer
     *
     * This endpoint allows you to add a new question and answer pair.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @bodyParam Question string required The question text. Example: "What is Laravel?"
     * @bodyParam Answer string required The answer to the question. Example: "Laravel is a PHP framework for web artisans."
     *
     * @response 201 {
     *   'success': 1
     *   "message": "Question created"
     * }
     *
     * @response 422 {
     *   "errors": {
     *     "Question": [
     *       "The Question field is required."
     *     ],
     *     "Answer": [
     *       "The Answer field is required."
     *     ]
     *   }
     * }
     *
     * @response 500 {
     *   'success': 0
     *   "message": "An error occurred while creating the question",
     *   "error": "Detailed error message here"
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addQueAndAns(Request $request){
        $validator = Validator::make($request->all(), [
            'Question' => 'required|string',
            'Answer' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            QueAndAns::create($data);

            return response()->json(['success'=>1,'message' => 'Question created'], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success'=> 0,
                'message' => 'An error occurred while creating the question',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @group Question and Answer Management
     *
     * Update a Question and Answer
     *
     * This endpoint allows you to update an existing question and answer pair.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * @urlParam id int required The ID of the question to update. Example: 1
     * @bodyParam Question string The question text. Example: "What is Laravel?"
     * @bodyParam Answer string The answer to the question. Example: "Laravel is a PHP framework for web artisans."
     *
     * @response 200 {
     *   'success': 1
     *   "message": "Q&A updated successfully",
     *   "Q&A": {
     *     "id": 1,
     *     "Question": "Updated question text",
     *     "Answer": "Updated answer text"
     *   }
     * }
     *
     * @response 404 {
     *   'success': 0
     *   "message": "Record not found"
     * }
     *
     * @response 500 {
     *   'success': 0
     *   "message": "Error While Updating Q&A",
     *   "error": "Detailed error message here"
     * }
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */

    public function updateQueAndAns(Request $request,$id)
    {
        $validator=Validator::make($request->all(),[
            'Question' => 'sometimes|required|string',
            'Answer' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            //code...
            $QA= QueAndAns::findorFail($id);
            
                if ($request->filled('Question')) {
                    $QA->Question = $request->Question;
                }
        
                if ($request->filled('Answer')) {
                    $QA->Answer = $request->Answer;
                }

                $QA->save();

                return response()->json(['success'=>1,'message' => 'Q&A updated successfully', 'Q&A' => $QA], 200);
            

        }catch (ModelNotFoundException $e) {
            // Return a response if the record was not found
            return response()->json(['success'=>0,'message' => 'Record not found'], 404);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json([
                'success'=>0,
                'message' => 'Error While Updating Q&A',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @group Question and Answer Management
     *
     * Delete a Question and Answer
     *
     * This endpoint deletes a question and answer by its ID.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * 
     * @urlParam id int required The ID of the question to delete. Example: 1
     *
     * @response 200 {
     *  'success' : 1,
     *   "message" : "Record deleted successfully"
     * }
     *
     * @response 404 {
     *  'success':1,
     *   "message": "Record not found"
     * }
     *
     * @response 500 {
     *  'success':0,
     *   "message": "An error occurred while deleting the record",
     *   "error": "Detailed error message here"
     * }
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteQueAndAns($id)
    {
        try {
            // Find the record by ID or fail if it doesn't exist
            $queAndAns = QueAndAns::findOrFail($id);

            // Delete the record
            $queAndAns->delete();

            // Return a success response
            return response()->json(['success'=>1,'message' => 'Record deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            // Return a response if the record was not found
            return response()->json(['success'=>1,'message' => 'Record not found'], 404);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'success'=>0,
                'message' => 'An error occurred while deleting the record',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @group Question and Answer Management
     *
     * Show All Questions and Answers
     *
     * This endpoint retrieves all the question and answer pairs.
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "Question": "What is Laravel?",
     *     "Answer": "Laravel is a PHP framework for web artisans."
     *   },
     *   {
     *     "id": 2,
     *     "Question": "What is PHP?",
     *     "Answer": "PHP is a scripting language for web development."
     *   }
     * ]
     *
     * @response 404 {
     * ' success':0,
     *   "message": "No Questions found"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function showQueAndAns()
    {
        $data =QueAndAns::all() ;
        if ($data->isEmpty()) {
            return response()->json(['success'=>0,'message' => 'No Questions found'], 404);
        } else {
            return response()->json($data, 200);
        }
    }
}
