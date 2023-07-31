<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPollMeetingFollwersIdsNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('notifications', function (Blueprint $table) {
                    
                   $table->bigInteger('meeting_id')->nullable()->after('discussion_id');
                   $table->bigInteger('poll_id')->nullable()->after('meeting_id');
                   $table->bigInteger('followers_id')->nullable()->after('poll_id'); 
                   });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
