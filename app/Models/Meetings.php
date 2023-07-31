<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Meetings extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'meetings';
    
    protected $fillable = [
        'discussion_id','user_id','venue_name','street_1', 'street_2', 'city','state','zipcode','meeting_date','start_time','end_time','purpose_of_meeting','additional_conference_details','created_at','updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        
    ];
    
   
}
