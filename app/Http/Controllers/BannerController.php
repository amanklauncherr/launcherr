<?php

namespace App\Http\Controllers;

// use App\Models\Banner;
use App\Models\BannerNew;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class BannerController extends Controller
{
   
    /**
     * @group Banner Management
     *
     * Upload or Update Banner
     *
     * This endpoint allows you to upload a new banner or update an existing one. 
     * Only 3 banners can exist at a time, so no additional banners will be accepted once the limit is reached.
     * **Note:** This endpoint requires an `Authorization: Bearer <access_token>` header.
     * 
     * **Note:** You will get the access_token after Admin Login
     * 
     * @authenticated
     * 
     * @header Authorization Bearer {access_token}
     * 
     * @bodyParam Banner_No string required The banner number or identifier. Example: "1"
     * @bodyParam Banner_image file required if new banner, optional if updating An image file for the banner, allowed types: jpeg, png, jpg, gif, svg, max size 5MB.
     * 
     * @response 201 {
     *   "message": "Banner created"
     * }
     * 
     * @response 200 {
     *   "message": "Banner updated"
     * }
     * 
     * @response 400 {
     *   "message": "Cannot create more than 3 banners"
     * }
     * 
     * @response 422 {
     *   "errors": {
     *     "Banner_image": [
     *       "The Banner image field is required when creating a new banner."
     *     ]
     *   }
     * }
     * 
     * @response 500 {
     *   "message": "An error occurred while Adding or Updating Section",
     *   "error": "Detailed error message here"
     * }
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function Upload(Request $request)
    {
        $banner = BannerNew::where('Banner_No', $request->Banner_No)->first();

        $validator = Validator::make($request->all(), [
            'Banner_No' => 'required|string',
            // 'Banner_button_text' => $banner ? 'nullable|string|max:20' : 'required|string|max:20',
            'Banner_image' => $banner 
                                ? 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120' 
                                : 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $data = $validator->validated();

            if (!$banner && BannerNew::count() >= 3) {
                return response()->json([
                    'message' => 'Cannot create more than 3 banners'
                ], 400);
            }

            if ($request->hasFile('Banner_image')) {
                $uploadedFileUrl = Cloudinary::upload($request->file('Banner_image')->getRealPath())->getSecurePath();
                $data['Banner_image'] = $uploadedFileUrl;
            }

            if($banner)
            {
                $banner->update($data);
                return response()->json(['message' => 'Banner updated'], 200);
            }else {
                BannerNew::create($data);
                return response()->json(['message' => 'Banner created'], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while Adding or Updating Section',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @group Banner Management
     *
     * Show All Banners
     *
     * This endpoint retrieves all existing banners.
     *
     * @response 200 [
     *   {
     *     "Banner_No": "1",
     *     "Banner_image": "https://cloudinary.com/your-image-url.jpg",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   },
     *   {
     *     "Banner_No": "2",
     *     "Banner_image": "https://cloudinary.com/another-image-url.jpg",
     *     "created_at": "2024-01-02T00:00:00.000000Z",
     *     "updated_at": "2024-01-02T00:00:00.000000Z"
     *   }
     * ]
     * 
     * @response 404 {
     *   'success' : 0
     *   "message": "No Banner found"
     * }
     * 
     * @return \Illuminate\Http\JsonResponse
     */

    public function showUpload()
    {
        $terms =BannerNew::all();
        if($terms->isEmpty())
        {
            return response()->json(['success'=>0,'message' => 'No Banner found'], 404);
        }
        else {

            return response()->json($terms,200);
        }
    }
}
