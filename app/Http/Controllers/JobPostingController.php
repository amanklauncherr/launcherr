<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use App\Models\JobPosting;
use App\Models\Enquiry;
// use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class JobPostingController extends Controller
{

    public function empProfile(Request $request,$user_id){
        $employer=EmployerProfile::where('user_id',$user_id)->first();
        if(!$employer){
            return response()->json(['jobs' =>'Not Found'], 404);
        }
        return response()->json(['profile' => $employer], 200);
    }

    //
    /**
     * @group Job Management
     * 
     * Show all Job Postings (Admin)
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}*
     * 
     * @return \Illuminate\Http\JsonResponse
     * @response 404 {
     *   "success": 0, 
     *   "error": "No Gigs Added"
     * }
     * @response 200 {
     * "success": 1, 
     * "message": 'Gigs List',
     * "job": [{"id": 1, "title": "Job Title", ...}]}
     */

    public function showJobAdmin()
    {
        $job = JobPosting::with(['user'])->get();
        // $employer=EmployerProfile::get();
        if($job->isEmpty())
        {
            return response()->json(
                [
                    "success"=> 0, 
                    'error'=>'No Gigs Added'
                ],404);
        }
        return response()->json([
            "success"=>1,
            "message" => 'Gigs List',
            'job'=>$job
        ],200);
    }

    /**
     * @group Job Management

     * Show details for a specific job.
     *
     * This endpoint retrieves details for a specific job based on the provided job ID.
     * If the authenticated user is of type 'user', it will check if they have applied for this job.
     * 
     * @urlParam id integer required The ID of the job to retrieve. Example: 1
     * 
     * @response 200 {
     *     "success": 1,
     *     "message": "Gigs List",
     *     "job": {
     *         "user_id": 10,
     *         "gig_id": 1,
     *         "gigs_title": "Web Developer",
     *         "gigs_description": "We are looking for a skilled Web Developer...",
     *         "gigs_Shortdescription": "Build responsive websites",
     *         "gigs_duration": "3 months",
     *         "isActive": true,
     *         "isVerified": true,
     *         "company_name": "By Launcherr",
     *         "company_image": "https://res.cloudinary.com/douuxmaix/image/upload/v1720553096/jhnimqyeodio3jqgxbp0.jpg",
     *         "company_description": "",
     *         "gigs_location": "New York",
     *         "isApplied": false,
     *         "gigs_badge": "Top-rated"
     *     }
     * }
     * 
     * @response 404 {
     *     "success": 0,
     *     "message": "Gigs not found"
     * }
     *
     * @response 500 {
     *     "success": 0,
     *     "message": "An error occurred while Adding or Updating About info",
     *     "error": "Error message details"
     * }
     */

  
    public function showJob(Request $request)
    {        
        try {
 
            $params=$request->id;
            $tokenType = $request->attributes->get('token_type');
            $user = $request->attributes->get('user');
            $gigList = [];
    
            if ($tokenType === 'user' && $user) {
                // If user is authenticated, get their gig enquiries
                $gigEnquiry = Enquiry::where('userID', $user->id)->get();
                $gigList = $gigEnquiry->pluck('gigID')->toArray();
            }
    
            $query = JobPosting::with(['user.employerProfile'])->where('id',$params)->get();

            if ($query->isEmpty()) {
                return response()->json([
                    "success"=> 0,
                    'message' => 'Gigs not found'
                ], 404);
            }    

            $isApplied = in_array($params, $gigList);

            return response()->json(
                [
                    "success"=>1,
                    "message" => 'Gigs List',
                    'job' =>[
                        'user_id' => $query[0]->user_id,
                        'gig_id' => $query[0]->id,
                        'gigs_title' => $query[0]->title,
                        'gigs_description' => $query[0]->description,
                        'gigs_Shortdescription' => $query[0]->short_description,
                        'gigs_duration' => $query[0]->duration,
                        'isActive' => $query[0]->active,
                        'isVerified' => $query[0]->verified,
                        'company_name' => ($query[0]->user->id == env('AdminID')) ? 'By Launcherr' : $query[0]->user->employerProfile->company_name,
                        'company_image'=>($query[0]->user->id == env('AdminID')) ?  'https://res.cloudinary.com/douuxmaix/image/upload/v1720553096/jhnimqyeodio3jqgxbp0.jpg' : $query[0]->user->employerProfile->image,
                        'company_description'=>($query[0]->user->id == env('AdminID')) ? '' : $query[0]->user->employerProfile->about,
                        'gigs_location' => $query[0]->location,
                        'isApplied' => $tokenType === 'user' ? ($isApplied ? true : false) : null,
                        'gigs_badge' => $query[0]->badge,
                    ]
                ],200);
        }  catch (\Exception $e) {
            return response()->json([
                "success"=>0,
                'message' => 'An error occurred while Adding or Updating About info',
                'error' => $e->getMessage()
            ], 500);
        }
    }
        
    /**
     * 
     * @group Job Management
     * 
     * Add a new job posting.
     *
     * This endpoint allows an authenticated employer to add a new job posting. The job details such as title, description, 
     * short description, duration, location, and additional settings like active, verified, and badge status can be provided.     *
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * 
     * @bodyParam title string required The title of the job. Example: "Software Engineer"
     * @bodyParam description string The full description of the job. Example: "Looking for a skilled developer..."
     * @bodyParam short_description string A short summary of the job. Example: "Develop web applications"
     * @bodyParam duration integer required The duration of the job in months. Example: 6
     * @bodyParam active boolean The active status of the job. Default: false. Example: true
     * @bodyParam verified boolean The verified status of the job. Default: false. Example: true
     * @bodyParam location string required The location of the job. Example: "New York"
     * @bodyParam badge boolean Indicates if the job is featured or top-rated. Default: true. Example: true
     *
     * @response 201 {
     *     "id": 1,
     *     "user_id": 10,
     *     "title": "Software Engineer",
     *     "description": "Looking for a skilled developer...",
     *     "short_description": "Develop web applications",
     *     "duration": 6,
     *     "active": true,
     *     "verified": false,
     *     "location": "New York",
     *     "badge": true,
     *     "created_at": "2024-11-14T10:00:00.000000Z",
     *     "updated_at": "2024-11-14T10:00:00.000000Z"
     * }
     *
     * @response 422 {
     *     "errors": {
     *         "title": ["The title field is required."],
     *         "duration": ["The duration field is required."],
     *         "location": ["The location field is required."]
     *     }
     * }
     * 
     * @response 500 {
     *     "success": 0,
     *     "message": "An error occurred while Adding Coupon",
     *     "error": "Error message details"
     * }
     */
   
    public function AddJob(Request $request)
    {
        // jobExist=JobPosting::where('');
        $validator=Validator::make($request->all(),[
          'title' => 'required|string|max:50',
          'description' => 'nullable|string',
          'short_description' => 'nullable|string',
          'duration' => 'required|integer',
          'active' => 'boolean',
          'verified' => 'boolean',
          'location' => 'required|string',
          'badge' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            //code...
            $user = Auth::user();

            $jobData = $request->only(['title', 'description','short_description','duration', 'active', 'verified','location','badge']);

            if (!isset($jobData['active'])) {
                $jobData['active'] = false;
            }
            if (!isset($jobData['verified'])) {
                $jobData['verified'] = false;
            }
            if (!isset($jobData['badge'])) {
                $jobData['badge'] = true;
            }

            $newEmployer = JobPosting::create([
                'user_id' => $user->id,
                'title' => $jobData['title'],
                'description' => $jobData['description'],
                'short_description' => $jobData['short_description'],
                'duration' => $jobData['duration'],
                'active' => $jobData['active'],
                'verified' => $jobData['verified'],
                'location' => $jobData['location'],
                'badge' => $jobData['badge'],
            ]);
            return response()->json($newEmployer, 201);
        } catch (\Exception $e) {
            // Return a custom error response in case of an exception
            return response()->json([
                "success"=> 0,
                'message' => 'An error occurred while Adding Coupon',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @group Job Management
     * 
     * Update an existing job posting.
     *
     * This endpoint allows the authenticated employer to update an existing job posting by ID. 
     * 
     * Only the fields provided in the request will be updated.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * 
     * @urlParam id integer required The ID of the job posting to update. Example: 1
     * 
     * @bodyParam title string The updated title of the job. Required if job does not exist. Example: "Senior Software Engineer"
     * @bodyParam description string The updated full description of the job. Example: "Seeking an experienced developer..."
     * @bodyParam short_description string The updated short summary of the job. Example: "Develop large-scale applications"
     * @bodyParam duration integer The duration of the job in months. Required if job does not exist. Example: 12
     * @bodyParam location string The location of the job. Required if job does not exist. Example: "Remote"
     *
     * @response 201 {
     *     "Message": "Gig Update"
     * }
     *
     * @response 404 {
     *     "Message": "Gig Not Found"
     * }
     *
     * @response 422 {
     *     "errors": {
     *         "title": ["The title field is required when updating a new job."],
     *         "duration": ["The duration field is required when updating a new job."],
     *         "location": ["The location field is required when updating a new job."]
     *     }
     * }
     * 
     * @response 500 {
     *     "message": "An error occurred while updating Coupon",
     *     "error": "Error message details"
     * }
     */

    public function updateJob(Request $request,$id)
    {
        $jobExist=JobPosting::where('id',$id)->exists();
        $validator=Validator::make($request->all(),[
          'title' => $jobExist ? 'nullable|string|max:100' : 'required|string|max:100',
          'description' => 'nullable|string',
          'short_description' => 'nullable|string',
          'duration' => $jobExist ? 'nullable|integer' : 'required|integer',
          'location' => $jobExist ? 'nullable|string' : 'required|string',        
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            //code...
            // $user = Auth::user();

            $data = $validator->validated();

            $job=JobPosting::where('id',$id)->first();
            if($job){
                $job->update($data);
                return response()->json(["Message"=>"Gig Update"], 201);
            }
            return response()->json(["Message"=>"Gig Not Found"], 404);
        } catch (\Exception $e) {
            // Return a custom error response in case of an exception
            return response()->json([
                'message' => 'An error occurred while updating Coupon',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @group Job Management
     * Update the verified status of a job posting.
     *
     * This endpoint allows the authenticated user to toggle the verified status of a job posting.
     * The 'verified' status is changed from true to false, or vice versa.
     *
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * 
     * @urlParam id integer required The ID of the job posting to update. Example: 1
     *
     * @response 201 {
     *    'success': 1,
     *     "job": {
     *         "id": 1,
     *         "user_id": 2,
     *         "title": "Senior Software Engineer",
     *         "description": "Seeking an experienced developer...",
     *         "verified": true,
     *         "active": true,
     *         "location": "Remote",
     *         "badge": true,
     *         "created_at": "2024-01-01T00:00:00Z",
     *         "updated_at": "2024-01-02T00:00:00Z"
     *     }
     * }
     *
     * @response 404 {
     *     'success': 0
     *     "message": "Record not found"
     * }
     *
     * @response 500 {
     *     'success': 0
     *     "message": "An error occurred while updating Verified field",
     *     "error": "Error message details"
     * }
     */
    
    public function updateJobVerified(Request $request,$id)
    {
        try {
            //code...
            $job=JobPosting::findOrFail($id);

            $job->verified = !$job->verified;
    
            $job->save();

            return response()->json(['success'=>1,"job" => $job], 201);
        } catch (ModelNotFoundException $e) {
            // Return a response if the record was not found
            return response()->json(['success'=>0,'message' => 'Record not found'], 404);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'success'=> 0,
                'message' => 'An error occurred while updating Verified feild',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @group Job Management
     * 
     * Update the active status of a job posting.
     *
     * This endpoint allows the authenticated user to toggle the active status of a job posting.
     * The 'active' status is changed from true to false, or vice versa.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * 
     * @urlParam id integer required The ID of the job posting to update. Example: 1
     *
     * @response 201 {
     *     "job": {
     *         "id": 1,
     *         "user_id": 2,
     *         "title": "Senior Software Engineer",
     *         "description": "Seeking an experienced developer...",
     *         "verified": true,
     *         "active": true,
     *         "location": "Remote",
     *         "badge": true,
     *         "created_at": "2024-01-01T00:00:00Z",
     *         "updated_at": "2024-01-02T00:00:00Z"
     *     }
     * }
     *
     * @response 404 {
     *     "message": "Record not found"
     * }
     *
     * @response 500 {
     *     "message": "An error occurred while updating Active field",
     *     "error": "Error message details"
     * }
     */
    public function updateJobActive(Request $request,$id)
    {
        try {
            //code...
            $job=JobPosting::findOrFail($id);

            $job->active = !$job->active;
    
            $job->save();
            
                return response()->json(["job" => $job], 201);
        } catch (ModelNotFoundException $e) {
            // Return a response if the record was not found
            return response()->json(['message' => 'Record not found'], 404);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'message' => 'An error occurred while updating active feild',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for job postings based on various filter parameters.
     *
     * This endpoint allows users to search for job postings based on location, duration, and verification status.
     * Additionally, it checks if the user has applied to the job posting (if the user is authenticated).
     *
     * @group Job Management
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after User Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * 
     * @queryParam location string optional Filter by location. Example: "New York"
     * @queryParam duration integer optional Filter by duration (1 for exact match or less than or equal to the given value). Example: 6
     * @queryParam isVerified boolean optional Filter by verification status. Example: true
     * 
     * @response 200 {
     *     'success': 0,
     *     "job": [
     *         {
     *             "user_id": 2,
     *             "gig_id": 1,
     *             "gigs_title": "Senior Software Engineer",
     *             "gigs_description": "Seeking an experienced developer...",
     *             "gigs_Shortdescription": "Join our growing team.",
     *             "gigs_duration": 6,
     *             "gigs_location": "Remote",
     *             "gigs_badge": true,
     *             "isActive": true,
     *             "isVerified": true,
     *             "company_name": "TechCorp",
     *             "company_image": "https://example.com/company-image.jpg",
     *             "company_description": "Innovating the future of tech.",
     *             "isApplied": true
     *         }
     *     ]
     * }
     * 
     * @response 404 {
     *     'success' : 0
     *     "message": "No Job Found"
     * }
     * 
     * @response 422 {
     *     "errors": {
     *         "location": ["The location field is required."]
     *     }
     * }
     * 
     * @response 500 {
     *     'success': 0
     *     "message": "An error occurred while adding or updating the About info",
     *     "error": "Error message details"
     * }
     */

    public function searchJob(Request $request)
    {
        try {
            $params=$request->all();

            $validator = Validator::make($params, [
                'location' => 'nullable|string',
                'duration' => 'nullable|integer',
                'isVerified' => 'nullable|boolean',
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // $user=Auth::user();

            $tokenType = $request->attributes->get('token_type');
            $user = $request->attributes->get('user');
            $gigList = [];
    
            if ($tokenType === 'user' && $user) {
                // If user is authenticated, get their gig enquiries
                $gigEnquiry = Enquiry::where('userID', $user->id)->get();
                $gigList = $gigEnquiry->pluck('gigID')->toArray();
            }
    
            $query = JobPosting::with(['user.employerProfile']);
    
            if (!empty($params['location'])) {
                $query->where('location', 'like', '%' . $params['location'] . '%');
            }
    
            if (!empty($params['duration'])) {
                $duration = $params['duration'];
                if ($duration == 1) {
                    $query->where('duration', $duration);
                } else {
                    $query->where('duration', '<=', $duration);
                }
            }
    
            if (isset($params['isVerified'])) {
                $query->where('verified', $params['isVerified']);
            }
    
            $searchResults = $query->get();
    
            $jobsArray = $searchResults->toArray();
    
            $newJobsArray = array_map(function ($job) use ($gigList, $tokenType) {
                $isApplied = in_array($job['id'], $gigList);
    
                return [
                    'user_id' => $job['user_id'],
                    'gig_id' => $job['id'],
                    'gigs_title' => $job['title'],
                    'gigs_description' => $job['description'],
                    'gigs_Shortdescription' => $job['short_description'],
                    'gigs_duration' => $job['duration'],
                    'gigs_location' => $job['location'],
                    'gigs_badge' => $job['badge'],
                    'isActive' => $job['active'],
                    'isVerified' => $job['verified'],
                    'company_name' => isset($job['user']['employer_profile']) ? $job['user']['employer_profile']['company_name'] : 'By Launcherr',
                    'company_image' => isset($job['user']['employer_profile']) ? $job['user']['employer_profile']['image'] : 'https://res.cloudinary.com/douuxmaix/image/upload/v1720553096/jhnimqyeodio3jqgxbp0.jpg',                    
                    'company_description' => isset($job['user']['employer_profile']) ? $job['user']['employer_profile']['about'] : '',                    
                    'isApplied' => $tokenType === 'user' ? ($isApplied ? true : false) : null,
                    'gigs_badge' => $job['badge'],
                ];
            }, $jobsArray);

            if(!$newJobsArray)
            {
                return response()->json(['success'=>0,'message'=>'No Job Found'], 404);                        
            }    
            return response()->json(['success'=>1,'job' => $newJobsArray], 200);        

        }  catch (\Exception $e) {
            //throw $th;
            return response()->json([
                'success'=>0,
                'message' => 'An error occurred while Adding or Updating About info',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        // public function updateBadge(Request $request,$id)
    // {
    //     try {
    //         //code...
    //         $job=JobPosting::findOrFail($id);

    //         $job->badge = !$job->badge;
    
    //         $job->save();

    //         return response()->json(["job" => $job], 201);
    //     } catch (ModelNotFoundException $e) {
    //         // Return a response if the record was not found
    //         return response()->json(['message' => 'Record not found'], 404);
    //     } catch (\Exception $e) {
    //         // Handle any other exceptions
    //         return response()->json([
    //             'message' => 'An error occurred while updating Badge feild',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
}
