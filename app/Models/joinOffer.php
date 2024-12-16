<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class joinOffer extends Model
{
    use HasFactory;
    protected $fillable=[
        'section',
        'heading',
        'sub_heading'
    ];
}
