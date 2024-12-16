<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployerProfile extends Model
{
    use HasFactory;
    use HasFactory;
    protected $table = 'employer_profiles';  // specify the existing table name if it's different from the model name

    // If the primary key is not 'id', specify the primary key
    protected $primaryKey = 'id';

    // If the primary key is not an auto-incrementing integer, set this property
    protected $keyType = 'int';

    // If the primary key is not an incrementing integer, set this to false
    public $incrementing = true;

    // Define the attributes that are mass assignable
    protected $fillable = [
        'user_id', 'image','company_name', 'company_website', 'address', 'about', 'city', 'state', 'country'
    ];

    // If the table does not have timestamps, set this to false
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
