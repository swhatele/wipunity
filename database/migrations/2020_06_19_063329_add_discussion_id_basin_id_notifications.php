<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscussionIdBasinIdNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
                    
                   $table->bigInteger('discussion_id')->nullable()->after('notification_type_id');
                   $table->bigInteger('discussion_reply_id')->nullable()->after('notification_type_id');
                   $table->bigInteger('discussion_comment_id')->nullable()->after('notification_type_id');
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
