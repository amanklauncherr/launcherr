<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TravelHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TravelHistoryController extends Controller
{
    //
    public function GetFlightTravelHistory(Request $request)
    {

        $History=TravelHistory::where('user_id',Auth::guard('api')->id())
        ->where('BookingType','FLIGHT')
        ->get()
        ->reverse();

        if($History->isEmpty())
        {
            return response()->json(
                [
                    'success' => 0,
                    'error' => 'No Flight Travel History'
                ]
            );
        }

        $data = [];

        foreach($History as $his)
        {
            $obj=[
                'user_id'=>$his->user_id,
                'BookingType'=>$his->BookingType,
                'BookingRef'=>$his->BookingRef,
                'PnrDetails'=>json_decode($his->PnrDetails),
                'PAXTicketDetails'=>json_decode($his->PAXTicketDetails),
                'TravelDetails'=>json_decode($his->TravelDetails),
                'Status' => $his->Status,
                'Ticket_URL' => $his->Ticket_URL
            ];
                array_push($data,$obj);
        }

        return response()->json(
            [
                'success' => 1,
                'message' => 'Flight Travel History',
                'data'=> $data
            ]
        );       
    }

    public function GetBusTravelHistory(Request $request)
    {

        $History=TravelHistory::where('user_id',Auth::guard('api')->id())
        ->where('BookingType','BUS')
        ->get()
        ->reverse();

        if($History->isEmpty())
        {
            return response()->json(
                [
                    'success' => 0,
                    'error' => 'No BUS Travel History'
                ]
            );
        }

        $data = [];

        foreach($History as $his)
        {
            $obj=[
                'user_id'=>$his->user_id,
                'BookingType'=>$his->BookingType,
                'BookingRef'=>$his->BookingRef,
                'PnrDetails'=>json_decode($his->PnrDetails),
                'PAXTicketDetails'=>json_decode($his->PAXTicketDetails),
                'TravelDetails'=>json_decode($his->TravelDetails),
                'Status' => $his->Status,
                'Ticket_URL' => $his->Ticket_URL
            ];
                array_push($data,$obj);
        }

        return response()->json(
            [
                'success' => 1,
                'message' => 'Bus Travel History',
                'data'=> $data
            ]
        );       
    }
}
