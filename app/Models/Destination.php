<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    use HasFactory;
    protected $fillable=[
        'name',
        'city',
        'state',
        'destination_type',
        'thumbnail_image',
        'images',
        'short_description',
        'description',
    ];
}
