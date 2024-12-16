<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','user_Number' ,'user_Address', 'user_City', 'user_State', 'user_Country', 'user_PinCode', 'user_AboutMe'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
