<?php

namespace App\Repositories\EloquentRepositories;

use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Eloquent\Repository;
use DB;
use Exception;
use App\Models\Meetings;

/**
 * Description of UserSettingsRepository
 *
 * @author appit
 */
class DiscussionRepository extends Repository {

    public function model() {
        return 'App\Models\Discussions';
    }

    /**
     * 
     * @param type $sort_by
     * @param type $sort_type
     * @param type $search_query
     * @param type $filter_value
     * @return type
     */
    public function discussionsList($search_text, $filters, $login_id) {

        $query = DB::table('discussions as d')
                ->leftjoin('basins as b', 'd.basin_id', '=', 'b.id')
                ->leftjoin('users as u', 'd.user_id', '=', 'u.id')
                ->select('d.*', 'b.basin_name', 'b.basin_color_code', 
                        DB::raw('(SELECT count(*)  from favourites as f where f.discussion_id = d.id ) AS f_count'),
                        DB::raw('(SELECT count(*)  from replies as r where r.discussion_id = d.id and r.id not in (select discussion_reply_id from reports where discussion_reply_id is not null) ) AS replies_count'),
                        DB::raw('(SELECT count(*)  from comments as c where c.discussion_id = d.id and c.id not in (select discussion_comment_id from reports where discussion_comment_id is not null) ) AS comments_count'), 
                        DB::raw('(SELECT ifnull((select count(*) from favourites as f2 where f2.user_id = ' . $login_id . ' and f2.discussion_id = d.id),-1))  AS favourited')
                )->whereNotIn('d.id', DB::table('reports')->select('discussion_id')->where('discussion_id', '!=', null))
                ->where('u.status', '!=', '3')
                ->where('d.status',1)
                ->orderBy('d.updated_at', 'desc');
        $data = $query->paginate(10);
        return $data ? $data : false;
    }

    public function discussionsListWithFilters($tag_ids, $basin_ids, $login_id) {
         
        $query = DB::table('discussions as d')
                ->leftjoin('basins as b', 'd.basin_id', '=', 'b.id')
                ->leftjoin('discussion_vs_tags as dt', 'd.id', '=', 'dt.discussion_id')
                ->leftjoin('users as u', 'd.user_id', '=', 'u.id')
                ->select('d.*', 'b.basin_name', 'b.basin_color_code', 
                        DB::raw('(SELECT count(*)  from favourites as f where f.discussion_id = d.id ) AS f_count'), 
                        DB::raw('(SELECT count(*)  from replies as r where r.discussion_id = d.id and r.id not in (select discussion_reply_id from reports where discussion_reply_id is not null) ) AS replies_count'),
                        DB::raw('(SELECT count(*)  from comments as c where c.discussion_id = d.id and c.id not in (select discussion_comment_id from reports where discussion_comment_id is not null) ) AS comments_count'), 
                        DB::raw('(SELECT ifnull((select count(*) from favourites as f2 where f2.user_id = ' . $login_id . ' and f2.discussion_id = d.id),-1))  AS favourited')
                );
//                ->where(function($query ) use ($tag_ids, $basin_ids) {
//                    $query->whereIn('d.basin_id', $basin_ids)
//                    ->WhereIn('dt.tag_id', $tag_ids);
//                })
            if(count($basin_ids) > 0 &&  $basin_ids[0] > 0){
                  $query->whereIn('d.basin_id', $basin_ids);
            }
             if(count($tag_ids) >0 && $tag_ids[0]){
                  $query->WhereIn('dt.tag_id', $tag_ids);
            }
                $query->where('u.status', '!=', '3')
                ->where('d.status',1)
                ->whereNotIn('d.id', DB::table('reports')->select('discussion_id')->where('discussion_id', '!=', null))
                ->groupBy('d.id')
                ->orderBy('d.updated_at', 'desc');
//                echo $query->toSql();
                
        $data = $query->paginate(10);
        return $data ? $data : false;
    }

