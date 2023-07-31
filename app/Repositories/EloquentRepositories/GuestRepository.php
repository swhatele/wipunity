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
class GuestRepository extends Repository {

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
    public function discussionsList() {
 
        $query = DB::table('discussions as d')
                ->leftjoin('basins as b', 'd.basin_id', '=', 'b.id')
                ->leftjoin('users as u', 'd.user_id', '=', 'u.id')
                ->select('d.*', 'b.basin_name', 'b.basin_color_code', DB::raw('(SELECT count(*)  from favourites as f where f.discussion_id = d.id ) AS f_count'), 
                        DB::raw('(SELECT count(*)  from replies as r where r.discussion_id = d.id and r.id not in (select discussion_reply_id from reports where discussion_reply_id is not null) ) AS replies_count')
                )->whereNotIn('d.id',DB::table('reports')->select('discussion_id')->where('discussion_id','!=',null) )
                ->where('u.status' ,'!=', '3')
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
                        DB::raw('(SELECT count(*)  from replies as r where r.discussion_id = d.id ) AS replies_count') 
                )
                ->where(function($query ) use ($tag_ids,$basin_ids) {
                    $query->whereIn('d.basin_id', $basin_ids)
                    ->orWhereIn('dt.tag_id', $tag_ids);
                })
                ->where('u.status' ,'!=', '3')
                ->where('d.status',1)
               ->whereNotIn('d.id',DB::table('reports')->select('discussion_id')->where('discussion_id','!=',null) )
                 ->groupBy('d.id')
                ->orderBy('d.updated_at', 'desc');
//                echo $query->toSql();
        $data = $query->paginate(10);
        return $data ? $data : false;
    }
    
    public function searchDiscussionsList($query){
         $query = DB::table('discussions as d')
                ->leftjoin('basins as b', 'd.basin_id', '=', 'b.id')
                ->leftjoin('comments as c', 'd.id', '=', 'c.discussion_id')
                 ->leftjoin('replies as r', 'd.id', '=', 'r.discussion_id')
                 ->leftjoin('users as u', 'd.user_id', '=', 'u.id')
                ->select('d.*', 'b.basin_name', 'b.basin_color_code', 
                        DB::raw('(SELECT count(*)  from favourites as f where f.discussion_id = d.id ) AS f_count'), 
                        DB::raw('(SELECT count(*)  from replies as r where r.discussion_id = d.id ) AS replies_count') 
                )
                  ->where('d.topic', 'like', '%' . $query . '%')
                 ->orWhere('r.reply_text', 'like', '%' . $query . '%')
                 ->orWhere('c.comment_text', 'like', '%' . $query . '%')
                 ->where('u.status' ,'!=', '3')
                 ->where('d.status',1)
                 ->whereNotIn('d.id',DB::table('reports')->select('discussion_id')->where('discussion_id','!=',null) )
                 ->groupBy('d.id')
                ->orderBy('d.updated_at', 'desc');
        $data = $query->paginate(10);
        return $data ? $data : false;
    }

    public function getRepliesList($discussion_id, $login_id) {

        $replies = DB::table('replies as r')
                ->leftjoin('users as u', 'r.user_id', '=', 'u.id')
                ->select('r.id as id','r.reply_text as r_text','r.updated_at as updated_at', 'u.profile_icon', 
                        DB::raw('(select count(*) from comments as c where c.discussion_reply_id=r.id)  as c_count'), 
                        DB::raw('(select count(*) from favourites as f where f.discussion_reply_id=r.id ) as r_f_count'),
                        '1 as list_type'
                )
                ->where('r.discussion_id', $discussion_id)
                ->orderBy('r.updated_at', 'desc');
        
        $polls = DB::table('polls as p')
                ->leftjoin('users as u', 'p.user_id', '=', 'u.id')
                ->select('p.id as id','p.question as r_text ','p.updated_at as updated_at', 'u.profile_icon', DB::raw('(select 0  as c_count)'), 
                        DB::raw('(select 0 as r_f_count)'), 
                        DB::raw('(SELECT  0 AS favourited)'),
                         '2 as list_type'
                )
                ->where('p.discussion_id', $discussion_id)
                ->union($replies)
                ->orderBy('updated_at', 'desc');
                 

        $data = $polls->paginate(5);
 
        return $data ? $data : false;
    }

    public function loadReplies($discussion_id, $last_reply_id) {

        $query = DB::table('replies as r','u.profile_icon')
                ->leftjoin('users as u', 'r.user_id', '=', 'u.id')
                ->select('r.*', 'u.profile_icon', DB::raw('(select count(*) from comments as c where c.discussion_reply_id=r.id)  as c_count'), 
                        DB::raw('(select count(*) from favourites as f where f.discussion_reply_id=r.id ) as r_f_count')
                )
                ->where('r.discussion_id', $discussion_id)
                ->where('r.id', '<', $last_reply_id)
                ->orderBy('r.updated_at', 'desc')
                ->limit(5);
        $data = $query->get();

        return $data ? $data : false;
    }

    public function discussionDetails($id) {

        $query = DB::table('discussions as d')
                        ->leftjoin('basins as b', 'd.basin_id', '=', 'b.id')
                ->leftjoin('users as u', 'd.user_id', '=', 'u.id')
                        ->select('d.*', 'u.profile_icon',
                                'b.basin_name', 'b.basin_color_code', DB::raw('(SELECT count(*)  from favourites as f where f.discussion_id = d.id ) AS f_count'), 
                                DB::raw('(SELECT count(*)  from replies as r where r.discussion_id = d.id ) AS replies_count')
                        )
                        ->where('d.status',1)
                        ->where('d.id', $id)->first();
        return $query ? $query : false;
    }

    public function meetingsList($discussion_id) {
        $query = DB::table('meetings as m')
                ->leftjoin('users as u', 'm.user_id', '=', 'u.id')
                ->select('m.*', 'u.profile_icon')
                ->where('discussion_id', $discussion_id)
                ->where('meeting_date', '>=', $date = date('Y-m-d'))
                ->orWhere(function ($query) {
                    $query->where('meeting_date', '>=', $date = date('Y-m-d'))
                    ->where('start_time', '>', $date = date('H:i'));
                })
                ->orderBy('meeting_date', 'asc')
                ->limit(5);
        $data = $query->get();

        return $data ? $data : false;
    }

     

    public function replyDetails($reply_id) {
        $query = DB::table('replies as r','u.profile_icon')
                ->leftjoin('users as u', 'r.user_id', '=', 'u.id')
                ->select('r.*', 'u.profile_icon', DB::raw('(select count(*) from comments as c where c.discussion_reply_id=r.id)  as c_count'), 
                        DB::raw('(select count(*) from favourites as f where f.discussion_reply_id=r.id ) as r_f_count')
                )
                ->where('r.id', $reply_id);

        $data = $query->first();

        return $data ? $data : false;
    }

    public function commentDetails($comment_id) {
        $query = DB::table('comments as c','u.profile_icon')
                ->leftjoin('users as u', 'c.user_id', '=', 'u.id')
                ->select('c.*', 'u.profile_icon')
                ->where('c.id', $comment_id);

        $data = $query->first();

        return $data ? $data : false;
    }

    public function commentsList($reply_id) {
        $query = DB::table('comments as c', 'u.profile_icon')
                ->leftjoin('users as u', 'c.user_id', '=', 'u.id')
                ->select('c.*', 'u.profile_icon')
                ->where('c.discussion_reply_id', $reply_id)
                ->where('u.status', '!=',3)
                ->whereNotIn('c.id',DB::table('reports')->select('discussion_comment_id')->where('discussion_comment_id','!=',null) )
                ->orderBy('c.updated_at', 'asc');

        $data = $query->get();

        return $data ? $data : false;
    }

    public function reasonsList() {
        $query = DB::table('report_reasons')
                ->orderBy('id', 'asc');

        $data = $query->get();

        return $data ? $data : false;
    }

    //------------------------------------------------------------------------------------------
    //----------------------------------------------------------------------------------------------
}
