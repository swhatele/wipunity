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
use App\Repositories\EloquentRepositories\GuestRepository as GuestRepo;
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

class GuestController extends Controller {

    protected $request;
    protected $hasher;
    protected $userRepo;
    protected $admin;
    protected $tagRepo;
    protected $guestRepo;

    public function __construct(Request $request, Hasher $hasher, UserRepo $userRepo, Admin $admin, TagRepo $tagRepo, GuestRepo $guestRepo) {

        $this->request = $request;
        $this->hasher = $hasher;
        $this->userRepo = $userRepo;
        $this->admin = $admin;
        $this->tagRepo = $tagRepo;
        $this->guestRepo = $guestRepo;
    }

    /**
     * This is used to get the list of users based on all conditions
     */
    public function index(Request $request) {
        try {
           
            
            $discussion_list = $this->guestRepo->discussionsList();
            $report_reasons = $this->guestRepo->reasonsList();
            

            Session::forget('open_discussion_id');
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();  
            return view('guest/discussionList', compact('discussion_list', 'report_reasons', 'basins', 'tags'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

     
    public function discussionDetails($discussion_id) {
        try {

             $report_check = Reports::where('discussion_id', $discussion_id)->first();

            if ($report_check) {
                return redirect()->action('GuestController@index');
            }


            $discussion_details = $this->guestRepo->discussionDetails($discussion_id);

            $data ['basin_name'] = $discussion_details->basin_name;
            $data ['basin_color_code'] = $discussion_details->basin_color_code;
 
            $page = 1; //$request->get('page', 1);

            $paginate = 5;
            $offset = ($page - 1) * $paginate;

            $replies_list = DB::select("call sp_guest_replies_polls('$discussion_id','$paginate','$offset')");
             
     
//        $replies_list = $this->guestRepo->getRepliesList($discussion_id, $login_id); 
            $report_reasons = $this->guestRepo->reasonsList();
            $meetings = $this->guestRepo->meetingsList($discussion_id);

            $poll_answers1 = DB::table('poll_answer')->where('id', '<', '3')->get();
            $poll_answers2 = DB::table('poll_answer')->where('id', '>', '2')->get();
             
            if ($this->request->ajax()) {
                $data ['html'] = view('guest._partial_discussion_details', compact('discussion_details', 'replies_list',  'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2'))->__toString();
                $data ['http_status'] = Response::HTTP_OK;
                return json_encode($data);
            } else {

                Session::put("open_discussion_id", $discussion_id);
                  
                return view('guest/discussion_details', compact('discussion_details', 'replies_list', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2'));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function loadMoreReplies(Request $request) {
        try { 
            $discussion_id = $request->get('discussion_id');
            $last_reply_id = $request->get('last_reply_id');
 $load_more = $request->get('load_more');
 
            $discussion_details = $this->guestRepo->discussionDetails($discussion_id);
            $page = $request->get('page');

            $paginate = 5;
            $offset = ($page - 1) * $paginate;

           $replies_list = DB::select("call sp_guest_replies_polls('$discussion_id','$paginate','$offset')"); 
//           echo "call sp_guest_replies_polls('$discussion_id','$paginate','$offset')";
            $report_reasons = $this->guestRepo->reasonsList();
            $meetings = $this->guestRepo->meetingsList($discussion_id);

            $poll_answers1 = DB::table('poll_answer')->where('id', '<', '3')->get();
            $poll_answers2 = DB::table('poll_answer')->where('id', '>', '2')->get();

            if (count($replies_list) > 0) {
                if (count($replies_list) < 5) {
                    $data ['replies_count'] = 1;
                } else {
                    $data ['replies_count'] = 0;
                }
               if($load_more ==1){
                $data ['html'] = view('guest._partial_replies', compact('discussion_details', 'replies_list',  'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2'))->__toString();
                }else{
                $data ['html'] = view('guest._partial_replies2', compact('discussion_details', 'replies_list',  'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2'))->__toString();
                    
                }
                $data ['http_status'] = Response::HTTP_OK;
            } else {
                $data ['replies_count'] = 1;
                $data ['html'] = view('guest._partial_no_data')->__toString();
                $data ['http_status'] = Config::get('constants.HTTP_NO_DATA');
            }

            return json_encode($data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

     

    public function loadComments(Request $request) {
        try {
            $post = $request->all();
            

            $discussion_details = $this->guestRepo->discussionDetails($post['discussion_id']);

            $reply = $this->guestRepo->replyDetails($post['reply_id']);
            $type = 2;
            $comments = $this->guestRepo->commentsList($post['reply_id']);
            if (count($comments) > 0) {
                $arrResponse['html'] = view('dashboard._partial_comment', compact('discussion_details', 'reply', 'login_id', 'comments', 'type'))->__toString();
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.CommentsList');
            } else {
                $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                $arrResponse['message'] = Lang::get('global.somethingWentWrong');
            }

            return json_encode($arrResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }
 

    public function getMeetingDetails(Request $request) {

        try {
            $post = $this->request->all();
            
            $meeting_details = Meetings::select('*')->where('id', $post['meeting_id'])->first(); 
            if ($meeting_details) {
                
                $user = Users::find($meeting_details->user_id);
                $user_name =$user->name;
                if($user->profile_icon != null){
                $user_profile_icon = env('S3_URL_PROFILE').env('S3_BUCKET_DOCUMENTS').'/'.$user->profile_icon;
                }else{
                    $user_profile_icon = \URL::asset('resources/assets/images/profile_icon.png');
                }
                $arrResponse['data'] = $meeting_details;
                $arrResponse['data']['user_name'] =$user_name;
                $arrResponse['data']['user_profile_icon'] =$user_profile_icon;
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.Success');
            } else {
                $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                $arrResponse['message'] = Lang::get('global.somethingWentWrong');
            }

            return json_encode($arrResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }
 
    public function exploreDashboard(Request $request) {
        try {
             
           
            $discussion_list = $this->guestRepo->discussionsList();
            $report_reasons = $this->guestRepo->reasonsList();
            Session::forget('open_discussion_id');
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();  
            return view('guest/explore', compact( 'discussion_list', 'report_reasons',  'basins', 'tags'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }
    
    public function exploreDetails($discussion_id) {
        try {
 
            $report_check = Reports::where('discussion_id', $discussion_id)->first();

            if ($report_check) {
                return redirect()->action('GuestController@index');
            }


            $discussion_details = $this->guestRepo->discussionDetails($discussion_id);

            $data ['basin_name'] = $discussion_details->basin_name;
            $data ['basin_color_code'] = $discussion_details->basin_color_code;

           
            $page = 1; //$request->get('page', 1);

            $paginate = 5;
            $offset = ($page - 1) * $paginate;

           $replies_list = DB::select("call sp_guest_replies_polls('$discussion_id','$paginate','$offset')");
          
            $report_reasons = $this->guestRepo->reasonsList();
            $meetings = $this->guestRepo->meetingsList($discussion_id);

            $poll_answers1 = DB::table('poll_answer')->where('id', '<', '3')->get();
            $poll_answers2 = DB::table('poll_answer')->where('id', '>', '2')->get(); 
            if ($this->request->ajax()) {
                $data ['html'] = view('guest._partial_discussion_details', compact('discussion_details', 'replies_list', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2'))->__toString();
                $data ['http_status'] = Response::HTTP_OK;
                return json_encode($data);
            } else {

                Session::put("open_discussion_id", $discussion_id);
                return view('guest/exploreDetails', compact('discussion_details', 'replies_list', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2'));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function netwrokDashboard(Request $request) {
        try {
              
            $discussion_list = $this->guestRepo->discussionsList();
            $report_reasons = $this->guestRepo->reasonsList(); 

            Session::forget('open_discussion_id');
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();  
            return view('guest/network', compact( 'discussion_list', 'report_reasons', 'basins', 'tags'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    
    public function exploreView(Request $request){
        try{
             
            $report_reasons = $this->guestRepo->reasonsList();
            
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get(); 
            
            return view('guest/exploreView', compact( 'report_reasons', 'basins', 'tags'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }
    public function guestViewHowToUseTool(Request $request)
    {
        try{
            return view('guest/guestViewHowToUseTool');
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }
}