    public function searchDiscussionsList($search, $login_id) {
        $query = DB::table('discussions as d')
                ->leftjoin('basins as b', 'd.basin_id', '=', 'b.id')
                ->leftjoin('comments as c', 'd.id', '=', 'c.discussion_id')
                ->leftjoin('replies as r', 'd.id', '=', 'r.discussion_id')
                ->leftjoin('users as u', 'd.user_id', '=', 'u.id')
                ->select('d.*', 'b.basin_name', 'b.basin_color_code', 'r.id as reply_id', 'c.id as comment_id', 
                        DB::raw('(SELECT count(*)  from favourites as f where f.discussion_id = d.id ) AS f_count'), 
                        DB::raw('(SELECT count(*)  from replies as r where r.discussion_id = d.id and r.id not in (select discussion_reply_id from reports where discussion_reply_id is not null) ) AS replies_count'), 
                        DB::raw('(SELECT count(*)  from comments as c where c.discussion_id = d.id and c.id not in (select discussion_comment_id from reports where discussion_comment_id is not null) ) AS comments_count'), 
                        DB::raw('(SELECT ifnull((select count(*) from favourites as f2 where f2.user_id = ' . $login_id . ' and f2.discussion_id = d.id),-1))  AS favourited'),
                        DB::raw('IF(d.topic like "%' . $search .'%", "1", IF(r.reply_text LIKE "%'. $search .'%", "2",IF(c.comment_text LIKE "%'. $search .'%", "3","1"))) AS  result_type'),
                        DB::raw('IF(d.topic like "%' . $search .'%", d.id, IF(r.reply_text LIKE "%'. $search .'%", r.id,IF(c.comment_text LIKE "%'. $search .'%", c.id,d.id))) AS  result_id')
                )
                ->where(function ($query) use ($search) {
                    $query->where('b.basin_name', 'like', '%' . $search . '%')
                            ->orwhere('d.topic', 'like', '%' . $search . '%')
                    ->orWhere('r.reply_text', 'like', '%' . $search . '%')
                    ->orWhere('c.comment_text', 'like', '%' . $search . '%');
                })
                ->where('u.status', '!=', '3')
                ->where('d.status',1)
                ->whereNotIn('d.id', DB::table('reports')->select('discussion_id')->where('discussion_id', '!=', null))
                ->groupBy('d.id')
                ->orderBy('d.updated_at', 'desc');
        $data = $query->paginate(10);
        return $data ? $data : false;
    }

    public function getRepliesList($discussion_id, $login_id) {

        $replies = DB::table('replies as r')
                ->leftjoin('users as u', 'r.user_id', '=', 'u.id')
                ->select('r.id as id', 'r.reply_text as r_text', 'r.updated_at as updated_at', 'u.profile_icon', DB::raw('(select count(*) from comments as c where c.discussion_reply_id=r.id)  as c_count'), DB::raw('(select count(*) from favourites as f where f.discussion_reply_id=r.id ) as r_f_count'), DB::raw('(SELECT ifnull((select id from favourites as f2 where f2.user_id = ' . $login_id . ' and f2.discussion_reply_id = r.id),-1))  AS favourited'), '1 as list_type'
                )
                ->where('r.discussion_id', $discussion_id)
                ->orderBy('r.updated_at', 'desc');

        $polls = DB::table('polls as p')
                ->leftjoin('users as u', 'p.user_id', '=', 'u.id')
                ->select('p.id as id', 'p.question as r_text ', 'p.updated_at as updated_at', 'u.profile_icon', DB::raw('(select 0  as c_count)'), DB::raw('(select 0 as r_f_count)'), DB::raw('(SELECT  0 AS favourited)'), '2 as list_type'
                )
                ->where('p.discussion_id', $discussion_id)
                ->union($replies)
                ->orderBy('updated_at', 'desc');


        $data = $polls->paginate(5);

        return $data ? $data : false;
    }

