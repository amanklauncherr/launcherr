<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iatacode extends Model
{
    use HasFactory;
    protected $fillable=[
        'airport_name',
        'country',
        'iata_code'
    ];
}
