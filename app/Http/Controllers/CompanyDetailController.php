<?php

namespace App\Http\Controllers;

use App\Models\CompanyDetails;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class CompanyDetailController extends Controller
{
    //
    /**
     * @group Company Details Management
     *
     * Add or Update Company Details
     *
     * This endpoint allows you to add or update the company's details. If company details already exist, the fields become optional; otherwise, they are required for creating a new entry.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @bodyParam company_name string required The name of the company (required if no details exist). Example: "ABC Corp"
     * @bodyParam company_email string required The email address of the company (required if no details exist). Example: "info@abccorp.com"
     * @bodyParam company_contact string required The contact number of the company (required if no details exist). Example: "+1234567890"
     * @bodyParam company_timing string required The business hours or timing of the company (required if no details exist). Example: "9 AM - 6 PM"
     *
     * @response 200 {
     *  'success': 1
     *   "message": "Company Details updated"
     * }
     *
     * @response 201 {
     *   'success': 1
     *   "message": "Company Details Created"
     * }
     *
     * @response 422 {
     *   "errors": {
     *     "company_name": [
     *       "The company_name field is required."
     *     ],
     *     "company_email": [
     *       "The company_email field is required."
     *     ]
     *   }
     * }
     *
     * @response 500 {
     *   'success': 0
     *   "message": "An error occurred while Adding or Updating Company Details",
     *   "error": "Detailed error message here"
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addDetail(Request $request)
    {
        $companyDetails=CompanyDetails::first();
        // return response()->json(['cd'=>$companyDetails]);
        $validator = Validator::make($request->all(), [
            'company_name' => $companyDetails ? 'nullable:string':'required|string',
            'company_address' => $companyDetails ? 'nullable:string':'required|string',
            'company_email' => $companyDetails ? 'nullable:email':'required|email',
            'company_contact' => $companyDetails ? 'nullable:string':'required|string',
            'company_timing' => $companyDetails ? 'nullable:string':'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            //code...
            $data = $validator -> validate();
            $companyDetails = CompanyDetails::first();

            if ($companyDetails) {
                $companyDetails->update($data);
                return response()->json(['success'=>1,'message' => 'Company Details updated'], 200);
            } else {
                CompanyDetails::create($data);
                return response()->json(['success'=>1,'message' => 'Company Details Created'], 201);
            }
        } catch (\Exception $e) {
            // Return a custom error response in case of an exception
            return response()->json([
                'success' => 0,
                'message' => 'An error occurred while Adding or Updating Company Details',
                'error' => $e->getMessage()
            ], 500);
        }
        }

    /**
     * @group Company Details Management
     *
     * Show Company Details
     *
     * This endpoint retrieves the existing company details. If no details are found, a message is returned.
     *
     * @response 200 {
     *   "company_name": "ABC Corp",
     *   "company_email": "info@abccorp.com",
     *   "company_contact": "+1234567890",
     *   "company_timing": "9 AM - 6 PM"
     * }
     *
     * @response 404 {
     *   "message": "No Details found"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showDetail()
    {
        $details=CompanyDetails::first();
        if($details)
        {
            return response()->json($details,200);
        }
        else {
            return response()->json(['message' => 'No Details found'], 404);
        }
    }
}





