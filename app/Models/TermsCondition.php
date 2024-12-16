<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermsCondition extends Model
{
    use HasFactory;
    protected $fillable = ['content'];

    /**
     * Format the date when serializing the model.
     *
    * @param \DateTimeInterface $date
    * @return string
    */
   protected function serializeDate(\DateTimeInterface $date)
   {
       return $date->format('Y-m-d');
   }
}
