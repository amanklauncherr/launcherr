<?php

namespace App\Http\Controllers;

use App\Models\Section;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Sections extends Controller
{

    /**
     * @group Section Management
     *
     * Add or Update Section
     *
     * This endpoint adds a new section if it does not exist, or updates it if it does.
     * - If the `section` already exists, only `heading` and `sub-heading` are optional.
     * - Otherwise, all fields are required.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @bodyParam section string required The section name. Example: "About Us"
     * @bodyParam heading string optional The section heading, required if section does not exist. Example: "Welcome to Our Company"
     * @bodyParam sub-heading string optional The section sub-heading, max length 1500 characters, required if section does not exist. Example: "Our mission is to provide..."
     *
     * @response 200 {
     *    "success" : 1
     *    "message": "Section updated"
     * }
     * @response 201 {
     *     "success" : 1
     *    "message": "Section created"
     * }
     * @response 422 {
     *    "success": 0,
     *    "error": "Validation error message here"
     * }
     * @response 500 {
     *    "success" : 0
     *    "message": "An error occurred while Adding or Updating Section",
     *    "error": "Detailed error message here"
     * }
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addSection(Request $request){

        $sectionExists = Section::where('section', $request->section)->exists();
       
        $validator = Validator::make(
            $request->all(),[
                'section' => $sectionExists ? 'required|string' : 'required|string|unique:sections',
                'heading' => $sectionExists ? 'nullable|string' : 'required|string' ,
                'sub-heading' => $sectionExists ? 'nullable|string|max:1500' : 'required|string|max:1500',
            ]);

            if ($validator->fails()) {
                $error = $validator->errors()->first(); // Get the first error message
                return response()->json([
                    'success' => 0,
                    'error' => $error
                ], 422);
            }            

            try {
                //code...
                $data=$validator->validated();

                $sections= Section::where('section',$request->section)->first();

                if($sections)
                {
                    $sections->update($data);
                    return response()->json(["success" => 1, 'message' => 'Section updated'], 200);
                }else{
                    $data['sub-heading'] = $request->input('sub-heading', 'null');
                    Section::create($data);
                    return response()->json([ "success" => 1, 'message' => 'Section created'], 201);
                }
            }catch (\Exception $e) {
                // Return a custom error response in case of an exception
                return response()->json([
                    "success" => 0,
                    'message' => 'An error occurred while Adding or Updating Section',
                    'error' => $e->getMessage()
                ], 500);
            }

    }


    /**
     * @group Section Management
     *
     * Show All Sections
     *
     * Retrieves all sections. Returns a 404 error if no sections are found.
     *
     * @response 200 {
     *   "About Us": {
     *       "heading": "Welcome to Our Company",
     *       "sub-heading": "Our mission is to provide..."
     *   },
     *   "Services": {
     *       "heading": "What We Offer",
     *       "sub-heading": ""
     *   }
     * }
     * @response 404 {
     *   "success": 0,
     *   "message": "No sections found"
     * }
     * @response 500 {
     *   "success": 0,
     *   "message": "An error occurred while fetching sections",
     *   "error": "Detailed error message here"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showSection()
    {
        try {
            // Fetch all sections
            $sections = Section::all();
            // Check if sections exist
            if ($sections->isEmpty()) {
                return response()->json(["success" => 0,'message' => 'No sections found'], 404);
            }
    
            $sectionsArray = [];
            foreach ($sections as $section) {
                $sectionsArray[$section->section] = [
                    'heading' => $section->heading,
                    'sub-heading' => $section->{'sub-heading'} == null ? "" : $section->{'sub-heading'} 
                ];
            }
    
            return response()->json($sectionsArray, 200);    
    
        } catch (\Exception $e) {
            return response()->json([
                "success" => 0,
                'message' => 'An error occurred while fetching sections',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
