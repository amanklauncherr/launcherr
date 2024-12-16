<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelHistory extends Model
{
    use HasFactory;

    protected $fillable=[
        'user_id',
        'BookingType',
        'BookingRef',
        'PnrDetails',
        'PAXTicketDetails',
        'TravelDetails',
        'Status',
        'Ticket_URL'
    ];
    
}