    public function loadReplies($discussion_id, $last_reply_id) {

        $query = DB::table('replies as r', 'u.profile_icon')
                ->leftjoin('users as u', 'r.user_id', '=', 'u.id')
                ->select('r.*', 'u.profile_icon', DB::raw('(select count(*) from comments as c where c.discussion_reply_id=r.id)  as c_count'), DB::raw('(select count(*) from favourites as f where f.discussion_reply_id=r.id ) as r_f_count'), DB::raw('(SELECT ifnull((select id from favourites as f2 where f2.user_id = ' . $login_id . ' and f2.discussion_reply_id = r.id),-1))  AS favourited')
                )
                ->where('r.discussion_id', $discussion_id)
                ->where('r.id', '<', $last_reply_id)
                ->orderBy('r.updated_at', 'desc')
                ->limit(5);
        $data = $query->get();

        return $data ? $data : false;
    }


    public function discussionDetails($id, $login_id) {

        $query = DB::table('discussions as d')
                        ->leftjoin('basins as b', 'd.basin_id', '=', 'b.id')
                        ->leftjoin('users as u', 'd.user_id', '=', 'u.id')
                        ->select('d.*', 'u.name',  'u.profile_icon', 'b.basin_name', 'b.basin_color_code', 'b.id as basin_id',
                                DB::raw('(SELECT count(*)  from favourites as f where f.discussion_id = d.id ) AS f_count'), 
                                DB::raw('(SELECT count(*)  from replies as r where r.discussion_id = d.id ) AS replies_count'),
                                DB::raw('(SELECT ifnull((select count(*) from favourites as f2 where f2.user_id = ' . $login_id . ' and f2.discussion_id = d.id),-1))  AS favourited'),
                                DB::raw('(SELECT IFNULL((SELECT CASE WHEN id IS NOT NULL THEN 1 ELSE 0 END from followers  where user_id= ' . $login_id . ' and discussion_id = d.id),0)) AS followed')
                        )
                        ->where('d.status',1)
                        ->where('d.id', $id)->first();
        return $query ? $query : false;
    }
    
    public function discussionVSTags($id){
        $query = DB::table('discussion_vs_tags')->selectRaw('GROUP_CONCAT(tag_id) as tag_ids')->where('discussion_id', $id)->first();
        return $query ? $query : false;
    }

    public function meetingsList($discussion_id) {

        $query = DB::table('meetings as m')
                ->leftjoin('users as u', 'm.user_id', '=', 'u.id')
                ->select('m.*', 'u.profile_icon')
                ->where('m.discussion_id', $discussion_id)
                ->where(function ($query) {
                    $query->where('meeting_date', '>=', $date = date('Y-m-d'));
                    $query->orWhere(function ($query1) {
                        $query1->where('meeting_date', '>=', $date = date('Y-m-d'))
                        ->where('start_time', '>', $date = date('H:i'));
                    });
                })
                ->orderBy('meeting_date', 'asc')
                ->limit(5);
        $data = $query->get();

        return $data ? $data : false;
    }

    public function storeReply($discussion_id, $post, $user_id) {
        $update = array('survey_status' => 3, "survey_file_key_id" => $survey_doc);
        $survey_update = DB::table('replies')->insert($update);
        return true;
    }

    public function replyDetails($reply_id) {
        $query = DB::table('replies as r', 'u.profile_icon')
                ->leftjoin('users as u', 'r.user_id', '=', 'u.id')
                ->leftjoin('favourites as f', 'r.id','f.discussion_reply_id')
                ->select('r.*', 'u.profile_icon','u.name','f.id as f_id','r.image_key_id',
                        DB::raw('(select count(*) from comments as c where c.discussion_reply_id=r.id)  as c_count'), 
                        DB::raw('(select count(*) from favourites as f where f.discussion_reply_id=r.id ) as r_f_count')
                )
                ->where('r.id', $reply_id);

        $data = $query->first();

        return $data ? $data : false;
    }

