<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Controllers;

use Exception;
use Lang;
use Mail;
use Config;
use Session;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Hashing\Hasher;
use App\Repositories\EloquentRepositories\UserRepository as UserRepo;
use App\Repositories\EloquentRepositories\TagRepository as TagRepo;
use App\Repositories\EloquentRepositories\AdminRepository as Admin;
use App\Repositories\EloquentRepositories\DiscussionRepository as DiscussionRepo;
use DB;
use AWS;
use App\Models\Users;
use App\Models\Discussions;
use App\Models\Reports;
use App\Models\Replies;
use App\Models\Comments;
use App\Models\Favourites;
use App\Models\Followers;
use App\Models\Polls;
use App\Models\PollsAnswers;
use App\Models\Meetings;
use App\Models\MeetingAttendies;
use App\Models\DiscussionTags;
use App\Models\Notifications;
use Symfony\Component\HttpFoundation\Response;
use App\Jobs\AddUserNotifications;
use Log;

class NotificationsController extends Controller {

    protected $request;
    protected $hasher;
    protected $userRepo;
    protected $admin;
    protected $tagRepo;
    protected $discussionRepo;

    public function __construct(Request $request, Hasher $hasher, UserRepo $userRepo, Admin $admin, TagRepo $tagRepo, DiscussionRepo $discussionRepo) {

        $this->request = $request;
        $this->hasher = $hasher;
        $this->userRepo = $userRepo;
        $this->admin = $admin;
        $this->tagRepo = $tagRepo;
        $this->discussionRepo = $discussionRepo;
    }

