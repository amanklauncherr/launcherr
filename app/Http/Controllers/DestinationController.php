<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class DestinationController extends Controller
{
    /**
     * @group Destination Management
     * 
     * Add or update a destination.
     *
     * This endpoint allows adding a new destination or updating an existing one, depending on whether an ID is provided. 
     * If the destination already exists (based on the provided ID), it will be updated with the new details.
     * The endpoint accepts destination details such as name, city, state, images, and more.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * 
     * @queryParam id string optional The ID of the destination to be updated. If not provided, a new destination is created.
     * 
     * @bodyParam name string required The name of the destination. Example: "Paris"
     * @bodyParam city string optional The city of the destination. Example: "Paris"
     * @bodyParam state string optional The state of the destination. Example: "Ile-de-France"
     * @bodyParam destination_type string optional The type of the destination. Example: "Tourist"
     * @bodyParam thumbnail_image file required Image for the thumbnail of the destination. File must be of type jpeg, png, jpg, gif, or svg.
     * @bodyParam images array required Array of images for the destination. Each image must be of type jpeg, png, jpg, gif, or svg.
     * @bodyParam short_description string required A short description of the destination. Example: "The city of lights."
     * @bodyParam description string required A detailed description of the destination. Example: "Paris is known for its art, fashion, and culture."
     * 
     * @response 201 {
     *     'success' : 1
     *     "message": "Destination Added"
     * }
     * 
     * @response 200 {
     *      'success' : 1
     *     "message": "Destination Updated"
     * }
     * 
     * @response 422 {
     *     "errors": {
     *         "name": ["The name field is required."]
     *     }
     * }
     * 
     * @response 500 {
     *     'success' : 0
     *     "error": "Something went wrong",
     *     "details": "Error message details"
     * }
     */
    public function addDestination(Request $request){

        $params = $request->query('id');
        $destination = Destination::where('id', $params)->first();
    
        $validator = Validator::make($request->all(), [
            'name' => $destination ? 'nullable|string' : 'required|string',
            'city' => 'nullable|string',
            'state' => $destination ? 'nullable|string' : 'required|string',
            'destination_type' => $destination ? 'nullable|string' : 'required|string',
            'thumbnail_image' => $destination ? 'nullable|image|mimes:jpeg,png,jpg,gif,svg' : 'required|image|mimes:jpeg,png,jpg,gif,svg',
            'images' => $destination ? 'nullable|array' : 'required|array',
            'images.*' => $destination ? 'nullable|image|mimes:jpeg,png,jpg,gif,svg' : 'required|image|mimes:jpeg,png,jpg,gif,svg',
            'short_description' => $destination ? 'nullable|string' : 'required|string',
            'description' => $destination ? 'nullable|string' : 'required|string'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();
            if(!isset($data['city']))
            {
                $data['city'] = '';
            }
            if ($request->hasFile('thumbnail_image')) {
                $urlthumbnailPath = Cloudinary::upload($request->file('thumbnail_image')->getRealPath())->getSecurePath();
                $data['thumbnail_image'] = $urlthumbnailPath;
            }
    
            if ($destination) {
                if ($request->hasFile('images')) {
                    $existingImages = json_decode($destination['images'], true); // Decode existing images from JSON
        
                    foreach ($request->file('images') as $index => $image) {
                        $urlImagePath = Cloudinary::upload($image->getRealPath())->getSecurePath();
                        $existingImages[$index] = $urlImagePath; // Update specific image index
                    }
        
                    $data['images'] = json_encode($existingImages); // Encode updated images back to JSON
                }
    
                $destination->update($data);
                return response()->json(['success'=> 1,'message' => 'Destination Updated'], 200);
            }
            if ($request->hasFile('images')) {
                $imagePaths = [];
                foreach ($request->file('images') as $image) {
                    $urlImagePath = Cloudinary::upload($image->getRealPath())->getSecurePath();
                    $imagePaths[] = $urlImagePath;
                }
                $data['images'] = json_encode($imagePaths);
            }

            Destination::create($data);
            return response()->json(['success'=> 1, 'message' => 'Destination Added'], 201);
    
        } catch (\Throwable $th) {
            return response()->json(['success'=> 0, 'error' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }
    }

    /**
     * Show all destinations.
     *
     * This endpoint retrieves all destinations from the database. If no destinations are found, it returns a 404 error. 
     * If destinations are found, it returns a list of destinations along with their details such as name, city, state, 
     * images, description, etc.
     *
     * @group Destination Management
     * 
     * @response 200 {
     *     "success": 1,
     *     "data": [
     *         {
     *             "id": "1",
     *             "name": "Paris",
     *             "city": "Paris",
     *             "state": "Ile-de-France",
     *             "destination_type": "Tourist",
     *             "thumbnail_image": "https://link-to-thumbnail-image.com",
     *             "images": [
     *                 "https://link-to-image1.com",
     *                 "https://link-to-image2.com"
     *             ],
     *             "short_description": "The city of lights.",
     *             "description": "Paris is known for its art, fashion, and culture."
     *         }
     *     ]
     * }
     *
     * @response 404 {
     *     "success": 0,
     *     "message": "No Destinations found"
     * }
     */

    public function showDestination()
    {
        $terms = Destination::all();

        if ($terms->isEmpty()) {
            return response()->json(['success'=>0,'message' => 'No Destinations found'], 404);
        } else 
        {
            $existingImages = [];

            foreach ($terms as $destination) {
                $images = json_decode($destination->images, true); // Decode images JSON to array
                $existingImages[] = [
                    'id' => $destination->id,
                    'name' => $destination->name,
                    'city' => $destination->city,
                    'state' => $destination->state,
                    'destination_type'=> $destination->destination_type,
                    'thumbnail_image' => $destination->thumbnail_image,
                    'images' => $images,
                    'short_description' => $destination->short_description,
                    'description' => $destination->description,
                    // Add other fields as needed
                ];
        }
        return response()->json(['success'=>1,'data'=>$existingImages], 200);
    }   
    }

    /**
     * Show all destinations.
     *
     * This endpoint retrieves all destinations from the database. If no destinations are found, it returns a 404 error. 
     * If destinations are found, it returns a list of destinations along with their details such as name, city, state, 
     * images, description, etc.
     *
     * @group Destination Management
     * 
     * @response 200 {
     *     "success": 1,
     *     "data": [
     *         {
     *             "id": "1",
     *             "name": "Paris",
     *             "city": "Paris",
     *             "state": "Ile-de-France",
     *             "destination_type": "Tourist",
     *             "thumbnail_image": "https://link-to-thumbnail-image.com",
     *             "images": [
     *                 "https://link-to-image1.com",
     *                 "https://link-to-image2.com"
     *             ],
     *             "short_description": "The city of lights.",
     *             "description": "Paris is known for its art, fashion, and culture."
     *         }
     *     ]
     * }
     *
     * @response 404 {
     *     "success": 0,
     *     "message": "No Destinations found"
     * }
     */

    public function destination(Request $request){
        try {
            // Retrieve the 'id' from the query parameters
            $params = $request->query('id');
    
            // Fetch the destination record by id
            $terms = Destination::where('id', $params)->get();
    
            if ($terms->isEmpty()) {
                return response()->json(['success'=>0,'message' => 'Destination not found'], 404);
            }
    
            $terms[0]->makeHidden(['created_at', 'updated_at']);

            // Encode the 'images' attribute to JSON
            $terms[0]->images = json_decode($terms[0]->images);
    
            // Return the response as JSON
            return response()->json(['success'=>1,'destination'=>$terms[0]], 200);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }
    }

    /**
     * Search destinations based on filters.
     *
     * This endpoint allows users to search destinations based on the `state` and `destination_type` query parameters. 
     * Both filters are optional. If no matching destinations are found, a 404 error is returned.
     *
     * @group Destination Management
     * 
     * @param string|null $state Filter by state. (Optional)
     * @param string|null $destination_type Filter by destination type. (Optional)
     *
     * @response 200 {
     *     "success": 1,
     *     "Destination": [
     *         {
     *             "id": "1",
     *             "name": "Paris",
     *             "city": "Paris",
     *             "state": "Ile-de-France",
     *             "destination_type": "Tourist",
     *             "thumbnail_image": "https://link-to-thumbnail-image.com",
     *             "images": [
     *                 "https://link-to-image1.com",
     *                 "https://link-to-image2.com"
     *             ],
     *             "short_description": "The city of lights.",
     *             "description": "Paris is known for its art, fashion, and culture."
     *         },
     *         {
     *             "id": "2",
     *             "name": "New York",
     *             "city": "New York",
     *             "state": "New York",
     *             "destination_type": "Urban",
     *             "thumbnail_image": "https://link-to-thumbnail-image.com",
     *             "images": [
     *                 "https://link-to-image1.com",
     *                 "https://link-to-image2.com"
     *             ],
     *             "short_description": "The city that never sleeps.",
     *             "description": "New York is known for its skyline, culture, and food."
     *         }
     *     ]
     * }
     *
     * @response 422 {
     *     "success": 0,
     *     "errors": [
     *         "The state field must be a string.",
     *         "The destination type field must be a string."
     *     ]
     * }
     *
     * @response 404 {
     *     "success": 0,
     *     "message": "No destination Found"
     * }
     *
     * @response 500 {
     *      "success": 0,
     *     "error": "Something went wrong",
     *     "details": "Detailed error message"
     * }
     */
    public function searchDestination(Request $request){
        try {
            //code...
            $params=$request->all();
            $validator= Validator::make($params,[
              'state' => 'nullable|string',
              'destination_type' =>'nullable|string',
            ]);
            if ($validator->fails()) {
                $errors = $validator->errors()->all(); // Get all error messages
                $formattedErrors = [];
        
                foreach ($errors as $error) {
                    $formattedErrors[] = $error;
                }
                return response()->json([
                    'success' => 0,
                    'errors' => $formattedErrors
                ], 422);
            }   
            $query=Destination::query();
            if(!empty($params['state']))
            {
                $query->where('state', 'like', '%' . $params['state'] . '%');
            }
            if(!empty($params['destination_type']))
            {
                $query->where('destination_type', 'like', '%' . $params['destination_type'] . '%');
            }

            $searchResults = $query->get();
            if($searchResults->isEmpty())
            {
                return response()->json(['success' => 0, 'message'=>'No destination Found'], 404);  
            }
            return response()->json(['success' => 1,'Destination' => $searchResults], 200);     
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([ "success"=> 0,'error' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }
    }


     /**
     * @group Destination Management
     * 
     * Retrieve unique destination types.
     *
     * This endpoint returns all unique destination types available in the `Destination` records.
     * If no destination types are found, a 404 error is returned.
     * 
     * @response 200 {
     *     "success": 1,
     *     "destination_types": [
     *         "Tourist",
     *         "Urban",
     *         "Beach"
     *     ]
     * }
     *
     * @response 404 {
     *     "success": 0,
     *     "message": "No data Found"
     * }
     *
     * @response 500 {
     *     "success": 0,
     *     "error": "Something went wrong",
     *     "details": "Detailed error message"
     * }
     */

    public function destinationType(){
    
    try {
        // Get all Destination records
        $types = Destination::all();

        if(!$types){
            return response()->json(['success'=>0,'message'=>'No data Found'], 404);       
        }

        $uniqueTypes = $types->pluck('destination_type')->unique()->values();
        // Return the unique destination types in a JSON response
        return response()->json(['success'=>1,'destination_types' => $uniqueTypes], 200);
    } catch (\Throwable $th) {
        return response()->json([
            'success' => 0,
            'error' => 'Something went wrong', 
            'details' => $th->getMessage()], 500);
    }
    }

   

    /**
     * @group Destination Management
     * 
     * Delete a destination by ID.
     *
     * This endpoint deletes a destination record based on the provided `id`. 
     * If the destination with the given ID does not exist, a 404 error is returned. 
     * On successful deletion, a success message is returned.
     *
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @param int $id (query) The ID of the destination to delete.
     *
     * @response 200 {
     *     "success": 1,
     *     "message": "Destination Removed Successfully"
     * }
     *
     * @response 404 {
     *     "success": 0,
     *     "message": "No Data Found"
     * }
     *
     * @response 500 {
     *     "success": 0,
     *     "error": "Something went wrong while deleting",
     *     "details": "Detailed error message"
     * }
     */

    public function deleteDestination(Request $request){
        try {
            //code...
            $params = $request->query('id');
            $destination = Destination::where('id', $params)->first();
            if(!$destination)
            {
                return response()->json(['success'=>0,'message'=>'No Data Found'],404);
            }
            $destination->delete();
            return response()->json(['success'=>1,'message'=>'Destination Removed Successfully'],200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => 0,
                'error' => 'Something went wrong while deleating ', 
                'details' => $th->getMessage()], 500);
        }

    }
}
// |dimensions:ratio=16/9
