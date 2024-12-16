<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model
{
    use HasFactory;
    protected $table = 'job_posting';  // specify the existing table name if it's different from the model name

    // If the primary key is not 'id', specify the primary key
    protected $primaryKey = 'id';

    // If the primary key is not an auto-incrementing integer, set this property
    protected $keyType = 'int';

    // If the primary key is not an incrementing integer, set this to false
    public $incrementing = true;

    // Define the attributes that are mass assignable
    protected $fillable = [
        'user_id', 'title', 'description','short_description', 'duration', 'active', 'verified','location','badge'
    ];

    // If the table does not have timestamps, set this to false
    public $timestamps = true;

    // Define the default values for attributes
    protected $attributes = [
        'active' => 0,
        'verified' => 0,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enquiries()
    {
        return $this->hasMany(Enquiry::class, 'gigID', 'id');
    }   

    // public function employer()
    // {
    //     return $this->belongsTo(EmployerProfile::class);
    // }

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