    /**
     * This is used to get the list of users based on all conditions
     */
    public function index(Request $request) {
        try {

            $discussion_list = $this->discussionRepo->discussionsList($search_text, $filters, $login_id);
            $report_reasons = $this->discussionRepo->reasonsList();
            $user = Users::find($login_id);
            $first_login = $user->first_login;

            Session::forget('open_discussion_id');
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            Users::where(['id' => $login_id])->update(['first_login' => 2]);
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            return view('dashboard/discussionList', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags', 'user', 'notfication_count'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function check() {
        try {
            $activity_details = array(
                'notification_type_id' => 1,
                'user_id' => 20,
                'discussion_id' => '31',
                'discussion_reply_id'=>162
            );
                
            Log::info($activity_details);

            $discussion_id = $activity_details['discussion_id'];
            $notification_type_id = $activity_details['notification_type_id'];
            $user_id = $activity_details['user_id'];
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


            $users_list = DB::select("call sp_activity_users('$discussion_id','$user_id')");
            
//            dd($users_list);
            
                
            $notifications = [];
            if(count($users_list) > 0){
            foreach ($users_list as $user) {
                $notification = [];

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

                if (strpos($user->in_app_notification_id, '2') !== false && $user->activity_type == 1) {
                    // user enaled all notifications with tagged
                    $notification['notification_type_id'] = $activity_details['notification_type_id'];
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
                }

                    
                if (!empty($notification)) {
                    $notifications[] = $notification;
                } 
                if (strpos($user->email_notification_id, '2') !== false && $notification_type_id == 4) {

                    $body_text = 'An event has been scheduled about a discussion you participated in.';
                    $mailBody = array(
                        'email' => $user->email,
                        'name' => $user->name,
                        'body_text' => $body_text,
                        'discussion_id' => $discussion_id,
                    );
                    Log::info($mailBody);
                    Mail::send('emails.realtime_notification', $mailBody, function ($m) use ($mailBody) {
                        $m->from(env('MAIL_FROM_ADDRESS'), "WIP Support Team");
                        $m->to($mailBody['email'], $mailBody['name'])
                                ->subject('WIP Notifications');
                    });
                    //
                }
            }
            
//             Log::info($notifications);
//             dd($notifications);
            Notifications::insert($notifications);
            }

           

            // check discussion posted user notifications settings
            $discussion_details = DB::table('discussions')->select('user_id')->where('id', $discussion_id)->first();
            $user_notification_settings = DB::table('user_notification_settings')->where('user_id', $discussion_details->user_id)->first();
                    
            if (strpos($user_notification_settings->in_app_notification_id, '1') !== false && $user_id != $discussion_details->user_id) {
                $own_notification['notification_type_id'] = $activity_details['notification_type_id'];
                $own_notification['notification'] = $new_activity . 'a discussion you started';
                $own_notification['discussion_id'] = $discussion_id;
                $own_notification['status'] = 1;
                $own_notification['user_id'] = $discussion_details->user_id;

                if (isset($activity_details['meeting_id'])) {
                    $own_notification['meeting_id'] = $activity_details['meeting_id'];
                }
                if (isset($activity_details['poll_id'])) {
                    $own_notification['poll_id'] = $activity_details['poll_id'];
                }
                if (isset($activity_details['followers_id'])) {
                    $own_notification['followers_id'] = $activity_details['followers_id'];
                }
                if (isset($activity_details['discussion_comment_id'])) {
                    $own_notification['discussion_comment_id'] = $activity_details['discussion_comment_id'];
                }
                if (isset($activity_details['discussion_reply_id'])) {
                    $own_notification['discussion_reply_id'] = $activity_details['discussion_reply_id'];
                }
                //                print_r($own_notification);
                Notifications::insert($own_notification);
            }


            //Rela time email notification for tagged user
            if ($notification_type_id == 1 || $notification_type_id == 2) {
                    
//                strpos($user->email_notification_id, '1') !== false
                //Need to change as tagged functionality.
                $notification['discussion_id'] = $discussion_id;
                $notification['status'] = 1;
               $notification['notification_type_id']   = $activity_details['notification_type_id'];
//                $notification['user_id'] = $user->user_id;
                $mention_regex = '/@\[([0-9]+)\]/i';
                if ($notification_type_id == 1) {
                    $body_text = 'You are tagged in a reply';
                    $notification['discussion_reply_id'] = $activity_details['discussion_reply_id'];
                    $notification['notification'] = $body_text;
                    $reply = DB::table('replies')->select('reply_text_mentions')->where('id', $activity_details['discussion_reply_id'])->first();
                    if (preg_match_all($mention_regex, $reply->reply_text_mentions, $matches)) {
                        //@[21] can manage post @[22]  @[36]  @[28]  @[4]  @[34]
                        foreach ($matches[1] as $match) {
                            $match_user = DB::table('users as u')
                                    ->leftJoin('user_notification_settings as un', 'u.id','=', 'un.user_id')
                                    ->whereIn('u.id', array($match))
                                    ->first();

                            if (isset($match_user->id)) {
                                $notification['user_id'] = $match_user->id;
                                if (strpos($match_user->in_app_notification_id, '3') !== false) {
                                    Notifications::insert($notification);
                                }
                                if (strpos($match_user->email_notification_id, '1') !== false && $match_user->notification_type_id==1) {
                                    $mailBody = array(
                                        'email' => $match_user->email,
                                        'name' => $match_user->name,
                                        'body_text' => $body_text,
                                        'discussion_id' => $discussion_id,
                                    );
                                    Mail::send('emails.realtime_notification', $mailBody, function ($m) use ($mailBody) {
                                        $m->from(env('MAIL_FROM_ADDRESS'), "WIP Support Team");
                                        $m->to($mailBody['email'], $mailBody['name'])
                                                ->subject('WIP Notifications');
                                    });
                                }
                            }
                        }
                    }
                } elseif ($notification_type_id == 2) {
                    $body_text = 'You are tagged in a comment';
                    $notification['notification'] = $body_text;
                    $notification['discussion_comment_id'] = $activity_details['discussion_comment_id'];
                    $comment = DB::table('comments')->select('comment_text_mentions')->where('id', $activity_details['discussion_comment_id'])->first();
                    if (preg_match_all($mention_regex, $comment->comment_text_mentions, $matches)) {
                        foreach ($matches[1] as $match) {
                            $match_user = DB::table('users as u')
                                    ->leftJoin('user_notification_settings as un', 'u.id','=', 'un.user_id')
                                    ->whereIn('u.id', array($match))
                                    ->first();

                            if (isset($match_user->id)) {
                                $notification['user_id'] = $match_user->id;
                                if (strpos($match_user->in_app_notification_id, '3') !== false) {
                                    Notifications::insert($notification);
                                }
                                if (strpos($match_user->email_notification_id, '1') !== false  && $match_user->notification_type_id==1) {
                                    $mailBody = array(
                                        'email' => $match_user->email,
                                        'name' => $match_user->name,
                                        'body_text' => $body_text,
                                        'discussion_id' => $discussion_id,
                                    );
                                    Mail::send('emails.realtime_notification', $mailBody, function ($m) use ($mailBody) {
                                        $m->from(env('MAIL_FROM_ADDRESS'), "WIP Support Team");
                                        $m->to($mailBody['email'], $mailBody['name'])
                                                ->subject('WIP Notifications');
                                    });
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

}
