<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cites;
use Illuminate\Http\Request;

class CitesController extends Controller
{
    /**
     * @group State&City&Iata
     *
     * API to retrieve a list of all cities, sorted alphabetically.
     *
     * @response 200 {
     *   "city": ["New York", "Los Angeles", "Chicago", "San Francisco", "Miami"]
     * }
     *
     * @response 500 {
     *   "message": "An error occurred while retrieving cities",
     *   "error": "<error-message>"
     * }
     */
    public function Cites(Request $request){
        
        // Retrieve all cities
        $cities = Cites::all();

        $cityArray = [];
        foreach($cities as $city)
        {
            $cityArray[] = $city->city;
        }
        
        sort($cityArray);
        return response()->json($cityArray,200);
    }   
}
