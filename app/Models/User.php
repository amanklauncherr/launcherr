<?php

namespace App\Models;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'isProfile'
    ];



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function jobPostings()
    {
       return $this->hasMany(JobPosting::class);
    }

    public function employerProfile()
    {
       return $this->hasOne(EmployerProfile::class);
    }

    public function enquiries()
    {
        return $this->hasMany(Enquiry::class, 'userID', 'id');
    }

    public function userProfile()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    

     /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */


     public function getJWTIdentifier()
     {
         return $this->getKey();
     }
      
     /**
      * Return a key value array, containing any custom claims to be added to the JWT.
      *
      * @return array
      */
 
     public function getJWTCustomClaims()
     {
         return [];
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
