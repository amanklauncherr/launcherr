<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DotMitSourceCities extends Model
{
    use HasFactory;
    
    protected $fillable=[
        "City_ID",
        "City_Name",
        "State_ID",
        "State_Name",
        "LocationType",
        "Latitude",
        "Longitude"
    ];
}
