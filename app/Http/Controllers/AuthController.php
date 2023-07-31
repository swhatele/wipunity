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
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Hash;
use App\Repositories\EloquentRepositories\AdminRepository as Admin;
use App\Repositories\EloquentRepositories\UserRepository as User;
use App\Models\Users;
use App\Models\UserNotificationSettings;
use DB;
use AWS;

/**
 * Description of AuthController
 *
 * @author appit
 */
class AuthController extends Controller {

    protected $request;
    protected $hasher;
    protected $admin;
    protected $user;

    public function __construct(Request $request, Hasher $hasher, Admin $admin, User $user) {

        $this->request = $request;
        $this->hasher = $hasher;
        $this->admin = $admin;
        $this->user = $user;
    }

    
    /**his is used to display the landing page*/
    
    public function landing(){
          if (Session::has('session_key') && Session::get('session_key') == env('APP_NAME')) {
            return redirect(url('discussions'));
        }
         return view('landing/landing');
    }
    
    
    /**
     * This is used to display the login page
     * @return type
     */
    public function entry() {
        if (Session::has('session_key') && Session::get('session_key') == env('APP_NAME')) {
            return redirect(url('discussions'));
        } else {
            Session::flush();
            return view('users/login');
        }
    }

    public function forgotPassword() {
          if (Session::has('session_key') && Session::get('session_key') == env('APP_NAME')) {
            return redirect(url('discussions'));
        }
        return view('users/forgotPassword');
    }

