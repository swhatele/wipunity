<?php

namespace App\Repositories\EloquentRepositories;

use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Eloquent\Repository;
use DB;
use Exception;

/**
 * Description of UserSettingsRepository
 *
 * @author appit
 */
class UserRepository extends Repository {

    public function model() {
        return 'App\Models\Users';
    }

    /**
     * 
     * @param type $sort_by
     * @param type $sort_type
     * @param type $search_query
     * @param type $filter_value
     * @return type
     */
    public function getUsers($sort_by = null, $sort_type = null, $type = null) {

        $query = DB::table('users as u');
        $query->select('u.id', 'u.email', 'u.name', 'u.company_name', 'u.title', 'u.activated_at', 'u.status', 'r.reason');
        $query->leftjoin('user_deactivation_reasons as r', 'u.deactivation_reason_code', '=', 'r.id');
        $query->where('u.role', '=', 2);

        if ($type != '' && $type == 'new_accounts') {
            $query->where('u.status', '=', '1');
        }
        if ($type != '' && $type == 'deactivated_accounts') {
            $query->where('u.status', '=', '3');
        }
        if ($sort_by != '' && $sort_type != '') {
            $query->orderBy($sort_by, $sort_type);
        } else {
            $query->orderBy('u.activated_at', 'desc');
        }

        $data = $query->paginate(10);

        return $data ? $data : false;
    }

    /**
     * 
     * @return type
     */
    public function isUserExist($email) {
        $query = DB::table('users')
                ->where('email', '=', $email)
                ->where('role', '=', 2)
                ->first();
        return $query ? $query : false;
    }

    public function isUserRegistrationCompleted($email) {
        $query = DB::table('users')
                ->where('email', '=', $email)
                ->where('on_boarding_status', '=', 5)
                ->first();
        return $query ? $query : false;
    }

    public function updateUserExist($user_id) {
        $query = DB::table('users')
                ->where('email', '=', $email)
                ->where('id', '!=', $user_id)
                ->first();
        return $query ? $query : false;
    }

    public function getUserDetails($user_id) {
        $query = DB::table('users as u')
                ->select('u.*')
                ->where('u.id', '=', $user_id)
                ->first();
        return $query ? $query : false;
    }

    public function checkNotificationSettings($user_id) {
        $query = DB::table('user_notification_settings')
                ->where('user_id', '=', $user_id)
                ->first();
        return $query ? $query : false;
    }

    public function getNotifications($offset, $page_num) {
        $skip_value = ($page_num - 1) * $offset;
        $query = DB::table('admin_notification as a');
        $query->join('user_profile as up', 'a.user_id', '=', 'up.user_id');
        $query->orderby('a.id', 'desc');
        if (!empty($offset)) {
            $count = $query->count();
        }
        if (!empty($page_num)) {
            $query->skip($skip_value)->take($offset);
            $query->select('up.user_id', DB::raw("CONCAT(up.fname,' ',up.lname) AS name"), 'up.profile_img');
            $list = $query->get();
        }
        return $count > 0 ? ['count' => $count, 'list' => $list] : [];
    }

    public function getUsersSurveyStatus($sort_by, $sort_type) {

        $query = \App\Models\Users::where('role', 2)->where('status', 2);
        if ($sort_by != '' && $sort_type != '') {
            $query->orderBy($sort_by, $sort_type);
        } else {
            $query->orderBy('activated_at', 'desc');
        }

        $data = $query->paginate(10);

        return $data ? $data : false;
    }

//    public function updateUserSurveyStatus(){
//        
//    }

    public function updateUserSurveyStatus($user_id, $survey_doc) {
        $update = array('survey_status' => 3, "survey_file_key_id" => $survey_doc);
        $survey_update = DB::table('users as u')->where(['id' => $user_id])->update($update);
        return true;
    }

