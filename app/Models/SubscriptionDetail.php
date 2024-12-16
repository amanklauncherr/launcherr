<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionDetail extends Model
{
    use HasFactory;
    protected $fillable=[
        'sub_type',
        'sub_price',
        'sub_detail',
        'sub_days',
        'find_gigs',
        'book_travel',
        'book_adventure',
        'booking_fee',
        'coupon_voucher',
        'p_itinerary'
    ];
}
