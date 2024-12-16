<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    protected $fillable=[
        'Card_No',
        'Card_Heading',
        'Card_Image',
        'Card_Subheading',
    ];

}