    /**
     * This is method is used to login in to the application
     */
    public function userLogin() {
        try {
            $post = $this->request->all();
            $where = array(
                'email' => strtolower($post['email']),
            );


            $user_data = $this->admin->findByEmail($where);
            if ($user_data) {
                if( $user_data->status == 2){
                
                $current_password = $user_data->password;
                if ($this->matchPassword($post['password'], $current_password)) {
                    session(['looged_user_id' => $user_data->id]);
                    session(['logged_user_email' => $user_data->email]);
                    session(['session_key' => env('APP_NAME')]);
                    $user = Users::find($user_data->id);
                    Auth::login($user);

                    $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                    $arrResponse['message'] = Lang::get('global.LoginSuccess');
                } else {
                    $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                    $arrResponse['message'] = Lang::get('global.LoginFailed');
                }
                }
                 else  if( $user_data->status == 3){
                     $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                $arrResponse['message'] = Lang::get('global.AccountDeactivated');
                 }
                else {
                $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                $arrResponse['message'] = Lang::get('global.AccountNotActivated');
            }
            } else {
                $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                $arrResponse['message'] = Lang::get('global.LoginFailed');
            }
            return response()->json($arrResponse, 200);
        } catch (Exception $e) {
            dd($e->getMessage());
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

    public function userRegister(Request $request) {
        $post = $this->request->all();
        try {

//            $user_data = $this->user->isUserExist(strtolower($post['email']));
////            dd($user_data);
            session(['email' => $post['email']]);
            session(['registration_status' => 'Processing']);
            if (!$this->user->isUserExist(strtolower($post['email']))) {

                $user_info['email'] = $post['email'];
                $user_info['name'] = $post['first_name'] . ' ' . $post['last_name'];
                $user_info['password'] = Hash::make($post['password']);
                $user_info['company_name'] = $post['company_name'];
                $user_info['title'] = $post['title'];
                $user_info['status'] = 1;
                $user_info['role'] = 2;
                $user_info['on_boarding_status'] = 2;
                Users::create($user_info);
                session($user_info);
                $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                $arrResponse['message'] = Lang::get('global.AccountCreated');
                $arrResponse['on_boarding_status'] = "2";
            } else {
                $user_data = $this->user->isUserExist(strtolower($post['email']));
//                dd($user_data);
                if (isset($user_data) && $user_data->on_boarding_status == 5) {

                    $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
                    $arrResponse['message'] = Lang::get('global.UserAlreadyExist');
                    $arrResponse['on_boarding_status'] = $user_data->on_boarding_status;
                } elseif (isset($user_data) && $user_data->on_boarding_status != 5) {
                    $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                    $arrResponse['message'] = Lang::get('global.UserAlreadyExist');
                    $arrResponse['on_boarding_status'] = $user_data->on_boarding_status;
                }
            }
            return response()->json($arrResponse, 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
//            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

    public function signupStep2() {

        $register_email = session::get('email');
        $user_data = $this->user->isUserExist($register_email);

        return view('users/step2', compact('user_data'));
    }

    public function updateUserData(Request $request) {
        try {
            $post = $request->all();
            $user_data = $this->user->isUserExist($post['email']);
            $user_info['email'] = $post['email'];
            $user_info['name'] = $post['name'];
            $user_info['on_boarding_status'] = 3;
//Upload image to s3
            $uploaded_image = $post['uploaded_image'];
//            if(!isset($uploaded_image) && $user_data->profile_icon == null){
//                $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
//            $arrResponse['message'] ='Please upload profile picture.';
//            return response()->json($arrResponse, 200);
//            }
            if (isset($uploaded_image)) {
                $s3 = AWS::createClient('s3');
                try {

                    $img_guid = 'TNC-' . $user_data->id . date('YmdHis');
                    $imageFileName = $img_guid . '.png';
                    $imageUpload = $s3->putObject(array(
                        'Key' => env('S3_BUCKET_DOCUMENTS') . '/' . $imageFileName,
                        'Bucket' => env('S3_BUKET_PROFILE'),
                        'SourceFile' => $uploaded_image
                    ));
                    if (isset($imageUpload) && $imageUpload['@metadata']['statusCode'] == 200) {
                        Users::where('id', $user_data->id)->update(['profile_icon' => $imageFileName]);
                    }
                } catch (Exception $e) {
                    echo 'Caught exception: ', $e->getMessage(), "\n";
                }
            }
             
//Upload image to s3

            Users::where('id', $user_data->id)->update($user_info);
            $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
            $arrResponse['message'] = Lang::get('global.UserUpdated');
            $arrResponse['on_boarding_status'] = "3";
            return response()->json($arrResponse, 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

    public function signupStep3() {
        $register_email = session::get('email');
        $user_data = $this->user->isUserExist($register_email);
        $user_settings = DB::table('user_notification_settings')->where('user_id', '=', $user_data->id)->first();
//        dd($user_settings);
        $notifications = DB::table('notification_types')->get();
        $email_notification_types = DB::table('email_notification_types')->get();
        $in_app_notification_types = DB::table('in_app_notification_types')->get();

        return view('users/step3', compact('user_data', 'user_settings', 'notifications', 'email_notification_types', 'in_app_notification_types'));
    }

    public function updateUserSettings(Request $request) {

        $post = $request->all();


        $register_email = session::get('email');
        $user_data = $this->user->isUserExist($register_email);
        $notification_settings = $this->user->checkNotificationSettings($user_data->id);
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
            $user_info['on_boarding_status'] = 3;

            Users::where('id', $user_data->id)->update($user_info);
            $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
            $arrResponse['message'] = Lang::get('global.UserUpdated');
            $arrResponse['on_boarding_status'] = "4";
        } else {
            $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
            $arrResponse['message'] = Lang::get('global.somethingWentWrong');
            $arrResponse['on_boarding_status'] = $user_data->on_boarding_status;
        }
        return response()->json($arrResponse, 200);
    }

    public function signupStep4() {
        $register_email = session::get('email');
        $user_data = $this->user->isUserExist($register_email);

        return view('users/step4', compact('user_data'));
    }

    public function updateCodeConductStatus(Request $request) {
        $post = $request->all();
        $register_email = session::get('email');
        $user_data = $this->user->isUserExist($register_email);
        if ($post['code_of_conduct']) {
            $user_info['on_boarding_status'] = 5;
            Users::where('id', $user_data->id)->update($user_info);
            
            $mailBody = array(
                                'email' => $user_data->email,
                                'name' => $user_data->name, 
                            );
                            Mail::send('emails.registrationSuccess', $mailBody, function ($m) use ($mailBody) {
                                $m->from(env('MAIL_FROM_ADDRESS'), Lang::get('global.TNCTeam'));
                                $m->to($mailBody['email'], $mailBody['name'])->subject(Lang::get('global.TNCAccountCreatedSuccessfully'));
                            });
            $admin_ids=Users::select('email')->where('id','<>',1)->where('role',1)->get();
            
            $emails=array();
            foreach($admin_ids as $ids)
            {
                $emails[]=$ids['email'];
            }
            $adminMailBody =array(
                'user_email' => $user_data->email,
                'name' => $user_data->name,
                'emails'=>$emails
            );
            Mail::send('emails.sendAdminUserRegistrationSucess', $adminMailBody, function($m) use ($adminMailBody)
            {    
                $m->from(env('MAIL_FROM_ADDRESS'), Lang::get('global.TNCTeam'));
                $m->to($adminMailBody['emails'], $adminMailBody['name'])->subject(Lang::get('global.TNCNewAccountCreadted'));
            });
                            
                            
            session(['looged_user_id' => $user_data->id]);
            session(['logged_user_email' => $user_data->email]);
            session(['session_key' => env('APP_NAME')]);
            $user = Users::find($user_data->id);
            Auth::login($user);
            Session::forget('registration_status');
            $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
            $arrResponse['message'] = Lang::get('global.UserUpdated');
        } else {
            $arrResponse['http_status'] = Config::get('constants.DATA_NOT_MATCH');
            $arrResponse['message'] = Lang::get('global.somethingWentWrong');
        }
        return response()->json($arrResponse, 200);
    }

    public function signupCompleted() {
        Session::flush();
        return view('users/signupComplete');
    }

//----------------------------------------------------------------------------
    /**
     * 
     * @param type $plain_password
     * @param type $current_salt
     * @param type $current_password
     * @return boolean
     */
    public function matchPassword($plain_password, $current_password) {

        if (Hash::check($plain_password, $current_password)) {
            return true;
        } else {
            return false;
        }
    }

//----------------------------------------------------------------------------
    /**
     * 
     * @return type
     * @throws Exception
     */
    public function forgotPasswordRequest() {
        try {
            $post = $this->request->all();
            $where = array(
                'email' => strtolower($post['email']),
            );
            $user_data = $this->admin->findByEmail($where);
            if ($user_data) {
                $randomUid = md5($user_data->email . date('ymdHis'));
                $forgotPassword = array(
                    'email' => $user_data->email,
                    'user_id' => $user_data->id,
                    'token' => $randomUid,
                    'created_at' => gmdate("Y-m-d H:i:s"),
                );
                if ($this->admin->passwordTokenInsert($forgotPassword)) {
                    $link = url($randomUid . "/resetPassword");
                    $mailBody = array(
                        'email' => $post['email'],
                        'name' => $post['email'],
                        'link' => $link,
                    );
                    Mail::send('emails.forgotPasswordLink', $mailBody, function ($m) use ($mailBody) {
                        $m->from(env('MAIL_FROM_ADDRESS'), Lang::get('global.TNCTeam'));
                        $m->to($mailBody['email'], $mailBody['name'])->subject(Lang::get('global.TNCSupportReset'));
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
            return response()->json($arrResponse, 200);
        } catch (Exception $e) {
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

//--------------------------------------------------------------------------
    /**
     * Reset password page.
     * 
     * @param type $token
     * @return type
     */
    public function resetPassword($token) {
        try {
            if ($data = $this->admin->checkPasswordToken($token)) {
                $then = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $data->created_at);
                $now = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', gmdate("Y-m-d H:i:s"));
//            $diff_in_hours = $now->diffInMinutes($then) / 60;
//            if ($diff_in_hours > 24) { //check the link expiration if the link is grater than 24 hours then it's expired
//                return view('users/resetPasswordExpired');
//            }
                return view('users/resetPassword')->with(['token' => $token]);
            } else {
                return view('users/resetPasswordExpired');
            }
        } catch (Exception $e) {
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

//--------------------------------------------------------------------------
    /**
     * Reset new password by clicking email link.
     * 
     * @return type
     */
    public function resetNewPassword() {
        try {
            $post = $this->request->all();
            $token = $post['resetToken'];
            if ($data = $this->admin->checkPasswordToken($token)) {
                $email = $data->email;
                $user_id = $data->user_id;
                $secret = Hash::make($post['new_password']);
                $update = array(
                    'password' => $secret,
                );
                if ($this->user->update($update, $email, 'email')) {
                    $where = array(
                        'email' => $email,
                        'user_id' => $user_id,
                    );
                    $this->admin->deleteResetPassToken($where);
                    $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
                    $arrResponse['message'] = Lang::get('global.passwordSuccess');
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

//----------------------------------------------------------------------------
    /**
     * Admin can logout from admin portal.
     * 
     * @return type
     * @throws Exception
     */
    public function userLogout() {
        try {
            Session::flush();
            return view('users/logout');
        } catch (Exception $e) {
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }

}
