<?php

namespace App\Repositories\EloquentRepositories;

use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Eloquent\Repository;
use DB;
use Exception;

/**
 * UserDevices class.
 *
 * @since    1.0.0
 * @version  1.0.0
 */
class AdminRepository extends Repository {

    /**
     * Returns the name of the model class to be
     * used by this repository.
     *
     * @access  public
     * @return  string
     * @since   1.0.0
     */
    public function model() {
        return 'App\Models\Users';
    }

    public function passwordTokenInsert($data) {
        $query = DB::table('password_reset')->insert($data);

        return $query ? $query : false;
    }

    public function checkPasswordToken($token) {
        $query = DB::table('password_reset')
                ->where('token', '=', $token)
                ->first();

        return $query ? $query : false;
    }

    public function deleteResetPassToken($where) {
        $query = DB::table('password_reset')
                ->where($where)
                ->delete();

        return $query ? $query : false;
    }

    public function findByEmail($where) {
        $query = DB::table('users');
        $query->where($where);
        $query->where('role', '=', 2);
        $data = $query->first();
        return $data ? $data : false;
    }

}
