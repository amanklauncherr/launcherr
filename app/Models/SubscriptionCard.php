<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionCard extends Model
{
    use HasFactory;
    protected $fillable=[
        'card_no',
        'title',
        'price',
        'price_2',
        'features',
        'buttonLabel',
    ];
}
