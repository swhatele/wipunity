<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Middleware;

use Closure;
use Lang;
use Config;
use Session;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\Users;
use App\Repositories\EloquentRepositories\UserRepository as User;

/**
 * Description of UserAccessMiddleware
 *
 * @author appit
 */
class UserAccessMiddleware {

    protected $admin;

    public function __construct(User $user) {

        $this->user = $user;
    }

    public function handle($request, Closure $next) {
        try {


            if (!Auth::check()) {

                Session::flash('message', trans('global.SessionExpired'));
                Session::flash('type', 'warning');

                  return redirect('/entry#Login');
            }

            if (session('session_key') == env('APP_NAME') && !empty(session('looged_user_id'))) {
                $user_id = Session::get('looged_user_id');
                $user_details = $this->user->getUserDetails($user_id);
                $add['looged_user_id'] = session('admin_id');
                $add['logged_user_email'] = session('logged_user_email');
                $add['logged_user_name'] = $user_details->name;
                $request->merge($add);
                return $next($request);
            } else {
                if ($request->ajax()) {
                    $arrResponse['http_status'] = 440; //session expired
                    $arrResponse['message'] = Lang::get('global.SessionExpired');
                    return response()->json($arrResponse, 200);
                } else {
                    return redirect('/entry#Login');
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 400);
        }
    }

}
