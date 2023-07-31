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
use App\Models\Users;
use App\Repositories\EloquentRepositories\UserRepository as User;

/**
 * Description of UserAccessMiddleware
 *
 * @author appit
 */
class RegisterAccessMiddleware {

    protected $admin;

    public function __construct(User $user) {

        $this->user = $user;
    }

    public function handle($request, Closure $next) {
        try {
             $status = Session::get('registration_status') ;
            $email = Session::get('email');
            if ($status == 'Processing' && !empty($email)) {
                 
                $user_details = $this->user->isUserExist($email);
//                session($user_details);
                $add['registration_status'] = 'Processing';
                $add['email'] = $email;
                $request->merge($add);
                return $next($request);
            } else {
//                if ($request->ajax()) {
//                    $arrResponse['http_status'] = 440; //session expired
//                    $arrResponse['message'] = Lang::get('global.SessionExpired');
//                    return response()->json($arrResponse, 200);
//                } else {
                    return redirect('/');
//                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 400);
        }
    }

}
