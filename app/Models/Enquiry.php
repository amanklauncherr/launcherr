<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    use HasFactory;

    protected $fillable=[
        'userID',
        'gigID',
        'note'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userID', 'id');
    }

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class, 'gigID', 'id');
    }

    
    
}
