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
//use App\Jobs\sendingRealtimeNotifications;
use Log;
use DateTime;

class DashboardController extends Controller
{
    protected $request;
    protected $hasher;
    protected $userRepo;
    protected $admin;
    protected $tagRepo;
    protected $discussionRepo;

    public function __construct(Request $request, Hasher $hasher, UserRepo $userRepo, Admin $admin, TagRepo $tagRepo, DiscussionRepo $discussionRepo)
    {
        $this->request = $request;
        $this->hasher = $hasher;
        $this->userRepo = $userRepo;
        $this->admin = $admin;
        $this->tagRepo = $tagRepo;
        $this->discussionRepo = $discussionRepo;
    }

    public function home()
    {
        return view('landing/home');
    }

    /**
     * This is used to get the list of users based on all conditions
     */
    public function index(Request $request)
    {
        try {
            $login_id = session::get('looged_user_id');
            $search_text = '';
            Session::put("filters", 'off');
            Session::put("search", 'off');
            $filters = 'off';
            $search = 'off';
            $discussion_list = $this->discussionRepo->discussionsList($search_text, $filters, $login_id);

            $report_reasons = $this->discussionRepo->reasonsList();
            $user = Users::find($login_id);
            $first_login = $user->first_login;

            Session::forget('open_discussion_id');
            Session::forget('search_term');
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            Users::where(['id' => $login_id])->update(['first_login' => 2]);
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            $selected_tags = '';
            $selected_basins = '';
            $search_term = '';
            if ($this->request->ajax()) {
                if (count($discussion_list) > 0) {
                    $data ['html'] = view('dashboard._partial_discussions', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags'))->__toString();
                } else {
                    $data ['html'] = view('dashboard._partial_no_data', compact('login_id'))->__toString();
                }
                $data['lastPage'] = $discussion_list->lastPage();
                $data['currentPage'] = $discussion_list->currentPage();
                $data ['http_status'] = Response::HTTP_OK;
                return json_encode($data);
            } else {
                return view('dashboard/discussionList', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags', 'user', 'notfication_count', 'filters', 'selected_tags', 'selected_basins', 'search_term'));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function postDiscussion(Request $request)
    {
        try {
            $post = $request->all();
            $login_id = session::get('looged_user_id');

            $discussion_info['topic'] = $post['topic'];
            $discussion_info['basin_id'] = $post['basin_id'];
            $discussion_info['user_id'] = $login_id;
            $tag_ids = $post['tag_ids'];

            //Upload image to s3
            $uploaded_image = $request->file('upload_image');
            $discussion = Discussions::create($discussion_info);
            if (isset($uploaded_image)) {
                $s3 = AWS::createClient('s3');
                try {
                    $img_guid = 'Discussion-' . $discussion->id . '-' . date('YmdHis');
                    $imageFileName = $img_guid . '.png';
                    $imageUpload = $s3->putObject(array(
                        'Key' => env('S3_BUCKET_DOCUMENTS') . '/' . $imageFileName,
                        'Bucket' => env('S3_BUKET_PROFILE'),
                        'SourceFile' => $uploaded_image
                    ));
                    if (isset($imageUpload) && $imageUpload['@metadata']['statusCode'] == 200) {
                        Discussions::where('id', $discussion->id)->update(['image_key_id' => $imageFileName]);
                    }
                } catch (Exception $e) {
                    echo 'Caught exception: ', $e->getMessage(), "\n";
                }
            }

            //Upload image to s3
            //save tags
            $tags = explode(',', $tag_ids);

            $discussion_tags = [];
            if (count($tags) > 0) {
                foreach ($tags as $tag) {
//                    echo $tag;
                    array_push($discussion_tags, array('discussion_id' => $discussion->id, "tag_id" => $tag));
                }
//                dd($discussion_tags);
                DiscussionTags::insert($discussion_tags);
            }


            $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
            $arrResponse['message'] = Lang::get('global.DiscussionCreated');

            return response()->json($arrResponse, 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

    public function updateDiscussion(Request $request)
    {
        try {
            $post = $request->all();
            $login_id = session::get('looged_user_id');
            $discussion_id = $post['edit_discussion_id'];
            $discussion_info['topic'] = $post['topic'];
            $tag_ids = $post['tag_ids'];
            $uploaded_image = $request->file('upload_image');
            if (isset($uploaded_image)) {
                $s3 = AWS::createClient('s3');
                try {
                    $img_guid = 'Discussion-' . $discussion_id . '-' . date('YmdHis');
                    $imageFileName = $img_guid . '.png';
                    $imageUpload = $s3->putObject(array(
                        'Key' => env('S3_BUCKET_DOCUMENTS') . '/' . $imageFileName,
                        'Bucket' => env('S3_BUKET_PROFILE'),
                        'SourceFile' => $uploaded_image
                    ));
                    if (isset($imageUpload) && $imageUpload['@metadata']['statusCode'] == 200) {
                        $discussion_info['image_key_id'] = $imageFileName;
                    }
                } catch (Exception $e) {
                    throw new Exception($e->getMessage(), $e->getCode());
                }
            }

            $discussion = Discussions::where('id', $discussion_id)->update($discussion_info);
            //save tags
            $tags = explode(',', $tag_ids);

            $discussion_tags = [];
            if (count($tags) > 0) {
                foreach ($tags as $tag) {
                    array_push($discussion_tags, array('discussion_id' => $discussion_id, "tag_id" => $tag));
                }
                DiscussionTags::where('discussion_id', $discussion_id)->delete();
                DiscussionTags::insert($discussion_tags);
            }
            $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
            $arrResponse['message'] = Lang::get('global.DiscussionCreated');
            return response()->json($arrResponse, 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function searchDiscussions(Request $request)
    {
        try {
            $login_id = session::get('looged_user_id');
            $type = $request->get('type');
            if ($type == 1) {
                $search_term = $request->get('query');
                Session::put("search_term", $search_term);
            } else {
                $search_term = Session::get("search_term");
            }
            $discussion_list = $this->discussionRepo->searchDiscussionsList($search_term, $login_id);
//            dd($discussion_list);
            Session::put("filters", 'off');

            $selected_tags = '';
            $selected_basins = '';
            $report_reasons = $this->discussionRepo->reasonsList();
            $user = Users::find($login_id);
            $first_login = $user->first_login;
            Session::put("search", 'on');
//            Session::forget('open_discussion_id');

            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            Users::where(['id' => $login_id])->update(['first_login' => 2]);
            if ($this->request->ajax()) {
                if (count($discussion_list) > 0) {
                    $data ['html'] = view('dashboard._partial_search_discussions', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags'))->__toString();
                } else {
                    $data ['html'] = view('dashboard._partial_no_data', compact('login_id'))->__toString();
                }
                $data ['http_status'] = Response::HTTP_OK;
                $data['lastPage'] = $discussion_list->lastPage();
                $data['currentPage'] = $discussion_list->currentPage();
                return json_encode($data);
            } else {
                $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
                return view('dashboard/discussionList', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags', 'user', 'notfication_count', 'selected_tags', 'selected_basins', 'search_term'));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function filters(Request $request)
    {
        try {
            $login_id = session::get('looged_user_id');
            Session::put("filters", 'on');
            $tag_ids = explode(',', $request->get('tag_ids'));
            Session::put("tag_ids", $tag_ids);
            $basin_ids = explode(',', $request->get('basin_ids'));
            Session::put("basin_ids", $basin_ids);

            $discussion_list = $this->discussionRepo->discussionsListWithFilters($tag_ids, $basin_ids, $login_id);
//            dd($discussion_list);
            $report_reasons = $this->discussionRepo->reasonsList();
            $user = Users::find($login_id);
            $first_login = $user->first_login;
            $search_term = '';
            $data['lastPage'] = $discussion_list->lastPage();
            $data['currentPage'] = $discussion_list->currentPage();
//            Session::forget('open_discussion_id');
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            Users::where(['id' => $login_id])->update(['first_login' => 2]);
            if ($this->request->ajax()) {
                if (count($discussion_list) > 0) {
                    $data ['html'] = view('dashboard._partial_discussions', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags'))->__toString();
                } else {
                    $data ['html'] = view('dashboard._partial_no_data', compact('login_id'))->__toString();
                }
                $data ['http_status'] = Response::HTTP_OK;
                return json_encode($data);
            } else {
                $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
                return view('dashboard/discussionList', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags', 'user', 'notfication_count', 'selected_tags', 'selected_basins', 'search_term'));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function discussionsFilters(Request $request)
    {
//        try {
        $login_id = session::get('looged_user_id');
        $tag_ids = Session::get("tag_ids");
        $basin_ids = Session::get("basin_ids");
        if ($request->get('tag_ids')=="") {
            $tag_ids=array();
        } else {
            $tag_ids = explode(',', $request->get('tag_ids'));
        }
        Session::put("tag_ids", $tag_ids);
        if ($request->get('basin_ids')=="") {
            $basin_ids=array();
        } else {
            $basin_ids = explode(',', $request->get('basin_ids'));
        }
        Session::put("basin_ids", $basin_ids);


        $discussion_list = $this->discussionRepo->discussionsListWithFilters($tag_ids, $basin_ids, $login_id);
//            dd($discussion_list);
        $report_reasons = $this->discussionRepo->reasonsList();
        $user = Users::find($login_id);
        $first_login = $user->first_login;

//        Session::forget('open_discussion_id');
        $basins = DB::table('basins')->get();
        $tags = DB::table('tags')->get();
        $selected_tags = implode(',', $tag_ids);
        $selected_basins = implode(',', $basin_ids);
        $search_term = '';
        Users::where(['id' => $login_id])->update(['first_login' => 2]);
        if ($this->request->ajax()) {
            if (count($discussion_list) > 0) {
                $data ['html'] = view('dashboard._partial_discussions', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags'))->__toString();
            } else {
                $data ['html'] = view('dashboard._partial_no_data', compact('login_id'))->__toString();
            }
            $data ['http_status'] = Response::HTTP_OK;
            return json_encode($data);
        } else {
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            return view('dashboard/discussionList', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags', 'user', 'notfication_count', 'selected_tags', 'selected_basins', 'search_term'));
        }
//        } catch (Exception $e) {
//            throw new Exception($e->getMessage(), $e->getCode());
//        } catch (\Illuminate\Database\QueryException $qe) {
//            throw new Exception($qe->getMessage(), $qe->getCode());
//        }
    }

    public function discussionDetails($discussion_id)
    {
        try {
            $login_id = session::get('looged_user_id');
            $report_check = Reports::where('discussion_id', $discussion_id)->first();

            if ($report_check) {
                return redirect()->action('DashboardController@index');
            }


            $discussion_details = $this->discussionRepo->discussionDetails($discussion_id, $login_id);

            $data ['basin_name'] = $discussion_details->basin_name;
            $data ['basin_color_code'] = $discussion_details->basin_color_code;
//            dd($discussion_details);

            $user = Users::find($login_id);
            $page = 1; //$request->get('page', 1);

            $paginate = 5;
            $offset = ($page - 1) * $paginate;

            $replies_list = DB::select("call sp_replies_polls('$discussion_id','$login_id','$paginate','$offset')");
//            dd($replies_list);
//        $replies_list = $this->discussionRepo->getRepliesList($discussion_id, $login_id);
            $report_reasons = $this->discussionRepo->reasonsList();
            $meetings = $this->discussionRepo->meetingsList($discussion_id);

            $poll_answers1 = DB::table('poll_answer')->where('id', '<', '3')->get();
            $poll_answers2 = DB::table('poll_answer')->where('id', '>', '2')->get();
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            $discussionVSTags = $this->discussionRepo->discussionVSTags($discussion_id);
//            dd($discussionVSTags);
            if ($this->request->ajax()) {
                $data ['html'] = view('dashboard._partial_discussion_details', compact('discussion_details', 'replies_list', 'login_id', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2', 'user'))->__toString();
                $data ['http_status'] = Response::HTTP_OK;
                return json_encode($data);
            } else {
                $filters = Session::get("filters");
                $search = Session::get("search");
                $search_term = Session::get("search_term");
                Session::put("open_discussion_id", $discussion_id);

                return view('dashboard/discussion_details', compact('discussion_details', 'replies_list', 'login_id', 'basins', 'tags', 'discussionVSTags', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2', 'user', 'notfication_count', 'filters', 'search', 'search_term'));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function searchDiscussionDetails($discussion_id, $result_type, $result_id)
    {
        try {
            $login_id = session::get('looged_user_id');
            $report_check = Reports::where('discussion_id', $discussion_id)->first();

            if ($report_check) {
                return redirect()->action('DashboardController@index');
            }
            if ($result_type == 2) {
                return redirect()->action('DashboardController@loadSearchReply', ['reply_id' => $result_id]);
            }
            if ($result_type == 3) {
                return redirect()->action('DashboardController@loadSearchComment', ['comment_id' => $result_id]);
            }
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();


            $discussion_details = $this->discussionRepo->discussionDetails($discussion_id, $login_id);
            $discussionVSTags = $this->discussionRepo->discussionVSTags($discussion_id);
            $data ['basin_name'] = $discussion_details->basin_name;
            $data ['basin_color_code'] = $discussion_details->basin_color_code;
            //dd($discussion_details);
            $user = Users::find($login_id);
            $page = 1; //$request->get('page', 1);

            $paginate = 5;
            $offset = ($page - 1) * $paginate;

            $replies_list = DB::select("call sp_replies_polls('$discussion_id','$login_id','$paginate','$offset')");
//            dd($replies_list);
//        $replies_list = $this->discussionRepo->getRepliesList($discussion_id, $login_id);
            $report_reasons = $this->discussionRepo->reasonsList();
            $meetings = $this->discussionRepo->meetingsList($discussion_id);

            $poll_answers1 = DB::table('poll_answer')->where('id', '<', '3')->get();
            $poll_answers2 = DB::table('poll_answer')->where('id', '>', '2')->get();
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            if ($this->request->ajax()) {
                $data ['html'] = view('dashboard._partial_discussion_details', compact('discussion_details', 'replies_list', 'login_id', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2', 'user', 'basins', 'tags', 'discussionVSTags'))->__toString();
                $data ['http_status'] = Response::HTTP_OK;
                return json_encode($data);
            } else {
                $filters = Session::get("filters");
                $search = Session::get("search");
                $search_term = Session::get("search_term");
                Session::put("open_discussion_id", $discussion_id);

                return view('dashboard/discussion_details', compact('discussion_details', 'replies_list', 'login_id', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2', 'user', 'notfication_count', 'filters', 'search', 'search_term', 'basins', 'tags', 'discussionVSTags'));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function loadMoreReplies(Request $request)
    {
        try {
//            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            $discussion_id = $request->get('discussion_id');
            $last_reply_id = $request->get('last_reply_id');
            $load_more = $request->get('load_more');
            $discussion_details = $this->discussionRepo->discussionDetails($discussion_id, $login_id);
            $page = $request->get('page');

            $paginate = 5;
            $offset = ($page - 1) * $paginate;

            $replies_list = DB::select("call sp_replies_polls('$discussion_id','$login_id','$paginate','$offset')");

//    dd($replies_list);
//        $replies_list = $this->discussionRepo->getRepliesList($discussion_id, $login_id);
            $report_reasons = $this->discussionRepo->reasonsList();
            $meetings = $this->discussionRepo->meetingsList($discussion_id);

            $poll_answers1 = DB::table('poll_answer')->where('id', '<', '3')->get();
            $poll_answers2 = DB::table('poll_answer')->where('id', '>', '2')->get();

            if (count($replies_list) > 0) {
                if (count($replies_list) < 5) {
                    $data ['replies_count'] = 1;
                } else {
                    $data ['replies_count'] = 0;
                }
                if ($load_more == 1) {
                    $data ['html'] = view('dashboard._partial_replies', compact('discussion_details', 'replies_list', 'login_id', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2'))->__toString();
                } else {
                    $data ['html'] = view('dashboard._partial_replies2', compact('discussion_details', 'replies_list', 'login_id', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2'))->__toString();
                }
                $data ['http_status'] = Response::HTTP_OK;
            } else {
                $data ['replies_count'] = 1;
                $data ['html'] = view('dashboard._partial_no_data')->__toString();
                $data ['http_status'] = Config::get('constants.HTTP_NO_DATA');
            }

            return json_encode($data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function postReply(Request $request)
    {
        try {
            $post = $this->request->all();
//            dd($post['reply']);


            $login_id = session::get('looged_user_id');
            $reply_info['discussion_id'] = $post['discussion_id'];
            $reply_info['reply_text'] = $this->getMentions($post['reply']);
            $reply_info['reply_text_mentions'] = $post['reply'];
            $reply_info['user_id'] = $login_id;

            $uploaded_image = $request->file('upload_file');
            if (isset($uploaded_image)) {
                $s3 = AWS::createClient('s3');
                try {
                    $img_guid = 'Reply-' . $post['discussion_id'] . '-' . date('YmdHis');
                    $imageFileName = $img_guid . '.png';
                    $imageUpload = $s3->putObject(array(
                        'Key' => env('S3_BUCKET_DOCUMENTS') . '/' . $imageFileName,
                        'Bucket' => env('S3_BUKET_PROFILE'),
                        'SourceFile' => $uploaded_image
                    ));
//                    Log::info($imageUpload);

                    if (isset($imageUpload) && $imageUpload['@metadata']['statusCode'] == 200) {
                        $reply_info['image_key_id'] = $imageFileName;
                    }
                } catch (Exception $e) {
                    throw new Exception($e->getMessage(), $e->getCode());
                }
            }

            $store_reply = Replies::create($reply_info);

            if ($store_reply) {
                //create notification for new reply

                $notification['discussion_reply_id'] = $store_reply->id;
                $notification['notification_type_id'] = 1;
                $notification['user_id'] = $login_id;
                $notification['discussion_id'] = $post['discussion_id'];
//                  Log::info($notification);
                AddUserNotifications::dispatch($notification);

                $activity_details['notification_type_id'] = 1;
                $activity_details['user_id'] = $login_id;
                $activity_details['discussion_id'] = $post['discussion_id'];
//                Log::info($activity_details);
//                sendingRealtimeNotifications::dispatch($activity_details);
                $box_alignment = $post['box_alignment'];
                $discussion_details = $this->discussionRepo->discussionDetails($post['discussion_id'], $login_id);
                $reply = $this->discussionRepo->replyDetails($store_reply->id);
                $arrResponse['html'] = view('dashboard._partial_reply', compact('discussion_details', 'reply', 'login_id', 'box_alignment'))->__toString();
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.ReplyPostedSuccessfully');
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

    public function updateReply(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');

            $reply_info['reply_text'] = $this->getMentions($post['reply']);
            $reply_info['reply_text_mentions'] = $post['reply'];

            $reply_id = $post['reply_id'];

            $box_alignment = $post['box_alignment'];
            $uploaded_image = $request->file('upload_file');
            if (isset($uploaded_image)) {
                $s3 = AWS::createClient('s3');
                try {
                    $img_guid = 'Reply-' . $post['discussion_id'] . '-' . date('YmdHis');
                    $imageFileName = $img_guid . '.png';
                    $imageUpload = $s3->putObject(array(
                        'Key' => env('S3_BUCKET_DOCUMENTS') . '/' . $imageFileName,
                        'Bucket' => env('S3_BUKET_PROFILE'),
                        'SourceFile' => $uploaded_image
                    ));
//                    Log::info($imageUpload);

                    if (isset($imageUpload) && $imageUpload['@metadata']['statusCode'] == 200) {
                        $reply_info['image_key_id'] = $imageFileName;
                    }
                } catch (Exception $e) {
                    throw new Exception($e->getMessage(), $e->getCode());
                }
            }
            $update_reply = Replies::where(['id' => $reply_id])->update($reply_info);

            if ($update_reply) {
                $discussion_details = $this->discussionRepo->discussionDetails($post['discussion_id'], $login_id);
                $reply = $this->discussionRepo->replyDetails($reply_id);
                $arrResponse['html'] = view('dashboard._partial_reply', compact('discussion_details', 'reply', 'login_id', 'box_alignment'))->__toString();
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.ReplyPostedSuccessfully');
                $arrResponse['reply_text'] = $reply_info['reply_text'];
                $arrResponse['image_key_id'] = env('S3_URL_PROFILE').env('S3_BUCKET_DOCUMENTS').'/'.$reply->image_key_id ;
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

    public function postComment(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            $comment_info['discussion_id'] = $post['discussion_id'];
            $comment_info['discussion_reply_id'] = $post['reply_id'];
            $comment_info['comment_text'] = $this->getMentions($post['comment']);
            $comment_info['user_id'] = $login_id;
            $comment_info['comment_text_mentions'] = $post['comment'];
            $uploaded_image = $request->file('upload_file');
            if (isset($uploaded_image)) {
                $s3 = AWS::createClient('s3');
                try {
                    $img_guid = 'Comment-' . $post['discussion_id'] . '-' . date('YmdHis');
                    $imageFileName = $img_guid . '.png';
                    $imageUpload = $s3->putObject(array(
                        'Key' => env('S3_BUCKET_DOCUMENTS') . '/' . $imageFileName,
                        'Bucket' => env('S3_BUKET_PROFILE'),
                        'SourceFile' => $uploaded_image
                    ));
//                    Log::info($imageUpload);

                    if (isset($imageUpload) && $imageUpload['@metadata']['statusCode'] == 200) {
                        $comment_info['image_key_id'] = $imageFileName;
                    }
                } catch (Exception $e) {
                    throw new Exception($e->getMessage(), $e->getCode());
                }
            }


            $store_comment = Comments::create($comment_info);
            if ($store_comment) {
                $notification['discussion_comment_id'] = $store_comment->id;
                $notification['notification_type_id'] = 2;
                $notification['user_id'] = $login_id;
                $notification['discussion_id'] = $post['discussion_id'];
                $notification['discussion_reply_id'] = $post['reply_id'];
                AddUserNotifications::dispatch($notification);

                $activity_details['notification_type_id'] = 2;
                $activity_details['user_id'] = $login_id;
                $activity_details['discussion_id'] = $post['discussion_id'];
//                sendingRealtimeNotifications::dispatch($activity_details);

                $discussion_details = $this->discussionRepo->discussionDetails($store_comment->discussion_id, $login_id);

                $reply = $this->discussionRepo->replyDetails($store_comment->discussion_reply_id, $login_id);

                $comment = $this->discussionRepo->commentDetails($store_comment->id, $login_id);
                $type = 1;
                $arrResponse['html'] = view('dashboard._partial_comment', compact('discussion_details', 'reply', 'login_id', 'comment', 'type'))->__toString();
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.CommentPostedSuccessfully');
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

    public function updateComment(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            $comment_id = $post['comment_id'];
            $comment_info['comment_text'] = $this->getMentions($post['comment']);
            $comment_info['comment_text_mentions'] = $post['comment'];
            $uploaded_image = $request->file('upload_file');
            if (isset($uploaded_image)) {
                $s3 = AWS::createClient('s3');
                try {
                    $img_guid = 'Comment-' . $post['discussion_id'] . '-' . date('YmdHis');
                    $imageFileName = $img_guid . '.png';
                    $imageUpload = $s3->putObject(array(
                        'Key' => env('S3_BUCKET_DOCUMENTS') . '/' . $imageFileName,
                        'Bucket' => env('S3_BUKET_PROFILE'),
                        'SourceFile' => $uploaded_image
                    ));
//                    Log::info($imageUpload);

                    if (isset($imageUpload) && $imageUpload['@metadata']['statusCode'] == 200) {
                        $comment_info['image_key_id'] = $imageFileName;
                    }
                } catch (Exception $e) {
                    throw new Exception($e->getMessage(), $e->getCode());
                }
            }


            $store_comment = Comments::where('id', $comment_id)->update($comment_info);
            if ($store_comment) {
                $comment = $this->discussionRepo->commentDetails($comment_id);
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.CommentPostedSuccessfully');
                $arrResponse['comment_text'] = $this->getMentions($post['comment']);
                $arrResponse['image_key_id'] =env('S3_URL_PROFILE').env('S3_BUCKET_DOCUMENTS').'/'. $comment->image_key_id;
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


    public function loadComments(Request $request)
    {
        try {
            $post = $request->all();
            $login_id = session::get('looged_user_id');

            $discussion_details = $this->discussionRepo->discussionDetails($post['discussion_id'], $login_id);

            $reply = $this->discussionRepo->replyDetails($post['reply_id']);
            $type = 2;
            $comments = $this->discussionRepo->commentsList($post['reply_id'], $login_id);
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

    public function makeFavoriteReply(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            if ($post['type'] == 1) {
                $favourite['discussion_id'] = $post['reply_id'];
            }
            if ($post['type'] == 2) {
                $favourite['discussion_reply_id'] = $post['reply_id'];
            }
            if ($post['type'] == 3) {
                $favourite['discussion_replay_comment_id'] = $post['reply_id'];
            }
            $favourite['user_id'] = $login_id;

            $check_favourite = Favourites::where($favourite)->first();
            if ($check_favourite) {
                $store_favourite = Favourites::where($favourite)->delete();
            } else {
                $store_favourite = Favourites::create($favourite);
            }
            $arrResponse['test'] = json_encode($favourite);

            if ($store_favourite) {
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.DiscussionFavouritedSuccessfully');
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

    public function reportPost(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            if ($post['type'] == 1) {
                $report['discussion_id'] = $post['reply_id'];
            }
            if ($post['type'] == 2) {
                $report['discussion_reply_id'] = $post['reply_id'];
            }
            if ($post['type'] == 3) {
                $report['discussion_comment_id'] = $post['reply_id'];
            }
            $report['user_id'] = $login_id;
            $report['report_reason_id'] = $post['report_reason'];



            $store_report = Reports::create($report);

            if ($store_report) {
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.ReportedSuccessfully');
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

    public function followDiscussion(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');

            $store['discussion_id'] = $post['discussion_id'];
            $store['user_id'] = $login_id;
            $check = Followers::where($store)->first();
            if ($check) {
                Followers::where($store)->delete();
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.ReportedSuccessfully');
            } else {
                $save = Followers::create($store);
                if ($save) {
                    $notification['followers_id'] = $save->id;
                    $notification['notification_type_id'] = 3;
                    $notification['user_id'] = $login_id;
                    $notification['discussion_id'] = $post['discussion_id'];
                    AddUserNotifications::dispatch($notification);
                    $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                    $arrResponse['message'] = Lang::get('global.ReportedSuccessfully');
                } else {
                    $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                    $arrResponse['message'] = Lang::get('global.somethingWentWrong');
                }
            }

            return json_encode($arrResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function postNewPoll(Request $request)
    {
//        try {
        $post = $this->request->all();
        $login_id = session::get('looged_user_id');

        $store['discussion_id'] = $post['discussion_id'];
        $store['user_id'] = $login_id;
        $store['question'] = $post['question'];
        $store['poll_type'] = $post['poll_type'];
        $save = Polls::create($store);
        if ($save) {
            $notification['poll_id'] = $save->id;
            $notification['notification_type_id'] = 5;
            $notification['user_id'] = $login_id;
            $notification['discussion_id'] = $post['discussion_id'];

            AddUserNotifications::dispatch($notification);

//                $activity_details['notification_type_id'] = 4;
//                $activity_details['user_id'] = $login_id;
//                $activity_details['discussion_id'] = $post['discussion_id'];
//
            if ($post['poll_type'] == 1) {
                $poll_answers = DB::table('poll_answer')->where('id', '<', '3')->get();
            } else {
                $poll_answers = DB::table('poll_answer')->where('id', '>', '2')->get();
            }
            $poll_id = $save->id;

            $poll = DB::table('polls as p')->select('p.*', 'u.name', 'u.profile_icon')
                    ->leftjoin('users as u', 'p.user_id', '=', 'u.id')
                    ->where('p.id', $poll_id)
                    ->first();

//                 dd($poll);
            $arrResponse['html'] = view('dashboard._partial_poll', compact('poll_answers', 'poll'))->__toString();
            $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
            $arrResponse['message'] = Lang::get('global.PollAddedSuccessfully');
        } else {
            $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
            $arrResponse['message'] = Lang::get('global.somethingWentWrong');
        }


        return json_encode($arrResponse);
//        } catch (Exception $e) {
//            throw new Exception($e->getMessage(), $e->getCode());
//        } catch (\Illuminate\Database\QueryException $qe) {
//            throw new Exception($qe->getMessage(), $qe->getCode());
//        }
    }

    public function answerPollQuestion(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');

            $store['discussion_id'] = $post['discussion_id'];
            $store['user_id'] = $login_id;
            $store['poll_id'] = $post['poll_id'];

            $check = PollsAnswers::where($store)->get();
//            dd($check);
            if (count($check) == 0) {
                $store['answer_id'] = $post['answer_id'];
                $save = PollsAnswers::create($store);
                if ($save) {
                    $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                    $arrResponse['message'] = Lang::get('global.AnsweredSuccessfully');
                } else {
                    $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                    $arrResponse['message'] = Lang::get('global.somethingWentWrong');
                }
            } else {
                $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                $arrResponse['message'] = "Poll answered already.";
            }
            return json_encode($arrResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function postMeeting(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            $discussion_id = $post['discussion_id'];
            $meeting['discussion_id'] = $post['discussion_id'];
            $meeting['user_id'] = $login_id;
            if (strlen($post['venue_name']) > 1) {
                $meeting['venue_name'] = $post['venue_name'];
                $location = $post['venue_name'];
            } else {
                $meeting['street_1'] = $post['street_1'];
                $meeting['city'] = $post['city'];
                $meeting['state'] = $post['state'];
                $meeting['zipcode'] = $post['zipcode'];
                $location = $post['street_1'] . ',' . $post['city'] . ',' . $post['city'] . '-' . $post['zipcode'];
            }

            $posted_meeting_id = $post['meeting_id'];
            $meeting['meeting_date'] = date('Y-m-d', strtotime($post['meeting_date']));
            $meeting['start_time'] = date('H:i', strtotime($post['start_time']));
            $meeting['end_time'] = date('H:i', strtotime($post['end_time']));
            $meeting['purpose_of_meeting'] = $post['purpose_of_meeting'];
            $meeting['additional_conference_details'] = $post['additional_conference_details'];
//            dd($meeting);

            $query = Meetings::where('discussion_id', $discussion_id);

            if ($posted_meeting_id != -1) {
                $query->where('id', '=', $posted_meeting_id);
            } else {
                $query->where('meeting_date', '=', date('Y-m-d', strtotime($post['meeting_date'])))
                        ->where('start_time', '=', date('H:i', strtotime($post['start_time'])));
            }
            $check_meeting = $query->first();

            if ($posted_meeting_id == -1) {
                if (!$check_meeting && $posted_meeting_id == -1) {
                    $save = Meetings::create($meeting);
                    $user = Users::find($login_id);
                    $mailBody = array(
                        'email' => $user->email,
                        'name' => $user->name,
                        'meeting_date' => date('m-d-Y', strtotime($post['meeting_date'])),
                        'start_time' => date('h:i a', strtotime($post['start_time'])),
                        'end_time' => date('h:i a', strtotime($post['end_time'])),
                        'location' => $location,
                        'additional_conference_details' => $post['additional_conference_details'],
                        'purpose_of_meeting' => $post['purpose_of_meeting'],
                    );
                    Mail::send('emails.meetingScheduled', $mailBody, function ($m) use ($mailBody) {
                        $m->from(env('MAIL_FROM_ADDRESS'), Lang::get('global.TNCTeam'));
                        $m->to($mailBody['email'], $mailBody['name'])->subject("WIP: You've Scheduled a meeting!");
                    });

                    if ($save) {
                        $notification['meeting_id'] = $save->id;
                        $notification['notification_type_id'] = 4;
                        $notification['user_id'] = $login_id;
                        $notification['discussion_id'] = $post['discussion_id'];
                        AddUserNotifications::dispatch($notification);

                        $activity_details['notification_type_id'] = 3;
                        $activity_details['user_id'] = $login_id;
                        $activity_details['discussion_id'] = $post['discussion_id'];
//                        sendingRealtimeNotifications::dispatch($activity_details);


                        $meeting_id = $save->id;
                        $meeting = DB::table('meetings as m')
                                        ->leftjoin('users as u', 'm.user_id', '=', 'u.id')
                                        ->select('m.*', 'u.profile_icon', 'u.name')
                                        ->where('m.id', $meeting_id)->first();

                        $arrResponse['html'] = view('dashboard._partial_meeting', compact('meeting', 'meeting_id'))->__toString();
                        $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                        $arrResponse['message'] = Lang::get('global.MeetingAddedSuccessfully');
                    } else {
                        $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                        $arrResponse['message'] = Lang::get('global.somethingWentWrong');
                    }
                } else {
                    $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                    $arrResponse['message'] = Lang::get('global.MeetingAlreadyplanned');
                }
            } else {
                if ($check_meeting) {
                    $user = Users::find($login_id);
                    $update = Meetings::where(['id' => $posted_meeting_id])->update($meeting);
                    if ($update) {
                        $mailBody = array(
                            'email' => $user->email,
                            'name' => $user->name,
                            'meeting_date' => date('m-d-Y', strtotime($post['meeting_date'])),
                            'start_time' => date('h:i a', strtotime($post['start_time'])),
                            'end_time' => date('h:i a', strtotime($post['end_time'])),
                            'location' => $location,
                            'purpose_of_meeting' => $post['purpose_of_meeting'],
                            'additional_conference_details' => $post['additional_conference_details'],
                        );
                        Mail::send('emails.meetingScheduled', $mailBody, function ($m) use ($mailBody) {
                            $m->from(env('MAIL_FROM_ADDRESS'), Lang::get('global.TNCTeam'));
                            $m->to($mailBody['email'], $mailBody['name'])->subject('Meeting Scheduled Email');
                        });
                        $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                        $arrResponse['message'] = Lang::get('global.MeetingUpdatedSuccessfully');
                    } else {
                        $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                        $arrResponse['message'] = Lang::get('global.somethingWentWrong');
                    }
                } else {
                    $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                    $arrResponse['message'] = Lang::get('global.MeetingAlreadyplanned');
                }
            }


            return json_encode($arrResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function updateMeeting(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            $discussion_id = $post['discussion_id'];
            $meeting['discussion_id'] = $post['discussion_id'];
            $meeting['user_id'] = $login_id;
            if (strlen($post['venue_name']) > 1) {
                $meeting['venue_name'] = $post['venue_name'];
                $location = $post['venue_name'];
            } else {
                $meeting['street_1'] = $post['street_1'];
                $meeting['city'] = $post['city'];
                $meeting['state'] = $post['state'];
                $meeting['zipcode'] = $post['zipcode'];
                $location = $post['street_1'] . ',' . $post['city'] . ',' . $post['city'] . '-' . $post['zipcode'];
            }

            $posted_meeting_id = $post['meeting_id'];
            $meeting['meeting_date'] = date('Y-m-d', strtotime($post['meeting_date']));
            $meeting['start_time'] = date('H:i', strtotime($post['start_time']));
            $meeting['end_time'] = date('H:i', strtotime($post['end_time']));
            $meeting['purpose_of_meeting'] = $post['purpose_of_meeting'];
            $meeting['additional_conference_details'] = $post['additional_conference_details'];

            $query = Meetings::where('discussion_id', $discussion_id);

            if ($posted_meeting_id != -1) {
                $query->where('id', '!=', $posted_meeting_id);
            } else {
                $query->where('meeting_date', '=', date('Y-m-d', strtotime($post['meeting_date'])))
                        ->where('start_time', '=', date('H:i', strtotime($post['start_time'])));
            }
            $check_meeting = $query->first();


            if (!$check_meeting) {
                $update = Meetings::where(['id' => $posted_meeting_id])->update($meeting);
                if ($update) {
                    $mailBody = array(
                        'email' => $user->email,
                        'name' => $user->name,
                        'meeting_date' => date('m-d-Y', strtotime($post['meeting_date'])),
                        'start_time' => date('h:i a', strtotime($post['start_time'])),
                        'end_time' => date('h:i a', strtotime($post['end_time'])),
                        'location' => $location,
                        'purpose_of_meeting' => $post['purpose_of_meeting'],
                        'additional_conference_details' => isset($post['additional_conference_details'])?$post['additional_conference_details']:'',

                    );
                    Mail::send('emails.meetingScheduled', $mailBody, function ($m) use ($mailBody) {
                        $m->from(env('MAIL_FROM_ADDRESS'), Lang::get('global.TNCTeam'));
                        $m->to($mailBody['email'], $mailBody['name'])->subject('Meeting Scheduled Email');
                    });
                    $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                    $arrResponse['message'] = Lang::get('global.MeetingUpdatedSuccessfully');
                } else {
                    $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                    $arrResponse['message'] = Lang::get('global.somethingWentWrong');
                }
            } else {
                $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                $arrResponse['message'] = Lang::get('global.MeetingAlreadyplanned');
            }


            return json_encode($arrResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function getMeetingDetails(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            $meeting_details = Meetings::select('*')->where('id', $post['meeting_id'])->first();
            $user = Users::find($meeting_details->user_id);
            $user_name = $user->name;
            if ($user->profile_icon != null) {
                $user_profile_icon = env('S3_URL_PROFILE') . env('S3_BUCKET_DOCUMENTS') . '/' . $user->profile_icon;
            } else {
                $user_profile_icon = \URL::asset('resources/assets/images/profile_icon.png');
            }
            $meeting_status = MeetingAttendies::select('status')->where('meeting_id', $post['meeting_id'])->where('user_id', $login_id)->first();
            if ($meeting_status == null) {
                $meeting_status = 0;
            } else {
                $meeting_status = $meeting_status->status;
            }
            if ($meeting_details) {
                $arrResponse['data'] = $meeting_details;
                $arrResponse['data']['user_name'] = $user_name;
                $arrResponse['data']['user_profile_icon'] = $user_profile_icon;
                $arrResponse['meeting_status'] = $meeting_status;

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

    public function confirmMeetingAttendance(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');

            $store['meeting_id'] = $post['meeting_id'];
            $store['user_id'] = $login_id;
            $store['email'] = $post['meeting_attend_email'];
            $store['status'] = 1;
            $check = MeetingAttendies::where('meeting_id', $post['meeting_id'])->where('user_id', $login_id)->first();
            $meeting_details = Meetings::select('*')->where('id', $post['meeting_id'])->first();

            $user_details = $this->userRepo->getUserDetails($meeting_details->user_id);

            if (strlen($meeting_details->venue_name) > 1) {
                $location = $meeting_details->venue_name;
            } else {
                $location = $meeting_details->street_1 . ',' . $meeting_details->street_2 . ',' . $meeting_details->city . ',' . $meeting_details->state . '-' . $meeting_details->zipcode;
            }
            $start_date = date_create(date('Y-m-d', strtotime($meeting_details->meeting_date)) . ' ' . date('h:i a', strtotime($meeting_details->start_time)));
            $end_date = date_create(date('Y-m-d', strtotime($meeting_details->meeting_date)) . ' ' . date('h:i a', strtotime($meeting_details->end_time)));
            $mailBody = array(
                'email' => $post['meeting_attend_email'],
                'meeting_date' => date('m-d-Y', strtotime($meeting_details->meeting_date)),
                'start_time' => date('h:i a', strtotime($meeting_details->start_time)),
                'end_time' => date('h:i a', strtotime($meeting_details->end_time)),
                'location' => $location,
                'purpose_of_meeting' => $meeting_details->purpose_of_meeting,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'name' => $user_details->name
            );

            if (!$check) {
                $save = MeetingAttendies::create($store);
                //Sending Email

                Mail::send('emails.RSVP', $mailBody, function ($m) use ($mailBody) {
                    $filename = "invite.ics";
                    $since_start = date_diff($mailBody['start_date'], $mailBody['end_date']);
                    $meeting_duration = $since_start->i; // 30 munitues

                    $meetingstamp = strtotime(date_format($mailBody['start_date'], "Y/m/d H:iP"));

                    $dtstart = gmdate('Ymd\THis\Z', $meetingstamp);

                    $dtend = gmdate('Ymd\THis\Z', $meetingstamp + $meeting_duration);
                    $todaystamp = gmdate('Ymd\THis\Z');
                    $uid = date('Ymd') . 'T' . date('His') . '-' . rand() . '@yourdomain.com';
                    $description = 'WIP: Thanks for your RSVP';

                    $titulo_invite = $mailBody['purpose_of_meeting'];
                    $organizer = "CN=".$mailBody['name'].":" . env('MAIL_FROM_ADDRESS');

                    $mail[0] = "BEGIN:VCALENDAR";
                    $mail[1] = "PRODID:-//Google Inc//Google Calendar 70.9054//EN";
                    $mail[2] = "VERSION:2.0";
                    $mail[3] = "CALSCALE:GREGORIAN";
                    $mail[4] = "METHOD:REQUEST";
                    $mail[5] = "BEGIN:VEVENT";
                    $mail[6] = "DTSTART;TZID=America/Sao_Paulo:" . $dtstart;
                    $mail[7] = "DTEND;TZID=America/Sao_Paulo:" . $dtend;
                    $mail[8] = "DTSTAMP;TZID=America/Sao_Paulo:" . $todaystamp;
                    $mail[9] = "UID:" . $uid;
                    $mail[10] = "ORGANIZER;" . $organizer;
                    $mail[11] = "CREATED:" . $todaystamp;
                    $mail[12] = "DESCRIPTION:" . $description;
                    $mail[13] = "LAST-MODIFIED:" . $todaystamp;
                    $mail[14] = "LOCATION:" . $mailBody['location'];
                    $mail[15] = "SEQUENCE:0";
                    $mail[16] = "STATUS:CONFIRMED";
                    $mail[17] = "SUMMARY:" . $titulo_invite;
                    $mail[18] = "TRANSP:OPAQUE";
                    $mail[19] = "END:VEVENT";
                    $mail[20] = "END:VCALENDAR";

                    $mail = implode("\r\n", $mail);
                    // header("text/calendar");
                    file_put_contents($filename, $mail);

                    $m->from(env('MAIL_FROM_ADDRESS'), $mailBody['name']);
                    $m->to($mailBody['email'])->subject('WIP: Thanks for your RSVP');
                    $m->attach($filename, array('mime' => 'text/calendar; charset="utf-8"; method=REQUEST'));
                });
            } else {
                $delete = MeetingAttendies::where('meeting_id', $post['meeting_id'])->where('user_id', $login_id)->delete();
                $save = MeetingAttendies::create($store);
                //Sending Email

                Mail::send('emails.RSVP', $mailBody, function ($m) use ($mailBody) {
                    $filename = "invite.ics";
                    $since_start = date_diff($mailBody['start_date'], $mailBody['end_date']);
                    $meeting_duration = $since_start->i; // 30 munitues

                    $meetingstamp = strtotime(date_format($mailBody['start_date'], "Y/m/d H:iP"));

                    $dtstart = gmdate('Ymd\THis\Z', $meetingstamp);

                    $dtend = gmdate('Ymd\THis\Z', $meetingstamp + $meeting_duration);
                    $todaystamp = gmdate('Ymd\THis\Z');
                    $uid = date('Ymd') . 'T' . date('His') . '-' . rand() . 'tnc.org';
                    $description = 'WIP: Thanks for your RSVP';

                    $titulo_invite = $mailBody['purpose_of_meeting'];
                    $organizer = "CN=".$mailBody['name'].":" . env('MAIL_FROM_ADDRESS');

                    $mail[0] = "BEGIN:VCALENDAR";
                    $mail[1] = "PRODID:-//Google Inc//Google Calendar 70.9054//EN";
                    $mail[2] = "VERSION:2.0";
                    $mail[3] = "CALSCALE:GREGORIAN";
                    $mail[4] = "METHOD:REQUEST";
                    $mail[5] = "BEGIN:VEVENT";
                    $mail[6] = "DTSTART;TZID=America/Sao_Paulo:" . $dtstart;
                    $mail[7] = "DTEND;TZID=America/Sao_Paulo:" . $dtend;
                    $mail[8] = "DTSTAMP;TZID=America/Sao_Paulo:" . $todaystamp;
                    $mail[9] = "UID:" . $uid;
                    $mail[10] = "ORGANIZER;" . $organizer;
                    $mail[11] = "CREATED:" . $todaystamp;
                    $mail[12] = "DESCRIPTION:" . $description;
                    $mail[13] = "LAST-MODIFIED:" . $todaystamp;
                    $mail[14] = "LOCATION:" . $mailBody['location'];
                    $mail[15] = "SEQUENCE:0";
                    $mail[16] = "STATUS:CONFIRMED";
                    $mail[17] = "SUMMARY:" . $titulo_invite;
                    $mail[18] = "TRANSP:OPAQUE";
                    $mail[19] = "END:VEVENT";
                    $mail[20] = "END:VCALENDAR";

                    $mail = implode("\r\n", $mail);
                    // header("text/calendar");
                    file_put_contents($filename, $mail);
                    $m->from(env('MAIL_FROM_ADDRESS'), $mailBody['name']);
                    $m->to($mailBody['email'])->subject('WIP: Thanks for your RSVP');
                    $m->attach($filename, array('mime' => 'text/calendar; charset="utf-8"; method=REQUEST'));
                });
            }

            if ($save) {
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

    public function notGoingToMeeting(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');

            $check = DB::table('meeting_attendies')->select('id')->where('meeting_id', $post['meeting_id'])->where('user_id', $login_id)->get();

            if (count($check) > 0) {
                $save = MeetingAttendies::where('meeting_id', $post['meeting_id'])->where('user_id', $login_id)->delete();
                if ($save) {
                    $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                    $arrResponse['message'] = Lang::get('global.Success');
                } else {
                    $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                    $arrResponse['message'] = Lang::get('global.somethingWentWrong');
                }
            } else {
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.Success');
            }
            return json_encode($arrResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function notAttendingToMeeting(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');

            $store['meeting_id'] = (int) $post['meeting_id'];
            $store['user_id'] = $login_id;
            $store['status'] = 2;
//             dd($post['not_attend_meeting_reason'][0]);
            $store['not_attending_reason'] = (int) $post['not_attend_meeting_reason'][0];
//            dd($store);
            $check = DB::table('meeting_attendies')->select('id')->where('meeting_id', $post['meeting_id'])->where('user_id', $login_id)->get();

            if (count($check) == 0) {
                $save = MeetingAttendies::create($store);
            } else {
                $delete = MeetingAttendies::where('meeting_id', $post['meeting_id'])->where('user_id', $login_id)->delete();
                $save = MeetingAttendies::create($store);
            }

            if ($save) {
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

    public function exploreDashboard(Request $request)
    {
        try {
            $login_id = session::get('looged_user_id');
            $search_text = $request->get('search_text');
            $filters = $request->get('filters');
            $discussion_list = $this->discussionRepo->discussionsList($search_text, $filters, $login_id);
            $report_reasons = $this->discussionRepo->reasonsList();
            $user = Users::find($login_id);
            $first_login = $user->first_login;

            Session::forget('open_discussion_id');
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            Users::where(['id' => $login_id])->update(['first_login' => 2]);
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            return view('explore/explore', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags', 'user', 'notfication_count'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function netwrokDashboard(Request $request)
    {
        try {
            $login_id = session::get('looged_user_id');
            $search_text = $request->get('search_text');
            $filters = $request->get('filters');
            $discussion_list = $this->discussionRepo->discussionsList($search_text, $filters, $login_id);
            $report_reasons = $this->discussionRepo->reasonsList();
            $user = Users::find($login_id);
            $first_login = $user->first_login;

            Session::forget('open_discussion_id');
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            Users::where(['id' => $login_id])->update(['first_login' => 2]);
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            return view('dashboard/network', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags', 'user', 'notfication_count'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function loadFavoriteReply($reply_id)
    {
        try {
            $reply = $this->discussionRepo->replyDetails($reply_id);
            $login_id = session::get('looged_user_id');
            $report_check = Reports::where('discussion_id', $reply->discussion_id)->first();

            if ($report_check) {
                return redirect()->action('DashboardController@index');
            }


            $discussion_details = $this->discussionRepo->discussionDetails($reply->discussion_id, $login_id);
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            $data ['basin_name'] = $discussion_details->basin_name;
            $data ['basin_color_code'] = $discussion_details->basin_color_code;
            $discussionVSTags = $this->discussionRepo->discussionVSTags($reply->discussion_id);
            $user = Users::find($login_id);
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            $report_reasons = $this->discussionRepo->reasonsList();
            return view('dashboard/reply_details', compact('discussion_details', 'reply', 'login_id', 'report_reasons', 'user', 'notfication_count', 'basins', 'tags', 'discussionVSTags'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function loadFavoriteComment($comment_id)
    {
        try {
            $comment_details=DB::table('comments')->where('id', $comment_id)->first();
            $discussion_id=$comment_details->discussion_id;
            $reply_id=$comment_details->discussion_reply_id;

            $reply = $this->discussionRepo->replyDetails($reply_id);
            $login_id = session::get('looged_user_id');
            $report_check = Reports::where('discussion_id', $comment_details->discussion_id)->first();

            if ($report_check) {
                return redirect()->action('DashboardController@index');
            }
            // echo "zdfsdf";exit();
            $discussion_details = $this->discussionRepo->discussionDetails($discussion_id, $login_id);
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            $data ['basin_name'] = $discussion_details->basin_name;
            $data ['basin_color_code'] = $discussion_details->basin_color_code;
            $discussionVSTags = $this->discussionRepo->discussionVSTags($discussion_id);
            $user = Users::find($login_id);
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            $report_reasons = $this->discussionRepo->reasonsList();
            $comment = $this->discussionRepo->fav_comment_details($comment_id, $login_id);
            //var_dump($comment);exit();
            return view('dashboard/comment_details', compact('discussion_details', 'reply', 'login_id', 'report_reasons', 'user', 'notfication_count', 'basins', 'tags', 'discussionVSTags', 'comment'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function loadSearchReply($reply_id)
    {
        try {
            $reply = $this->discussionRepo->replyDetails($reply_id);
            $login_id = session::get('looged_user_id');
            $report_check = Reports::where('discussion_id', $reply->discussion_id)->first();
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            if ($report_check) {
                return redirect()->action('DashboardController@index');
            }
            $discussion_id = $reply->discussion_id;

            $discussion_details = $this->discussionRepo->discussionDetails($reply->discussion_id, $login_id);
            $discussionVSTags = $this->discussionRepo->discussionVSTags($discussion_id);
            $data ['basin_name'] = $discussion_details->basin_name;
            $data ['basin_color_code'] = $discussion_details->basin_color_code;
            $user = Users::find($login_id);
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            $report_reasons = $this->discussionRepo->reasonsList();
            return view('dashboard/reply_details', compact('discussion_details', 'reply', 'login_id', 'report_reasons', 'user', 'notfication_count', 'discussionVSTags', 'basins', 'tags'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function loadSearchComment($comment_id)
    {
        try {
            $login_id = session::get('looged_user_id');
            $comment = $this->discussionRepo->commentDetails($comment_id);
            $report_check = Reports::where('discussion_id', $comment->discussion_id)->first();

            if ($report_check) {
                return redirect()->action('DashboardController@index');
            }

            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            $discussion_id = $comment->discussion_id;
            $discussion_details = $this->discussionRepo->discussionDetails($comment->discussion_id, $login_id);
            $reply = $this->discussionRepo->replyDetails($comment->discussion_reply_id);
//            dd($reply);
            $data ['basin_name'] = $discussion_details->basin_name;
            $data ['basin_color_code'] = $discussion_details->basin_color_code;
            //dd($discussion_details);
            $discussionVSTags = $this->discussionRepo->discussionVSTags($discussion_id);
            $user = Users::find($login_id);
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            $report_reasons = $this->discussionRepo->reasonsList();
            return view('dashboard/comment_details', compact('discussion_details', 'reply', 'comment', 'login_id', 'report_reasons', 'user', 'notfication_count', 'discussionVSTags', 'basins', 'tags'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function deletePost(Request $request)
    {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            if ($post['type'] == 1) {
                $discussion_id = $post['reply_id'];
                //delete discussion, replies, comments, reports, favorites, followers, meetings, notifications
                Notifications::where('discussion_id', $discussion_id)->delete();
                Meetings::where('discussion_id', $discussion_id)->delete();
                Followers::where('discussion_id', $discussion_id)->delete();
                Favourites::where('discussion_id', $discussion_id)->delete();
                Reports::where('discussion_id', $discussion_id)->delete();
                Comments::where('discussion_id', $discussion_id)->delete();
                Replies::where('discussion_id', $discussion_id)->delete();
                Discussions::where('id', $discussion_id)->delete();
            }
            if ($post['type'] == 2) {
                $discussion_reply_id = $post['reply_id'];
                //delete  reply, comments, reports, favorites, notifications
                Notifications::where('discussion_reply_id', $discussion_reply_id)->delete();
                Favourites::where('discussion_reply_id', $discussion_reply_id)->delete();
                Reports::where('discussion_reply_id', $discussion_reply_id)->delete();
                Comments::where('discussion_reply_id', $discussion_reply_id)->delete();
                Replies::where('id', $discussion_reply_id)->delete();
            }
            if ($post['type'] == 3) {
                $discussion_comment_id = $post['reply_id'];
                //delete  comment, reports, notifications
                Notifications::where('discussion_comment_id', $discussion_comment_id)->delete();
                Reports::where('discussion_comment_id', $discussion_comment_id)->delete();
                Comments::where('id', $discussion_comment_id)->delete();
            }


            $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
            $arrResponse['message'] = Lang::get('global.ReportedSuccessfully');


            return json_encode($arrResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function basinFilter($basin_id)
    {
        try {
            $login_id = session::get('looged_user_id');
            Session::forget('tag_ids');
            Session::forget('basin_ids');
            Session::put("filters", 'on');
            Session::put("tag_ids", array('-1'));
            Session::put("basin_ids", array($basin_id));
            return redirect()->action('DashboardController@discussionsFilters');
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function getReplyText($reply_id)
    {
        try {
            $login_id = session::get('looged_user_id');
            $reply_text = Replies::select('reply_text_mentions', 'image_key_id')->where(['id' => $reply_id])->first();

            $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
            $arrResponse['message'] = Lang::get('global.ReportedSuccessfully');
            $arrResponse['post_reply'] = $this->replceMentions($reply_text->reply_text_mentions);
            $arrResponse['image_key_id'] = env('S3_URL_PROFILE').env('S3_BUCKET_DOCUMENTS').'/'.$reply_text->image_key_id;
            return json_encode($arrResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function getCommentText($comment_id)
    {
        try {
            $login_id = session::get('looged_user_id');
            $comment_text = Comments::select('comment_text_mentions', 'image_key_id')->where(['id' => $comment_id])->first();
            $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
            $arrResponse['message'] = Lang::get('global.ReportedSuccessfully');
            $arrResponse['post_comment'] = $this->replceMentions($comment_text->comment_text_mentions);
            if ($comment_text->image_key_id != null) {
                $arrResponse['image_key_id'] = env('S3_URL_PROFILE').env('S3_BUCKET_DOCUMENTS').'/'.$comment_text->image_key_id;
            } else {
                $arrResponse['image_key_id'] = null;
            }

            return json_encode($arrResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function getMentions($content)
    {
        $mention_regex = '/@\[([0-9]+)\]/i'; //mention regrex to get all @texts

        if (preg_match_all($mention_regex, $content, $matches)) {
            foreach ($matches[1] as $match) {
                $match_user = DB::table('users')->whereIn('id', array($match))->first(); //$DB->row("SELECT * FROM w3_user WHERE user_id=?",array($match));


                $match_search = '@[' . $match . ']';
                $match_replace = '<span class="post-links">@' . $match_user->name . '</span>';

                if (isset($match_user->id)) {
                    $content = str_replace($match_search, $match_replace, $content);
                }
            }
        }
        return $content;
    }

    public function replceMentions($content)
    {
        $mention_regex = '/@\[([0-9]+)\]/i'; //mention regrex to get all @texts

        if (preg_match_all($mention_regex, $content, $matches)) {
            foreach ($matches[1] as $match) {
                $match_user = DB::table('users')->whereIn('id', array($match))->first(); //$DB->row("SELECT * FROM w3_user WHERE user_id=?",array($match));


                $match_search = '@[' . $match . ']';
                $match_replace = '@[' . $match_user->name . '](id:' . $match_user->id . ')';

                if (isset($match_user->id)) {
                    $content = str_replace($match_search, $match_replace, $content);
                }
            }
        }
        return $content;
    }

    public function getFavoriteUsers(Request $request)
    {
        // return json_encode($request);
        try {
            $post = $request->all();

            $users=$this->discussionRepo->getfavoritedUsersList($post['type'], $post['id']);

            $arrResponse['users']=$users;

            $arrResponse['http_status'] = Config::get('constants.HTTP_OK');

            return json_encode($arrResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }
}
