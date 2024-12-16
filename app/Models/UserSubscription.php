<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'subscription_id', 'status', 'end_date'
    ];

    public function subscriptionDetail()
    {
        return $this->belongsTo(SubscriptionDetail::class, 'subscription_id');
    }
}
