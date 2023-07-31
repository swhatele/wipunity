<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Questions extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $connection = 'mysql2';
    
    protected $table = 'Questions';
    
    protected $fillable = [
        'Question_ID','Intent_Name','Question','Data_Available', 'Table_Column_Headers', 'Location_Status','Relation_table'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        
    ];
    
   
}
