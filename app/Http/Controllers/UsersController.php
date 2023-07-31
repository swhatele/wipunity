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
use Illuminate\Support\Facades\Hash;
use App\Repositories\EloquentRepositories\UserRepository as UserRepo;
use App\Repositories\EloquentRepositories\AdminRepository as Admin;
use DB;
use App\Models\Users;
use App\Models\UserProfile;
use App\Models\AdminNotification;
use App\Models\UserSettings;
use App\Models\UserNotificationSettings;
use Symfony\Component\HttpFoundation\Response;
use AWS;

class UsersController extends Controller {

    protected $request;
    protected $hasher;
    protected $userRepo;
    protected $admin;

    public function __construct(Request $request, Hasher $hasher, UserRepo $userRepo, Admin $admin) {

        $this->request = $request;
        $this->hasher = $hasher;
        $this->userRepo = $userRepo;
        $this->admin = $admin;
    }

    /**
     * This is used to create the reset password request
     */
    public function userResetPasswordRequest(Request $request) {
        try {
            $user_id = $request->get('to_user_id');

            $user_data = $this->userRepo->getUserDetails($user_id);
            if ($user_data) {
                $randomUid = md5($user_data->email . date('ymdHis'));
                $forgotPassword = array(
                    'email' => $user_data->email,
                    'user_id' => $user_data->id,
                    'token' => $randomUid,
                    'created_at' => gmdate("Y-m-d H:i:s"),
                );
                if ($this->admin->passwordTokenInsert($forgotPassword)) {
                    $link = url($randomUid . "/userResetPassword");
                    $mailBody = array(
                        'email' => $user_data->email,
                        'name' => $user_data->fname, ' ', $user_data->lname,
                        'link' => $link,
                    );
                    Mail::send('emails.userResetPasswordLink', $mailBody, function ($m) use ($mailBody) {
                        $m->from(env('MAIL_FROM_ADDRESS'), Lang::get('global.TripWirelessAdmin'));
                        $m->to($mailBody['email'], $mailBody['name'])->subject(Lang::get('global.TripWirelessAccountPasswordReset'));
                    });
                    $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                    $arrResponse['message'] = Lang::get('global.PasswordWResetLinkSuccess');
                    $arrResponse['link'] = $link;
                } else {
                    $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                    $arrResponse['message'] = Lang::get('global.somethingWentWrong');
                }
            } else {
                $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                $arrResponse['message'] = Lang::get('global.PleaseEnterValidEmail');
            }
            return response()->json($arrResponse, Response::HTTP_OK);
        } catch (Exception $e) {
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

    /**
     * This is used to navigate to reset password page.
     */
    public function userResetPassword($token) {
        try {
            if ($data = $this->admin->checkPasswordToken($token)) {
                return view('users/userResetPassword')->with(['token' => $token]);
            } else {
                return view('users/resetPasswordExpired');
            }
        } catch (Exception $e) {
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

    /**
     * This is used to change the user password.
     */
    public function userResetNewPassword(Request $request) {
        try {
            $post = $this->request->all();
            $token = $post['resetToken'];
            if ($data = $this->admin->checkPasswordToken($token)) {
                $email = $data->email;
                $user_id = $data->user_id;
                $salt = md5(uniqid($email, TRUE));
                $secret = hash('sha256', $salt . $post['new_password']);
                $update = array(
                    'salt' => $salt,
                    'password' => $secret,
                );
                if ($this->admin->update($update, $email, 'email')) {
                    $where = array(
                        'email' => $email,
                        'user_id' => $user_id,
                    );
                    $this->admin->deleteResetPassToken($where);
                    $user_data = $this->userRepo->getUserDetails($user_id);
                    $user_role = $user_data->role_id;
                    $successMessage = Lang::get('global.passwordSuccessRunner');
                    if ($user_role == 3 || $user_role == 4) {
                        $successMessage = Lang::get('global.passwordSuccess');
                    }
                    $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                    $arrResponse['message'] = $successMessage;
                    $arrResponse['user_role'] = $user_role;
                } else {
                    $arrResponse['http_status'] = Config::get('constants.DB_ERROR');
                    $arrResponse['message'] = Lang::get('global.somethingWentWrong');
                }
            } else {
                $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                $arrResponse['message'] = Lang::get('global.passwordLinkExpire');
            }
        } catch (Exception $e) {
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
        return response()->json($arrResponse, 200);
    }

    public function accountSettings() {
        $login_id = session::get('looged_user_id');
        $user = Users::find($login_id);
        $user_settings = DB::table('user_notification_settings')->where('user_id', '=', $login_id)->first();
        $notifications = DB::table('notification_types')->get();
        $email_notification_types = DB::table('email_notification_types')->get();
        $in_app_notification_types = DB::table('in_app_notification_types')->get();
        $user_deactivation_reasons = DB::table('user_deactivation_reasons')->get();
        $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
        return view('users/accountSettings', compact('login_id', 'user', 'user_settings', 'notifications', 'email_notification_types', 'in_app_notification_types', 'user_deactivation_reasons', 'notfication_count'));
    }

    public function updateUserData(Request $request) {
        try {
            $post = $request->all();
            $login_id = session::get('looged_user_id');
            $user = Users::find($login_id);
            $check_email = Users::where('email', $post['email'])->where('id', '!=', $login_id)->first();
            if ($check_email) {
                $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                $arrResponse['message'] = Lang::get('global.EmailAlreadyExist');
            } else {
                $user_info['email'] = $post['email'];
                $user_info['name'] = $post['name'];
//Upload image to s3
                $uploaded_image = $post['uploaded_image'];

                if (isset($uploaded_image)) {
                    $s3 = AWS::createClient('s3');
                    try {

                        $img_guid = 'TNC-' . $user->id . date('YmdHis');
                        $imageFileName = $img_guid . '.png';
                        $imageUpload = $s3->putObject(array(
                            'Key' => env('S3_BUCKET_DOCUMENTS') . '/' . $imageFileName,
                            'Bucket' => env('S3_BUKET_PROFILE'),
                            'SourceFile' => $uploaded_image
                        ));
                        if (isset($imageUpload) && $imageUpload['@metadata']['statusCode'] == 200) {
                            Users::where('id', $user->id)->update(['profile_icon' => $imageFileName]);
                        }
                    } catch (Exception $e) {
                        echo 'Caught exception: ', $e->getMessage(), "\n";
                    }
                }

//Upload image to s3

                Users::where('id', $user->id)->update($user_info);
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.UserUpdated');
            }
            return response()->json($arrResponse, 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

    public function changePassword(Request $requst) {
        try {
            $post = $this->request->all();
            $login_id = session::get('looged_user_id');
            $user_data = Users::find($login_id);

            $current_password = $user_data->password;
            if ($this->matchPassword($post['old_password'], $current_password)) {
                if ($post['password'] == $post['confirm_password']) {
                    $user_info['password'] = Hash::make($post['password']);
                    Users::where('id', $user_data->id)->update($user_info);
                    $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                    $arrResponse['message'] = Lang::get('global.PasswordUpdated');
                } else {
                    $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                    $arrResponse['message'] = Lang::get('global.PasswordNotMatched');
                }
            } else {
                $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                $arrResponse['message'] = Lang::get('global.OldPasswordNotMatched');
            }

            return response()->json($arrResponse, 200);
        } catch (Exception $e) {
            dd($e->getMessage());
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

    public function matchPassword($plain_password, $current_password) {

        if (Hash::check($plain_password, $current_password)) {
            return true;
        } else {
            return false;
        }
    }

    public function updateNotificationSettings(Request $request) {
        $login_id = session::get('looged_user_id');

        $post = $request->all();
        $user_data = Users::find($login_id);
        $notification_settings = $this->userRepo->checkNotificationSettings($user_data->id);
        if ($user_data) {
            if (isset($post['email_notification_type'])) {
                $settings_info['email_notification_id'] = implode(',', $post['email_notification_type']);
            } else {
                $settings_info['email_notification_id'] = null;
            }
            if (isset($post['app_notification_type'])) {
                $settings_info['in_app_notification_id'] = implode(',', $post['app_notification_type']);
            } else {
                $settings_info['in_app_notification_id'] = null;
            }
            $settings_info['notification_type_id'] = $post['notification_types'];
            if (!$notification_settings) {
                $settings_info['user_id'] = $user_data->id;
                UserNotificationSettings::create($settings_info);
            } else {
                UserNotificationSettings::where('user_id', $user_data->id)->update($settings_info);
            }

            $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
            $arrResponse['message'] = Lang::get('global.NotificationSettingsUpdated');
        } else {
            $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
            $arrResponse['message'] = Lang::get('global.somethingWentWrong');
        }
        return response()->json($arrResponse, 200);
    }

    public function deactivateAccount(Request $request) {
        try {
            $post = $request->all();
            $login_id = session::get('looged_user_id');
            $user_data = Users::find($login_id);

            if ($user_data) {
                $user_info['status'] = 3;
                $user_info['deactivation_reason_code'] = $post['reason'];
                Users::where('id', $user_data->id)->update($user_info);
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.DeactivatedSuccess');
            } else {
                $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                $arrResponse['message'] = Lang::get('global.AccountNotExists');
            }

            return response()->json($arrResponse, 200);
        } catch (Exception $e) {
            dd($e->getMessage());
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

    public function deactivated() {
        try {
            Session::flush();
            return view('users/deactivated');
        } catch (Exception $e) {
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

    public function userNotifications(Request $request) {
        $login_id = session::get('looged_user_id');
        $user = Users::find($login_id);
        $user_settings = DB::table('user_notification_settings')->where('user_id', '=', $login_id)->first();
        $notifications = DB::table('notification_types')->get();
        $email_notification_types = DB::table('email_notification_types')->get();
        $in_app_notification_types = DB::table('in_app_notification_types')->get();
        $user_deactivation_reasons = DB::table('user_deactivation_reasons')->get();
        $user_notifications = $this->userRepo->userNotifications($login_id);
//         dd($user_notifications);
        $notifications_unread_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
        if ($notifications_unread_count == 0) {
            $notifications_unread_count = 'no';
        }
//        foreach ($user_notifications as $n) {
////            dd($n);
//            $update['status'] = 2;
//
//            $notifications_status_update = DB::table('notifications')->where('id', $n->id)->update($update);
//        }

        if ($request->ajax()) {
            if ($user_notifications->count() > 0) {
                 $data['lastPage'] = $user_notifications->lastPage();
                $data['currentPage'] = $user_notifications->currentPage();
                $data ['html'] = view('users._partial_notifications', compact('login_id', 'user', 'user_settings', 'user_notifications', 'notifications', 'email_notification_types', 'in_app_notification_types', 'user_deactivation_reasons', 'notifications_unread_count'))->__toString();
                $data ['http_status'] = Response::HTTP_OK;
                $data ['type'] = count($user_notifications);
            } else {
//                $data ['html'] = view('users._partial_no_data')->__toString();
                $data ['http_status'] = Config::get('constants.HTTP_NO_DATA');
                $data ['type'] = count($user_notifications);
            }
            return json_encode($data);
        }

        $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
        return view('users/accountNotifications', compact('login_id', 'user', 'user_settings', 'user_notifications', 'notifications', 'email_notification_types', 'in_app_notification_types', 'user_deactivation_reasons', 'notifications_unread_count', 'notfication_count'));
    }

    public function activityDashboard() {

        $login_id = session::get('looged_user_id');
        $user = Users::find($login_id);
        $user_settings = DB::table('user_notification_settings')->where('user_id', '=', $login_id)->first();
        $notifications = DB::table('notification_types')->get();
        $email_notification_types = DB::table('email_notification_types')->get();
        $in_app_notification_types = DB::table('in_app_notification_types')->get();
        $user_deactivation_reasons = DB::table('user_deactivation_reasons')->get();
        $notfication_count = DB::table('notifications')->where('user_id', $login_id)->where('status', 1)->count();
        Session::put("filters", 'off');
        Session::put("search", 'off');
        $discussions_follow = $this->userRepo->followingDiscussions($login_id);
        $discussions_started = $this->userRepo->startedDiscussions($login_id);
        $favorites = $this->userRepo->getFavourites($login_id);
      //dd($favorites);exit();
        return view('users/accountActivity', compact('login_id', 'user', 'discussions_follow', 'favorites', 'user_settings', 'notifications', 'email_notification_types', 'in_app_notification_types', 'user_deactivation_reasons', 'notfication_count','discussions_started'));
    }

    public function usersList(Request $request) {
//        try {

        $login_id = session::get('looged_user_id');
        $search_term = $request->get('query');

        $getUsersList = $this->userRepo->getUsersList($search_term, $login_id);
        return json_encode($getUsersList);
//            } catch (Exception $e) {
//            throw new Exception($e->getMessage(), $e->getCode());
//        } catch (\Illuminate\Database\QueryException $qe) {
//            throw new Exception($qe->getMessage(), $qe->getCode());
//        }
    }

    public function updateNotificationStatus($notification_id) {
        $update['status'] = 2;
        $notifications_status_update = DB::table('notifications')->where('id', $notification_id)->update($update);
        $data ['http_status'] = Response::HTTP_OK;
        return json_encode($data);
    }

}
