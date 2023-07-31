<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use AWS;

class sendingRealtimeNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

     protected $user_notification;
     public $tries = 3;

     public function __construct($user_notification) {

         //
         $this->user_notification = $user_notification;
     }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      try {

          $activity_details = $this->user_notification;

           $notification_type_id = $activity_details['notification_type_id'];
           $user_id = $activity_details['user_id'];
           $discussion_id = $activity_details['discussion_id'];
          if($notification_type_id == 1){
            $body_text ='A reply posted about a discussion you participated in.';

          }elseif ($notification_type_id == 2) {
            $body_text='A comment posted about a discussion you participated in.';

          }elseif ($notification_type_id == 3) {
            $body_text='An event has been scheduled about a discussion you participated in.';

          }elseif ($notification_type_id == 4) {
            $body_text='A poll has been posted about a discussion you participated in.';

          }

          $users_list = DB::select("call sp_realtime_activity_users('$discussion_id','$user_id')");

          $notifications = [];
          foreach ($users_list as $user) {


          $mailBody = array(
                                         'email' => $user->email,
                                         'name' => $user->name,
                                         'body_text' =>$body_text,
                                         'discussion_id' =>$discussion_id,


                                     );
         Mail::send('emails.realtime_notification', $mailBody, function ($m) use ($mailBody) {
                                         $m->from(env('MAIL_FROM_ADDRESS'), Lang::get('global.TNCTeam'));
                                         $m->to($mailBody['email'], $mailBody['name'])
                                         ->subject('WIP Notifications');
                                     });

          }

      } catch (Exception $e) {
          throw new Exception($e->getMessage(), $e->getCode());
      } catch (\Illuminate\Database\QueryException $qe) {
          throw new Exception($qe->getMessage(), $qe->getCode());
      }


    }
}
