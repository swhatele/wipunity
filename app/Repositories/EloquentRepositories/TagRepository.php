<?php

namespace App\Repositories\EloquentRepositories;

use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Eloquent\Repository;
use DB;
use Exception;

/**
 * Description of UserSettingsRepository
 *
 * @author appit
 */
class TagRepository extends Repository {

    public function model() {
        return 'App\Models\Tags';
    }

    /**
     * 
     * @param type $sort_by
     * @param type $sort_type
     * @param type $search_query
     * @param type $filter_value
     * @return type
     */
    public function getTagsList($sort_by = null, $sort_type = null) {

        $query = DB::table('tags');
        $query->select('id', 'tag_name', 'created_at');

        if ($sort_by != '' && $sort_type != '') {
            $query->orderBy($sort_by, $sort_type);
        } else {
            $query->orderBy('created_at', 'asc');
        }

        $data = $query->paginate(10);

        return $data ? $data : false;
    }

    public function checkExists($tag_name,  $tag_id) {
        
        $query = \App\Models\Tags::where('tag_name', $tag_name);
        if ($tag_id != null) {
            $query->where('id', '!=', $tag_id);
        }
        $list =$query->get(); 
        if (count($list) == 0) {
            return true;
        } else {
            return false;
        }
    }

    //------------------------------------------------------------------------------------------
    //----------------------------------------------------------------------------------------------
}
