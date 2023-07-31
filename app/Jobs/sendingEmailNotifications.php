<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use AWS;

class sendingEmailNotifications implements ShouldQueue
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
           // Log::info($activity_details);
           $notification_type_id = $activity_details['notification_type_id'];
           $user_id = $activity_details['user_id'];
           $email = $activity_details['email'];
           $name = $activity_details['name'];

          $replies_count = $activity_details['replies_count'];
          $comments_count = $activity_details['comments_count'];
          $polls_count = $activity_details['polls_count'];
          $meetings_count = $activity_details['meetings_count'];
          if($notification_type_id == 1){
            $subject='WIP: Your Monthly Digest';
            $event_digest = 'monthly';
          }elseif ($notification_type_id == 2) {
            $subject='WIP: Your Weekly Digest';
            $event_digest = 'weekly';
          }else {
            $subject='WIP: Your Dail Digest';
            $event_digest = 'daily';
          }

          $mailBody = array(
                                         'email' => $email,
                                         'name' => $name,
                                         'replies_count'=> $replies_count,
                                         'comments_count' => $comments_count,
                                         'polls_count'=>$polls_count,
                                         'meetings_count' =>$meetings_count,
                                         'event_digest'=> $event_digest,


                                     );
         Mail::send('emails.notification', $mailBody, function ($m) use ($mailBody) {
                                         $m->from(env('MAIL_FROM_ADDRESS'), Lang::get('global.TNCTeam'));
                                         $m->to($mailBody['email'], $mailBody['name'])
                                         ->subject($subject);
                                     });



      } catch (Exception $e) {
          throw new Exception($e->getMessage(), $e->getCode());
      } catch (\Illuminate\Database\QueryException $qe) {
          throw new Exception($qe->getMessage(), $qe->getCode());
      }


    }
}
