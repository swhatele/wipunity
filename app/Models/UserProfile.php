<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'user_profile';

    protected $guarded = [
        'id'
    ];
    protected $fillable = [
        'user_id','fname','lname' ,'phone_number','license_plate_number','license_plate_state','model_of_vehicle','model_year_vehicle','company_name','profile_img','profile_thumb_img'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];

}
