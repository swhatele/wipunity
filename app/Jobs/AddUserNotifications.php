<?php

namespace App\Jobs;

use App\Models\Discussions;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\Notifications;
use DB;
use Exception;

class AddUserNotifications implements ShouldQueue {

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    protected $user_notification;
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_notification) {

        //
        $this->user_notification = $user_notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        try {

            $activity_details = $this->user_notification;
             Log::info($activity_details);
            $discussion_id = $activity_details['discussion_id'];
            $notification_type_id = $activity_details['notification_type_id'];
            $user_id = $activity_details['user_id'];
            $users_list = DB::select("call sp_activity_users('$discussion_id','$user_id')");

            $notifications = [];
            foreach ($users_list as $user) {
                $notification = [];
                $notification['notification_type_id'] = $activity_details['notification_type_id'];
                switch ($notification_type_id) {
                    case '1':
                        $new_activity = 'New reply posted in';
                        break;
                    case '2':
                        $new_activity = 'New comment added in';
                        break;
                    case '3':
                        $new_activity = 'New user following';
                        break;
                    case '4':
                        $new_activity = 'New meeting scheduled in';
                        break;
                    case '5':
                        $new_activity = 'New poll added in';
                        break;
                }
                switch ($user->activity_type) {
                    case '1':
                        $user_activity_status = ' a discussion you followed';
                        break;
                    case '2':
                        $user_activity_status = ' a discussion you replied';
                        break;
                    case '3':
                        $user_activity_status = ' a discussion you commented';
                        break;
                    case '4':
                        $user_activity_status = ' a discussion you posted a poll';
                        break;
                    case '5':
                        $user_activity_status = ' a discussion you  shceduled a meeting';
                        break;
                }
                $notification['notification'] = $new_activity . $user_activity_status;
                $notification['discussion_id'] = $discussion_id;
                $notification['status'] = 1;
                $notification['user_id'] = $user->user_id;

                if (isset($activity_details['meeting_id'])) {
                    $notification['meeting_id'] = $activity_details['meeting_id'];
                }
                if (isset($activity_details['poll_id'])) {
                    $notification['poll_id'] = $activity_details['poll_id'];
                }
                if (isset($activity_details['followers_id'])) {
                    $notification['followers_id'] = $activity_details['followers_id'];
                }
                if (isset($activity_details['discussion_comment_id'])) {
                    $notification['discussion_comment_id'] = $activity_details['discussion_comment_id'];
                }
                if (isset($activity_details['discussion_reply_id'])) {
                    $notification['discussion_reply_id'] = $activity_details['discussion_reply_id'];
                }


                $notifications[] = $notification;
            }
           // Log::info($notifications);
            Notifications::insert($notifications);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }



        //
    }

}
