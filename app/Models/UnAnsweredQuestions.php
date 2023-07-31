<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UnAnsweredQuestions extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'un_answered_questions';
    
    protected $fillable = [
        'question','hud_term'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];
    
    /**
     * 
     * @return type
     */
//    public function profile()
//    {
//        return $this->hasOne('App\Models\UserProfile');
//    }
//    public function fullName()
//    {
//        return $this->hasOne('App\Models\UserProfile');
//    }
}
