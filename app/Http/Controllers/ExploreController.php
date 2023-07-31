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

class ExploreController extends Controller {

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
            return view('dashboard/discussionList', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags', 'user', 'notfication_count'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function postDiscussion(Request $request) {

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
                    array_push($discussion_tags, array('discussion_id' => $discussion->id, "tag_id" => $tag));
                }
                DiscussionTags::create($discussion_tags);
            }


            $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
            $arrResponse['message'] = Lang::get('global.DiscussionCreated');

            return response()->json($arrResponse, 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

    public function searchDiscussions(Request $request) {
        try {
            $login_id = session::get('looged_user_id');
            $query = $request->get('query');

            $discussion_list = $this->discussionRepo->searchDiscussionsList($query, $login_id);
//            dd($discussion_list);
            $report_reasons = $this->discussionRepo->reasonsList();
            $user = Users::find($login_id);
            $first_login = $user->first_login;

            Session::forget('open_discussion_id');
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
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function discussionsFilters(Request $request) {
        try {
            $login_id = session::get('looged_user_id');
            $tag_ids = explode(',', $request->get('tag_ids'));
            $basin_ids = explode(',', $request->get('basin_ids'));

            $discussion_list = $this->discussionRepo->discussionsListWithFilters($tag_ids, $basin_ids, $login_id);
//            dd($discussion_list);
            $report_reasons = $this->discussionRepo->reasonsList();
            $user = Users::find($login_id);
            $first_login = $user->first_login;

            Session::forget('open_discussion_id');
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
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function exploreDetails($discussion_id) {
        try {

            $login_id = session::get('looged_user_id');
            $report_check = Reports::where('discussion_id', $discussion_id)->first();

            if ($report_check) {
                return redirect()->action('DashboardController@index');
            }


            $discussion_details = $this->discussionRepo->discussionDetails($discussion_id, $login_id);

            $data ['basin_name'] = $discussion_details->basin_name;
            $data ['basin_color_code'] = $discussion_details->basin_color_code;

            $user = Users::find($login_id);
            $page = 1; //$request->get('page', 1);

            $paginate = 5;
            $offset = ($page - 1) * $paginate;

            $replies_list = DB::select("call sp_replies_polls('$discussion_id','$login_id','$paginate','$offset')");

//    dd($replies_list);
//        $replies_list = $this->discussionRepo->getRepliesList($discussion_id, $login_id); 
            $report_reasons = $this->discussionRepo->reasonsList();
            $meetings = $this->discussionRepo->meetingsList($discussion_id);

            $poll_answers1 = DB::table('poll_answer')->where('id', '<', '3')->get();
            $poll_answers2 = DB::table('poll_answer')->where('id', '>', '2')->get();
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            if ($this->request->ajax()) {
                $data ['html'] = view('dashboard._partial_discussion_details', compact('discussion_details', 'replies_list', 'login_id', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2', 'user'))->__toString();
                $data ['http_status'] = Response::HTTP_OK;
                return json_encode($data);
            } else {

                Session::put("open_discussion_id", $discussion_id);
                return view('explore/exploreDetails', compact('discussion_details', 'replies_list', 'login_id', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2', 'user', 'notfication_count'));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function loadMoreReplies(Request $request) {
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
                 if($load_more ==1){
                $data ['html'] = view('dashboard._partial_replies', compact('discussion_details', 'replies_list', 'login_id', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2'))->__toString();
                }else{
                $data ['html'] = view('dashboard._partial_replies2', compact('discussion_details', 'replies_list', 'login_id', 'report_reasons', 'meetings', 'poll_answers1', 'poll_answers2'))->__toString();
                    
                }$data ['http_status'] = Response::HTTP_OK;
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

    public function postReply(Request $request) {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            $reply_info['discussion_id'] = $post['discussion_id'];
            $reply_info['reply_text'] = $post['reply'];
            $reply_info['user_id'] = $login_id;
            $store_reply = Replies::create($reply_info);

            if ($store_reply) {
                //create notification for new reply

                $notification['discussion_reply_id'] = $store_reply->id;
                $notification['notification_type_id'] = 1;
                $notification['user_id'] = $login_id;
                $notification['discussion_id'] = $post['discussion_id'];
                AddUserNotifications::dispatch($notification);
                $discussion_details = $this->discussionRepo->discussionDetails($post['discussion_id'], $login_id);
                $reply = $this->discussionRepo->replyDetails($store_reply->id);
                $arrResponse['html'] = view('dashboard._partial_reply', compact('discussion_details', 'reply', 'login_id'))->__toString();
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

    public function updateReply(Request $request) {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');

            $reply_info['reply_text'] = $post['reply'];

            $reply_id = $post['reply_id'];


            $update_reply = Replies::where(['id' => $reply_id])->update($reply_info);

            if ($update_reply) {
                $discussion_details = $this->discussionRepo->discussionDetails($post['discussion_id'], $login_id);
                $reply = $this->discussionRepo->replyDetails($reply_id);
                $arrResponse['html'] = view('dashboard._partial_reply', compact('discussion_details', 'reply', 'login_id'))->__toString();
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

    public function postComment(Request $request) {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            $comment_info['discussion_id'] = $post['discussion_id'];
            $comment_info['discussion_reply_id'] = $post['reply_id'];
            $comment_info['comment_text'] = $post['comment'];
            $comment_info['user_id'] = $login_id;



            $store_comment = Comments::create($comment_info);
            if ($store_comment) {

                $notification['discussion_comment_id'] = $store_comment->id;
                $notification['notification_type_id'] = 2;
                $notification['user_id'] = $login_id;
                $notification['discussion_id'] = $post['discussion_id'];
                 $notification['discussion_reply_id'] = $post['reply_id'];
                AddUserNotifications::dispatch($notification);

                $discussion_details = $this->discussionRepo->discussionDetails($store_comment->discussion_id, $login_id);

                $reply = $this->discussionRepo->replyDetails($store_comment->discussion_reply_id);

                $comment = $this->discussionRepo->commentDetails($store_comment->id,$login_id);
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

    public function updateComment(Request $request) {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            $comment_id = $post['comment_id'];
            $comment_info['comment_text'] = $post['comment'];



            $store_comment = Comments::where('id', $comment_id)->update($comment_info);
            if ($store_comment) {
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

    public function loadComments(Request $request) {
        try {
            $post = $request->all();
            $login_id = session::get('looged_user_id');

            $discussion_details = $this->discussionRepo->discussionDetails($post['discussion_id'], $login_id);

            $reply = $this->discussionRepo->replyDetails($post['reply_id']);
            $type = 2;
            $comments = $this->discussionRepo->commentsList($post['reply_id']);
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

    public function makeFavoriteReply(Request $request) {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            if ($post['type'] == 1) {
                $favourite['discussion_id'] = $post['reply_id'];
            }
            if ($post['type'] == 2) {
                $favourite['discussion_reply_id'] = $post['reply_id'];
            }
            $favourite['user_id'] = $login_id;

            $check_favourite = Favourites::where($favourite)->first();
            if ($check_favourite) {
                $store_favourite = Favourites::where($favourite)->delete();
            } else {
                $store_favourite = Favourites::create($favourite);
            }
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

    public function reportPost(Request $request) {
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

    public function followDiscussion(Request $request) {
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

    public function postNewPoll(Request $request) {
        try {
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

                if ($post['poll_type'] == 1) {
                    $poll_answers = DB::table('poll_answer')->where('id', '<', '3')->get();
                } else {
                    $poll_answers = DB::table('poll_answer')->where('id', '>', '2')->get();
                }
                $poll_id = $save->id;
                $poll = Polls::select('discussion_id', 'question', 'poll_type', 'created_at')->where('id', $poll_id)->first();
//                     dd($poll);

                $arrResponse['html'] = view('dashboard._partial_poll', compact('poll_answers', 'poll'))->__toString();
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.PollAddedSuccessfully');
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

    public function answerPollQuestion(Request $request) {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');

            $store['discussion_id'] = $post['discussion_id'];
            $store['user_id'] = $login_id;
            $store['poll_id'] = $post['poll_id'];
            $store['answer_id'] = $post['answer_id'];
            $save = PollsAnswers::create($store);
            if ($save) {
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.AnsweredSuccessfully');
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

    public function postMeeting(Request $request) {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');

            $meeting['discussion_id'] = $discussion_id = $post['discussion_id'];
            $meeting['user_id'] = $login_id;
            if (strlen($post['venue_name']) > 1) {
                $meeting['venue_name'] = $post['venue_name'];
                $location = $post['venue_name'];
            } else {
                $meeting['street_1'] = $post['street_1'];
                $meeting['city'] = $post['city'];
                $meeting['state'] = $post['state'];
                $meeting['zipcode'] = $post['zipcode'];
                 $location = $post['street_1'].','.$post['city'].','.$post['city'].'-'.$post['zipcode'];
            }

            $posted_meeting_id = $post['meeting_id'];
            $meeting['meeting_date'] = date('Y-m-d', strtotime($post['meeting_date']));
            $meeting['start_time'] = date('H:i', strtotime($post['start_time']));
            $meeting['end_time'] = date('H:i', strtotime($post['end_time']));
            $meeting['purpose_of_meeting'] = $post['purpose_of_meeting'];
            $query = Meetings::where('discussion_id', $discussion_id)
                    ->where('meeting_date', '=', date('Y-d-m', strtotime($post['meeting_date'])))
                    ->where('start_time', '=', date('H:i', strtotime($post['start_time'])));
            if ($posted_meeting_id != -1) {
                $query->where('id', '!=', $posted_meeting_id);
            }
            $check_meeting = $query->first();

            if (!$check_meeting) {
                if ($posted_meeting_id == -1) {
                    $save = Meetings::create($meeting);
                    $user = Users::find($login_id);
                    $mailBody = array(
                        'email' => $user->email,
                        'name' => $user->name,
                        'meeting_date' => date('Y-m-d', strtotime($post['meeting_date'])),
                        'start_time' => date('h:i a', strtotime($post['start_time'])),
                        'end_time' => date('h:i a', strtotime($post['end_time'])),
                        'location' => $location,
                    );
                    Mail::send('emails.meetingScheduled', $mailBody, function ($m) use ($mailBody) {
                        $m->from(env('MAIL_FROM_ADDRESS'), Lang::get('global.TNCTeam'));
                        $m->to($mailBody['email'], $mailBody['name'])->subject('Meeting Scheduled Email');
                    });

                    if ($save) {
                        $notification['meeting_id'] = $save->id;
                        $notification['notification_type_id'] = 4;
                        $notification['user_id'] = $login_id;
                        $notification['discussion_id'] = $post['discussion_id'];
                        AddUserNotifications::dispatch($notification);

                        $meeting_id = $save->id;
                        $meeting = DB::table('meetings as m')
                ->leftjoin('users as u', 'm.user_id', '=', 'u.id')
                ->select('m.*', 'u.profile_icon')
                                        ->where('m.id', $meeting_id)->first();

                        $arrResponse['html'] = view('dashboard._partial_meeting', compact('meeting', 'meeting_id'))->__toString();
                        $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                        $arrResponse['message'] = Lang::get('global.MeetingAddedSuccessfully');
                    } else {
                        $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                        $arrResponse['message'] = Lang::get('global.somethingWentWrong');
                    }
                } else {
                    $update = Meetings::where(['id' => $posted_meeting_id])->update($meeting);
                    if ($update) {


                        $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                        $arrResponse['message'] = Lang::get('global.MeetingUpdatedSuccessfully');
                    } else {
                        $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                        $arrResponse['message'] = Lang::get('global.somethingWentWrong');
                    }
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

    public function getMeetingDetails(Request $request) {

        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            $meeting_details = Meetings::select('*')->where('id', $post['meeting_id'])->first();
            $meeting_status = MeetingAttendies::select('status')->where('meeting_id', $post['meeting_id'])->where('user_id', $login_id)->first();
            if ($meeting_status == null) {
                $meeting_status = 0;
            } else {
                $meeting_status = $meeting_status->status;
            }
            if ($meeting_details) {
                $arrResponse['data'] = $meeting_details;
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

    public function confirmMeetingAttendance(Request $request) {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');

            $store['meeting_id'] = $post['meeting_id'];
            $store['user_id'] = $login_id;
            $store['email'] = $post['meeting_attend_email'];
            $store['status'] = 1;
            $check = MeetingAttendies::where('meeting_id', $post['meeting_id'])->where('user_id', $login_id)->first();
            if (!$check) {
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

    public function notGoingToMeeting(Request $request) {
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

    public function notAttendingToMeeting(Request $request) {
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
    
    
    public function exploreDashboard(Request $request){
        try{
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
    
    public function exploreDiscussions(Request $request){
        try{
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
             if ($this->request->ajax()) {
                if (count($discussion_list) > 0) {
                    $data ['html'] = view('explore._partial_discussions', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags'))->__toString();
                    
                } else {
                    $data ['html'] = view('explore._partial_no_data', compact('login_id'))->__toString();
                }
                $data['lastPage'] = $discussion_list->lastPage();
                $data['currentPage'] = $discussion_list->currentPage();
                $data ['http_status'] = Response::HTTP_OK;
                return json_encode($data);
            }
             
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }
    
    public function exploreView(Request $request){
        try{
             $login_id = session::get('looged_user_id');
            $search_text = $request->get('search_text');
            $filters = $request->get('filters');
//            $discussion_list = $this->discussionRepo->discussionsList($search_text, $filters, $login_id);
//            dd($discussion_list);
            $report_reasons = $this->discussionRepo->reasonsList();
            $user = Users::find($login_id);
            $first_login = $user->first_login;

            Session::forget('open_discussion_id');
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            Users::where(['id' => $login_id])->update(['first_login' => 2]);
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            return view('explore/exploreView', compact('login_id', 'report_reasons', 'first_login', 'basins', 'tags', 'user', 'notfication_count'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }
    public function ViewHowToUseTool(Request $request)
    {
        try{
        
            return view('guest/guestViewHowToUseTool');
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }



    public function netwrokDashboard(Request $request){
        try{      $login_id = session::get('looged_user_id');
            $search_text = $request->get('search_text');
            $filters = $request->get('filters');
            $discussion_list = $this->discussionRepo->discussionsList($search_text, $filters, $login_id);
            $report_reasons = $this->discussionRepo->reasonsList();
            $user = Users::find($login_id);
            $first_login = $user->first_login;
            $selected_basins ='';
            $selected_tags='';
            Session::forget('open_discussion_id');
            $basins = DB::table('basins')->get();
            $tags = DB::table('tags')->get();
            Users::where(['id' => $login_id])->update(['first_login' => 2]);
            $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
            return view('dashboard/network', compact('login_id', 'discussion_list', 'report_reasons', 'first_login', 'basins', 'tags', 'user', 'notfication_count','selected_basins','selected_tags'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }
}
