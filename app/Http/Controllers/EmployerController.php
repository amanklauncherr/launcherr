<?php

namespace App\Http\Controllers;

use App\Models\EmployerProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class EmployerController extends Controller
{
    //
    public function update(Request $request)
    {
        $employer= EmployerProfile::where('id',$request->id)->first();

        $validator = Validator::make($request->all(), [
            'id' => 'required|string', 
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|min:10|max:5120',
        ]);

        // Handle validation errors
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get validated data
            $data = $validator->validated();
 
            if($request->hasFile('image'))
            {
                $uploadedFileUrl = Cloudinary::upload($request->file('image')->getRealPath())->getSecurePath();
                $data['image']=$uploadedFileUrl;
            }

            if($employer)
            {
                $employer->update($data);
                return response()->json($employer,200);
            }

            return response()->json(['message' => 'Record not found'], 404);     
        }catch (\Exception $e) {
            // Return a custom error response in case of an exception
            return response()->json([
                'message' => 'An error occurred while uploading Employer profile image',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
