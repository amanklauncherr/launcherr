<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\state;
use App\Models\Cites;
use Illuminate\Http\Request;

class StateController extends Controller
{
    //
    /**
     * @group State&City&Iata
     *
     * API to retrieve a list of all states.
     *
     * @response 200 {
     *   "success": 1,
     *   "states": ["California", "Texas", "New York", "Florida", ...]
     * }
     *
     * @response 404 {
     *   "success": 0,
     *   "message": "No State Found"
     * }
     */

    public function showState()
    {
        $state=state::get();
        if($state->isEmpty())
        {
            return response()->json(['success'=>0,'message'=>'No State Found'],404);
        }
        $s=$state->pluck('state');
        return response()->json(['success'=>1,'states'=>$s],200);
    }

    /**
     * @group State&City&Iata
     *
     * API to retrieve unique states from the cities data.
     *
     * @response 200 {
     *   "success": 1,
     *   "states": ["California", "Texas", "New York", "Florida", ...]
     * }
     *
     * @response 404 {
     *   "success": 0,
     *   "message": "No State Found"
     * }
     */

    public function AllState()
    {
        $states = Cites::distinct()->pluck('state');

        if ($states->isEmpty()) {
            return response()->json(['success' => 0, 'message' => 'No State Found'], 404);
        }

        return response()->json(['success' => 1, 'states' => $states], 200);
    }

    /**
     * @group State&City&Iata
     *
     * API to retrieve a list of cities for a specific state.
     *
     * @queryParam state required The name of the state to filter cities by.
     *
     * @response 200 {
     *   "success": 1,
     *   "Cities": ["Los Angeles", "San Francisco", "San Diego", ...]
     * }
     *
     * @response 404 {
     *   "success": 0,
     *   "message": "Please Select A State"
     * }
     *
     * @response 404 {
     *   "success": 0,
     *   "message": "No State Found"
     * }
     */


    public function CITIES(Request $request)
    {
        $state=$request->query('state');
        if(!$state)
        {
            return response()->json(['success' => 0, 'message' => 'Please Select A State'], 404);
        }

        $cities = Cites::where('state',$state)->get()->pluck('city');
        if ($cities->isEmpty()) {
            return response()->json(['success' => 0, 'message' => 'No State Found'], 404);
        }
        return response()->json(['success' => 1, 'Cities' => $cities], 200);
    }

}