    public function userNotifications($user_id) {
        $query = DB::table('notifications as n');
        $query->select('n.id', 'n.notification', 'n.notification_type_id', 'n.discussion_id', 'n.status', 'n.created_at', 'd.topic',
                DB::raw('(select basin_name from basins where id=d.basin_id) as basin_name'), DB::raw('(select basin_color_code from basins where id=d.basin_id) as basin_color_code'), DB::raw('(SELECT CASE
                                WHEN discussion_reply_id IS NOT NULL THEN  (select reply_text from replies r where r.id=discussion_reply_id)
                                WHEN discussion_comment_id IS NOT NULL THEN  (select comment_text from comments c where c.id=discussion_comment_id)
                                WHEN meeting_id IS NOT NULL THEN  (select purpose_of_meeting from meetings m where m.id=meeting_id)
                                WHEN poll_id IS NOT NULL THEN  (select question from polls p where p.id=poll_id)
                                ELSE d.topic
                                
                            END) AS post_text')
        );
        $query->leftjoin('discussions as d', 'n.discussion_id', '=', 'd.id');
        $query->where('n.user_id', '=', $user_id);
        $query->orderBy('n.updated_at', 'desc');


        $data = $query->paginate(6);

        return $data ? $data : false;
    }

    public function followingDiscussions($login_id) {
        $query = DB::table('followers as f')
                        ->select('f.id', 'f.discussion_id', 'f.updated_at', 'd.topic', 
                                DB::raw('(select basin_name from basins where id=d.basin_id) as basin_name'), 
                                DB::raw('(select basin_color_code from basins where id=d.basin_id) as basin_color_code'))
                        ->leftjoin('discussions as d', 'f.discussion_id', '=', 'd.id')
                        ->where('d.status',1)
                        ->where('f.user_id', $login_id)->orderby('f.updated_at')->get();
        return $query ? $query : false;
    }
    public function startedDiscussions($login_id) {
        $query = DB::table('discussions as d')
                        ->select('d.topic', 'd.created_at','d.id',
                                DB::raw('(select basin_name from basins where id=d.basin_id) as basin_name'), 
                                DB::raw('(select basin_color_code from basins where id=d.basin_id) as basin_color_code'))
                                ->where('d.status',1)
                                ->where('d.user_id', $login_id)->orderby('d.updated_at')->get();
        return $query ? $query : false;
    }

    public function getFavourites($login_id) {
        $query = DB::table('favourites as f')
                        ->select('f.id', 'f.discussion_id', 'f.updated_at', 'r.id  as reply_id','cc.id as comment_id', 
                                DB::raw('(CASE WHEN f.discussion_id IS NOT NULL THEN f.discussion_id WHEN f.discussion_replay_comment_id IS NOT NULL THEN (select c.discussion_id from comments c where c.id=f.discussion_replay_comment_id )  ELSE (select discussion_id from replies where id=f.discussion_reply_id) END) AS d_id'), 
                                DB::raw('(CASE WHEN f.discussion_id IS NOT NULL THEN 1 WHEN f.discussion_replay_comment_id IS NOT NULL THEN 2 ELSE 0 END) AS favourite_type'), 
                                DB::raw('(select basin_name from basins where id=(select basin_id from discussions where id=d_id)) as basin_name'), 
                                DB::raw('(select basin_color_code from basins where id=(select basin_id from discussions where id=d_id)) as basin_color_code'))
                        ->leftjoin('discussions as d', 'f.discussion_id', '=', 'd.id')
                        ->leftjoin('replies as r', 'f.discussion_reply_id', '=', 'r.id')
                        ->leftjoin('comments as cc', 'f.discussion_replay_comment_id', '=', 'cc.id')
                        ->where('d.status',1)
                        ->where('f.user_id', $login_id)->orderby('f.updated_at')->get();
        return $query ? $query : false;
    }

    public function getUsersList($search_term) {
        $query = DB::table('users')
                ->select('id', 
                        'name',
                        DB::raw('CONCAT("https://wip-development.s3-us-west-2.amazonaws.com/development/", profile_icon) as avatar'),
                        'email  as info'
                        ,DB::Raw('"contact" as type') 
                        )
                ->where(function($query ) use ($search_term) {
                    $query->where('name', 'like', '%' . $search_term . '%')
                    ->orwhere('email', 'like', '%' . $search_term . '%');
                })
                ->where('role', '=', 2)
                ->get();
        return $query ? $query : false;
    }

    //------------------------------------------------------------------------------------------
    //----------------------------------------------------------------------------------------------
}
