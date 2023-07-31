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
class GuestAccessMiddleware {

    protected $admin;

    public function __construct(User $user) {

        $this->user = $user;
    }

    public function handle($request, Closure $next) {
        try {
            if (session('session_key') == env('APP_NAME') && !empty(session('looged_user_id'))) {
                 return redirect('/discussions');
            } else {
                 return $next($request);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 400);
        }
    }

}