    public function commentDetails($comment_id,$login_id) {
        $query = DB::table('comments as c', 'u.profile_icon')
                ->leftjoin('users as u', 'c.user_id', '=', 'u.id')
                ->select('c.*', 'u.profile_icon','u.name',
                DB::raw('(SELECT count(*)  from favourites as f where f.discussion_replay_comment_id = c.id ) AS f_count'), 
                DB::raw('(SELECT ifnull((select count(*) from favourites as f2 where f2.user_id = ' . $login_id . ' and f2.discussion_replay_comment_id = c.id),-1))  AS favourited')
                )
                ->where('c.id', $comment_id);

        $data = $query->first();

        return $data ? $data : false;
    }

    public function commentsList($reply_id,$login_id) {
        $query = DB::table('comments as c', 'u.profile_icon')
                ->leftjoin('users as u', 'c.user_id', '=', 'u.id')
                ->select('c.*', 'u.profile_icon','u.name',  
                  DB::raw('(SELECT count(*)  from favourites as f where f.discussion_replay_comment_id = c.id ) AS f_count'), 
                DB::raw('(SELECT ifnull((select count(*) from favourites as f2 where f2.user_id = ' . $login_id . ' and f2.discussion_replay_comment_id = c.id),-1))  AS favourited')
           )
                ->where('c.discussion_reply_id', $reply_id)
                ->where('u.status', '!=', 3)
                ->whereNotIn('c.id', DB::table('reports')->select('discussion_comment_id')->where('discussion_comment_id', '!=', null))
                ->orderBy('c.updated_at', 'asc');

        $data = $query->get();

        return $data ? $data : false;
    }
    public function fav_comment_details($comment_id,$login_id) {
        $query = DB::table('comments as c', 'u.profile_icon')
                ->leftjoin('users as u', 'c.user_id', '=', 'u.id')
                ->select('c.*', 'u.profile_icon','u.name',  
                  DB::raw('(SELECT count(*)  from favourites as f where f.discussion_replay_comment_id = c.id ) AS f_count'), 
                DB::raw('(SELECT ifnull((select count(*) from favourites as f2 where f2.user_id = ' . $login_id . ' and f2.discussion_replay_comment_id = c.id),-1))  AS favourited')
           )
                ->where('c.id', $comment_id)
                ->where('u.status', '!=', 3)
                ->whereNotIn('c.id', DB::table('reports')->select('discussion_comment_id')->where('discussion_comment_id', '!=', null))
                ->orderBy('c.updated_at', 'asc');

        $data = $query->first();

        return $data ? $data : false;
    }

    public function reasonsList() {
        $query = DB::table('report_reasons')
                ->orderBy('id', 'asc');

        $data = $query->get();

        return $data ? $data : false;
    }
    public function getfavoritedUsersList($type,$id)
    {
        if($type==1)
        {
            $query = DB::table('favourites as f')
            ->join('users as u', 'f.user_id', '=', 'u.id')
            ->select('u.id', 'u.profile_icon','u.name')
            ->where('f.discussion_id',$id)
            ->orderBy('f.updated_at', 'asc');
            $data = $query->get();
            return $data ? $data : false;
        }
        else if($type==2)
        {
            $query = DB::table('favourites as f')
            ->join('users as u', 'f.user_id', '=', 'u.id')
            ->select('u.id', 'u.profile_icon','u.name')
            ->where('f.discussion_reply_id',$id)
            ->orderBy('f.updated_at', 'asc');
            $data = $query->get();
            return $data ? $data : false;
        }
        else{
            $query = DB::table('favourites as f')
            ->join('users as u', 'f.user_id', '=', 'u.id')
            ->select('u.id', 'u.profile_icon','u.name')
            ->where('f.discussion_replay_comment_id',$id)
            ->orderBy('f.updated_at', 'asc');
            $data = $query->get();
            return $data ? $data : false;   
        }
    }


    //------------------------------------------------------------------------------------------
    //----------------------------------------------------------------------------------------------
}
