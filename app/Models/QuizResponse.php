<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizResponse extends Model
{
    use HasFactory;
    protected $fillable=[
        'name',
        'email',
        'phone',
        'answer1',
        'answer2',
        'answer3',
    ];

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
