<?php

namespace App\Http\Controllers;

use App\Models\ClientInfo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class ClientInfoController extends Controller
{
    /**
     * @group Client Management
     *
     * Add a New Client
     *
     * This endpoint allows you to add a new client with an image. If no URL is provided, it defaults to 'null'.
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * 
     * @bodyParam url string The URL associated with the client. Example: "https://example.com"
     * @bodyParam image file required An image file for the client, allowed types: jpeg, png, jpg, gif, svg, max size 5MB.
     *
     * @response 201 {
     *   "success": 1,
     *   "message": "Client Added",
     *   "Client": {
     *     "id": 1,
     *     "url": "https://example.com",
     *     "image": "https://cloudinary.com/your-image-url.jpg",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     *
     * @response 422 {
     *   "errors": {
     *     "image": [
     *       "The image field is required."
     *     ]
     *   }
     * }
     *
     * @response 500 {
     *   "success": 0,
     *   "message": "An error occurred while creating the client",
     *   "error": "Detailed error message here"
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addClient(Request $request){
        $validator = Validator::make($request->all(), [
            'url' => 'nullable|url',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['url'] = $request->input('url', 'null');

            $uploadedFileUrl = Cloudinary::upload($request->file('image')->getRealPath())->getSecurePath();

            $data['image'] = $uploadedFileUrl;

            $client=ClientInfo::create($data);

            return response()->json(['success'=>1, 'message' => 'Client Added','Client'=>$client], 201);
        } catch (\Exception $e) {
            // Return a custom error response in case of an exception
            return response()->json([
                'success'=> 0,
                'message' => 'An error occurred while creating the question',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @group Client Management
     *
     * Update Client 
     *
     * This endpoint allows you to update an existing client's details or image.
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * 
     * @urlParam id integer required The ID of the client to update. Example: 1
     * @bodyParam url string optional The URL associated with the client. Example: "https://example.com"
     * @bodyParam image file optional An image file for the client, allowed types: jpeg, png, jpg, gif, svg, max size 5MB.
     *
     * @response 200 {
     *   "success" : 1
     *   "message": "Client Updated",
     *   "client": {
     *     "id": 1,
     *     "url": "https://example.com",
     *     "image": "https://cloudinary.com/your-new-image-url.jpg",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     *
     * @response 404 {
     *   "success" : 0
     *   "message": "Record not found"
     * }
     *
     * @response 500 {
     *   "success" : 0
     *   "message": "An error occurred while updating the client",
     *   "error": "Detailed error message here"
     * }
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
     public function updateClient(Request $request, $id){
        $validator=Validator::make($request->all(),[
            'url' => 'nullable|url',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            //code...
            $client = ClientInfo::findorFail($id);

            $data = $validator->validated();
         
            if($request->hasFile('image'))
            {
                $uploadedFileUrl = Cloudinary::upload($request->file('image')->getRealPath())->getSecurePath();
                $data['image']=$uploadedFileUrl;
            }

            $client->update($data);    

            // Return a success response
            return response()->json(['success'=>1, 'message' => 'Client Updated', 'client' => $client], 200);
        }catch (ModelNotFoundException $e) {
            // Return a response if the record was not found
            return response()->json(['success'=>0, 'message' => 'Record not found'], 404);     
        }catch (\Exception $e) {
            // Return a custom error response in case of an exception
            return response()->json([
                'success' => 0,
                'message' => 'An error occurred while updating the client',
                'error' => $e->getMessage()
            ], 500);
        }
     }

     /**
     * @group Client Management
     *
     * Delete Client
     *
     * This endpoint allows you to delete an existing client by ID.
     * 
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     *
     * @urlParam id integer required The ID of the client to delete. Example: 1
     *
     * @response 200 {
     *   'success' : 1,
     *   "message": "Record deleted successfully"
     * }
     *
     * @response 404 {
     *   'success' : 0,
     *   "message": "Record not found"
     * }
     *
     * @response 500 {
     *   'success' : 0,
     *   "message": "An error occurred while deleting the record",
     *   "error": "Detailed error message here"
     * }
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
     public function deleteClient($id)
     {
         try {
             // Find the record by ID or fail if it doesn't exist
             $client = ClientInfo::findOrFail($id);
 
             // Delete the record
             $client->delete();
 
             // Return a success response
             return response()->json(['success' => 1,'message' => 'Record deleted successfully'], 200);
         } catch (ModelNotFoundException $e) {
             // Return a response if the record was not found
             return response()->json([ 'success' => 0,'message' => 'Record not found'], 404);
         } catch (\Exception $e) {
             // Handle any other exceptions
             return response()->json([
                 'success' => 0,
                 'message' => 'An error occurred while deleting the record',
                 'error' => $e->getMessage()
             ], 500);
         }
    } 


    /**
     * @group Client Management
     *
     * Show All Clients
     *
     * This endpoint retrieves all existing clients.
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "url": "https://example.com",
     *     "image": "https://cloudinary.com/your-image-url.jpg",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   },
     *   {
     *     "id": 2,
     *     "url": "https://another-example.com",
     *     "image": "https://cloudinary.com/another-image-url.jpg",
     *     "created_at": "2024-01-02T00:00:00.000000Z",
     *     "updated_at": "2024-01-02T00:00:00.000000Z"
     *   }
     * ]
     *
     * @response 404 {
     *   'success' : 0,
     *   "message": "No Client found"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function showClient()
    {
        $data =ClientInfo::all();
        if ($data->isEmpty()) {
            return response()->json(['success'=>0,'message' => 'No Client found'], 404);
        } else {
            return response()->json($data, 200);
        }
    }
}
