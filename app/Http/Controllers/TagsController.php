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
use DB;
use App\Models\Users;
use App\Models\Tags;
use App\Models\UserProfile;
use App\Models\AdminNotification;
use App\Models\UserSettings;
use App\Models\RunnerAvailable;
use Symfony\Component\HttpFoundation\Response;

class TagsController extends Controller {

    protected $request;
    protected $hasher;
    protected $userRepo;
    protected $admin;
    protected $tagRepo;

    public function __construct(Request $request, Hasher $hasher, UserRepo $userRepo, Admin $admin, TagRepo $tagRepo) {

        $this->request = $request;
        $this->hasher = $hasher;
        $this->userRepo = $userRepo;
        $this->admin = $admin;
        $this->tagRepo = $tagRepo;
    }

    /**
     * This is used to get the list of users based on all conditions
     */
    public function tags(Request $request) {
        try {
            $login_id = session::get('admin_id');
            $sort_by = $request->get('sortby');
            $sort_type = $request->get('sorttype');
            
            if ($sort_by == '' && $sort_type == '') {
                $sort_by = 'tag_name';
                $sort_type = 'ASC';
            }
//            $query = DB::table('tags');
//        $query->select('id', 'tag_name', 'created_at');
//
//        if ($sort_by != '' && $sort_type != '') {
//            $query->orderBy($sort_by, $sort_type);
//        } else {
//            $query->orderBy('tag_name', 'ASC');
//        }
//        echo $query->toSql();
//        $data = $query->paginate(10);
//
//dd($data);

            $tags_list = $this->tagRepo->getTagsList($sort_by, $sort_type);
//           dd($user_list); 

            if ($request->ajax()) {
                if ($tags_list->count() > 0) {
                    $data ['html'] = view('tags._partial_tags', compact('tags_list', 'login_id'))->__toString();
                    $data ['http_status'] = Response::HTTP_OK;
                } else {
                    $data ['html'] = view('tags._partial_no_data')->__toString();
                    $data ['http_status'] = Config::get('constants.HTTP_NO_DATA');
                }
                return json_encode($data);
            }
            return view('tags/tagList', compact('login_id', 'tags_list'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function addTag(Request $request) {
        try{ 
        $tag_name = $request->get('tag_name');
         
        
         $tag_exists = $this->tagRepo->checkExists($tag_name, null);
         if($tag_exists){
        $create = array(
            'tag_name' => $tag_name,
        );
        $tag = Tags::create($create);
        if ($tag) {
            $arrResponse['http_status'] = Response::HTTP_OK;
            $arrResponse['message'] = Lang::get('global.TagCreated');
        } else {
            $arrResponse['http_status'] = Response::HTTP_FAILED_DEPENDENCY;
            $arrResponse['message'] = Lang::get('global.somethingWentWrong');
        }
         }else{
             $arrResponse['http_status'] = Response::HTTP_FAILED_DEPENDENCY;
            $arrResponse['message'] = Lang::get('global.TagAlreadyExists');
         }
        return response()->json($arrResponse, Response::HTTP_OK);
    } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }
    
    public function updateTag(Request $request) {
        try{
           
        $tag_name = $request->get('tag_name');
        $tag_id = $request->get('id');
         $tag_exists = $this->tagRepo->checkExists($tag_name, $tag_id);
         if($tag_exists){
        $update = array(
            'tag_name' => $tag_name,
        );
        $tag = Tags::where('id', $tag_id)->update($update);
        if ($tag) {
            $arrResponse['http_status'] = Response::HTTP_OK;
            $arrResponse['message'] = Lang::get('global.TagUpdated');
        } else {
            $arrResponse['http_status'] = Response::HTTP_FAILED_DEPENDENCY;
            $arrResponse['message'] = Lang::get('global.somethingWentWrong');
        }
         }else{
             $arrResponse['http_status'] = Response::HTTP_FAILED_DEPENDENCY;
            $arrResponse['message'] = Lang::get('global.TagAlreadyExists');
            $arrResponse['tag_id']= $tag_id;
         }
        return response()->json($arrResponse, Response::HTTP_OK);
    } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

/**
 * This is used to activate the user based on user id.
 */
public function deleteTag(Request $request) {
    try {
        $tag_id = $request->get('tag_id');
         
        $tag = Tags::where('id', $tag_id)->delete();  
        if ($tag) { 
            $arrResponse['http_status'] = Response::HTTP_OK; 
            $arrResponse['message'] = Lang::get('global.TagDeleted');
        } else {
            $arrResponse['http_status'] = Response::HTTP_FAILED_DEPENDENCY;
            $arrResponse['message'] = Lang::get('global.somethingWentWrong');
        }
        return response()->json($arrResponse, Response::HTTP_OK);
    } catch (Exception $e) {
        throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
    }
    }

    /**
     * This is used to get the user details based on user id.
     */
    public function editUser(Request $request) {
        try {
            $edit_user_id = Session::get("edit_user_id");
            $edit_user_type = Session::get("edit_user_type");
            $user_data = $this->userRepo->getUserDetails($edit_user_id);
            session::put("edit_user_name", $user_data->fname . '_' . $user_data->lname);
            if (!empty($user_data->phone_number) && strlen($user_data->phone_number) == 10) {
                preg_match('/^(\d{3})(\d{3})(\d{4})$/', $user_data->phone_number, $mobile_number);
                $user_data->phone_number = "$mobile_number[1]-$mobile_number[2]-$mobile_number[3]";
            }
            $roles = $this->userRepo->getRoles(); //list of roles
            $states = $this->userRepo->getStates(); //list of states
            return view('users/userDetails', compact('user_data', 'edit_user_type', 'edit_user_id', 'roles', 'states'));
        } catch (Exception $e) {
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

}
