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
use App\Models\UnAnsweredQuestions;
use App\Models\Questions;
use Symfony\Component\HttpFoundation\Response;
use App\Jobs\AddUserNotifications;
use Log;

class FinnController extends Controller {

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
            return view('users/finnLoad');
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (\Illuminate\Database\QueryException $qe) {
            throw new Exception($qe->getMessage(), $qe->getCode());
        }
    }

    public function postQuestion(Request $request) {
        try{
        $post = $this->request->all();
//        Log::info($post);
//        dd($post);
        $data['question'] = $post['question'];
        $data['hud_term'] = $post['hud_term'];
        Log::info($data);
        UnAnsweredQuestions::create($data);
        $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
        $arrResponse['message'] = Lang::get('global.UserUpdated');
        return response()->json($arrResponse, 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
            throw new Exception(Lang::get('global.somethingWentWrong'), $e->getCode());
        }
    }
    

    public function finnData(Request $request) {
        try {
            $post = $request->all();

            $intentColumns = Questions::SELECT('Table_Column_Headers', 'Relation_table')->where('Intent_Name', $post['Intent_Name'])->where('Data_Available', 'yes')->first();
//         dd($intentColumns, $post);
            IF ($intentColumns) {


                //cloumn name genarate 
                $column_name = str_replace(' ', '_', $intentColumns['Table_Column_Headers']);
                $column_name = str_replace('(', '_', $column_name);
                $column_name = str_replace(')', '', $column_name);
                $column_name = str_replace('/', '_', $column_name);
                $column_name = str_replace('-', '_', $column_name);
                $column_name = str_replace('__', '_', $column_name);

                if (strpos($column_name, ',') !== false) {
                    $myArray = explode(',', $column_name);

                    $columns = implode(',', $myArray);
                    $resultData = DB::connection('mysql2')
                            ->table($intentColumns['Relation_table'])
                            ->select($myArray)
                            ->where('HUC12', $post['HUC12_code'])
                            ->get();

//                dd($resultData);
                    $finnData = "Here the info FINN have :";
                    $notFound = null;
                    foreach ($resultData as $reviews) {
//                    print_r($reviews);

                        foreach ($myArray as $rowColumn) {
//                           print_r($rowColumn);
//                           print_r($reviews->$rowColumn);
//                     if($reviews->$rowColumn != null){
                            $finn_ans = $reviews->$rowColumn;
                            $rowColumn = str_replace('_', ' ', $rowColumn);
                            if ($rowColumn != null) {
                                $finnData .= '' . $rowColumn . ': ' . $finn_ans . ' ';
                                $notFound = ' ';
                            } else {
                                $notFound = "I don't have information about this watershed.";
                            }
                        }

                        $finnData .= '      ';
                    }
                    $arrResponse['https_status'] = 200;
                    if ($notFound != null) {
                        $arrResponse['data'] = $finnData;
                    } else {
                        $arrResponse['data'] = "I don't have information about this watershed.";
                    }
                    return json_encode($arrResponse);
                } else {
//                
                    $query = DB::connection('mysql2')
                            ->table($intentColumns['Relation_table']);
                    if ($intentColumns['Relation_table'] == 'Phosphorus_table') {
                        $query->select(DB::raw('group_concat(' . $column_name . ') as data'), 'States', 'Concentration_relative_to_proposed_standard as concentration');
                    } elseif ($intentColumns['Relation_table'] == 'Nitrogen_table') {
                        $query->select(DB::raw('group_concat(' . $column_name . ') as data'), 'Concentration_relative_to_suggested_criteria as concentration');
                    } else {
                        $query->select(DB::raw('group_concat( DISTINCT (' . $column_name . ') SEPARATOR "<br>") as data'));
                    }


                    $resultData = $query->where('HUC12', $post['HUC12_code'])->get();
//                dd($resultData);
                    if ($resultData[0]->data == NULL) {
                        $resultData[0]->data = ' Not found.';
                    }
                    $result_set = null;
                    if (is_numeric($resultData[0]->data)) {
                        $result_set = round($resultData[0]->data, 2);
                    } else {
                        $result_set = $resultData[0]->data;
                    }
                    $Table_Column_Headers = str_replace('_', ' ', $intentColumns['Table_Column_Headers']);
                    $arrResponse['https_status'] = 200;
                    if ($intentColumns['Relation_table'] == 'Impervious_Table') {
                        $Table_Column_Headers = str_replace('pct', 'percent', $Table_Column_Headers);
                        $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set;
                    } else if ($intentColumns['Relation_table'] == 'Ecodeficit_table') {

                        $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set . '%. A high level of ecological protection is provided when flow alterations are within 10% of the natural flow. ';
                    } else if ($intentColumns['Relation_table'] == 'Discharge_table') {
                        if ($Table_Column_Headers == 'Facility Name') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set . '. Facilities listed here have a permit to discharge into your watershed. For more information please go to...(DEC info locator or watershed viewer). ';
                        } elseif ($Table_Column_Headers == 'Waterbody') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set . '. Facilities listed here have a permit to discharge into these waterbodies. For more information please go to...(DEC info locator or watershed viewer).';
                        } else {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set . '.';
                        }
                    } else if ($intentColumns['Relation_table'] == 'Population_table') {
                        if ($Table_Column_Headers == 'Total Population in 2010') {
                            $arrResponse['data'] = 'The total population in 2010 (the most recent available data) was ' . $result_set . ' ';
                        } elseif ($Table_Column_Headers == 'Percent change 2000-2010') {
                            $arrResponse['data'] = 'The percent change in population between 2000 and 2010 was ' . $result_set . '%';
                        } elseif ($Table_Column_Headers == 'Difference in population 2000-2010') {
                            $arrResponse['data'] = 'Between 2000 and 2010, population (increased/decreased) by ' . $result_set . 'people';
                        } else {
                            $arrResponse['data'] = $Table_Column_Headers . ' was ' . $result_set;
                        }
                    } else if ($intentColumns['Relation_table'] == 'Phosphorus_table') {

                        if ($Table_Column_Headers == 'Point Source Total Load (kg/yr)' || $Table_Column_Headers == 'Manure Total Load (kg/yr)' || $Table_Column_Headers == 'Agricultural Fertilizer Total Load (kg/yr)' || $Table_Column_Headers == 'Forest Total Load (kg/yr)' || $Table_Column_Headers == 'Developed Areas Total Load (kg/yr)') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set . '. This load includes sources within your watershed and any watersheds that drain into it.';
                        } elseif ($Table_Column_Headers == 'Manure Local load (kg/yr)' || $post['Intent_Name'] == 'phosphorous_load_fertilizer' || $post['Intent_Name'] == 'phosphorous_load_forests') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set . '. This load does not include inputs from upstream.';
                        } elseif ($post['Intent_Name'] == 'phosphorous_load_developed_area' || $post['Intent_Name'] == 'phosphorous_load' || $post['Intent_Name'] == 'phosphorous_load_point_sources' || $Table_Column_Headers == 'Manure Local  load (kg/yr)') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set . '. This load does not include inputs from upstream.';
                        } elseif ($post['Intent_Name'] == 'phosphorous_load_upstream') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set . '. This is the load from your watershed and any watersheds that drain into it.';
                        } elseif ($Table_Column_Headers == 'Percentage of Total Load from Point Sources' || $Table_Column_Headers == 'Percentage of Total Load from Manure' || $Table_Column_Headers == 'Percentage of Total Load from Agricultural Fertilizers' || $Table_Column_Headers == 'Perecentage of Total Load from Natural Sources' || $Table_Column_Headers == 'Percentage of Total Load from Developed Areas' || $post['Intent_Name'] == 'phosphorous_percentage_natural_sources_upstream') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . round($result_set,1) . '%. This percentage includes sources within your watershed and any watersheds that drain into it.';
                        } elseif ($Table_Column_Headers == 'Percentage of Local HUC12 load from Point Sources' || $Table_Column_Headers == 'Percentage of Local HUC12 load from Manure' || $Table_Column_Headers == 'Percentage of Local HUC12 load from Agricultural Fertilizers' || $Table_Column_Headers == 'Percentage of Local HUC12 load from Natural Sources' || $Table_Column_Headers == 'Pecentage of Local HUC12 load from Developed Areas') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set . '%. This percentage does not include inputs from upstream.';
                        } elseif ($post['Intent_Name'] == 'phosphorous_concentration') {
                            $arrResponse['data'] = 'The phosphorus concentration in your watershed is ' . $result_set . ' mg/L. The phosphorus concentration in your watershed is  ' . $resultData[0]->concentration . '. The proposed criteria for phosphorus in NY state is 0.02 mg/L.   .';
                        } else {
                            $arrResponse['data'] = $Table_Column_Headers . ' was ' . $result_set;
                        }
                    } else if ($intentColumns['Relation_table'] == 'Nitrogen_table') {
                        if ($Table_Column_Headers == 'Agricultural Sources Total Load (kg)' || $Table_Column_Headers == 'Manure Total Load (kg)' || $Table_Column_Headers == 'Agricultural Fertilizer Total Load (kg)' || $Table_Column_Headers == 'Agricultural Area Total Load (kg)' || $Table_Column_Headers == 'Developed Area Total Load (kg)' || $Table_Column_Headers == 'Point Source Total Load (kg)') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set . '. This load includes sources within your watershed and any watersheds that drain into it.';
                        } elseif ($Table_Column_Headers == 'Point Source Local Load (kg)' || $Table_Column_Headers == 'Atmospheric Local Load (kg)' || $Table_Column_Headers == 'Agricultural Fertilizer Local Load (kg)' || $Table_Column_Headers == 'Agricultural Area Local Load (kg)' || $Table_Column_Headers == 'Developed Local load (kg/yr)' || $Table_Column_Headers == 'Local Load (kg)') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set . '. This load does not include inputs from upstream.';
                        } elseif ($Table_Column_Headers == 'Total Load (kg)') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set . '. This is the load from your watershed and any watersheds that drain into it.';
                        } elseif ($Table_Column_Headers == 'Percentage of Local Load from Point Sources' || $Table_Column_Headers == 'Percentage of Local Load from Atmospheric' || $Table_Column_Headers == 'Percentage of Local Load from Manure' || $Table_Column_Headers == 'Percentage of Local Load from Agricutural Fertilizers' || $Table_Column_Headers == 'Percentage of Local Load from Agricultural Area' || $Table_Column_Headers == 'Manure Local Load (kg)' || $Table_Column_Headers == 'Pecentage of Local HUC12 load from Developed Areas') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . round($result_set,1) . '%. This percentage does not include inputs from upstream.';
                        } elseif ($Table_Column_Headers == 'Percentage of Total Load from Point Sources' || $Table_Column_Headers == 'Percentage of Total Load from Atmospheric' || $Table_Column_Headers == 'Percentage of Total Load from Manure' || $Table_Column_Headers == 'Percentage of Total Load from Agricultural Fertizliers' || $Table_Column_Headers == 'Percentage of Total Load from Agricultural Area' || $Table_Column_Headers == 'Percentage of Total Load from Developed Areas') {
                            $arrResponse['data'] = $Table_Column_Headers . ' is ' . round($result_set,1) . '%. This percentage includes sources within your watershed and any watersheds that drain into it.';
                        } elseif ($Table_Column_Headers == 'Nitrogen concentraiton (mg/L)') {
                            $arrResponse['data'] = 'The concentration is ' . $result_set . 'mg/L. This concentration is ' . $resultData[0]->concentration . '.  The proposed freshwater criteria for nitrogen in NY state is 0.48 mg/L.'; //in '.$resultData[0]->States.' state 
                        } else {
                            $arrResponse['data'] = $Table_Column_Headers . ' was ' . $result_set;
                        }
                    } else {
                        $arrResponse['data'] = $Table_Column_Headers . ' is ' . $result_set;
                    }

                    return json_encode($arrResponse);
//                return json_encode(array("status=200", 'message' => $resultData));
                }
            } else {
                return json_encode(array("status=400", 'data' => "I don't have information about this watershed."));
            }
        } catch (Exception $e) {
//            throw new Exception($e->getMessage(), $e->getCode());
            return json_encode(array("status" => $e->getCode(), 'data' => $e->getMessage()));
        } catch (\Illuminate\Database\QueryException $qe) {
            return json_encode(array("status" => $qe->getCode(), 'data' => $qe->getMessage()));
        }
    }

    public function postCurlData(Request $request) {
        $post = $request->all();
//dd($post);
        $url = 'https://dop1u55os2.execute-api.us-west-2.amazonaws.com/TestCase1/waterquality';
        $data = [
            "intent_name" => $post['intent_name'],
            "HUC12_code" => $post['HUC12_code']
        ];



        $ch = curl_init();
        $headers = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);
        $result = json_decode($result, TRUE);

        curl_close($ch);

        $arrResponse = [];
        if ($result['statusCode'] == 200) {
            $type = '';
            if (strpos($post['intent_name'], 'nitrogen') !== false) {
                $type = 'Nitrogen';
            }

            if (strpos($post['intent_name'], 'phosphorus') !== false) {
                $type = 'Phosphorus';
            }

            $arrResponse['statusCode'] = $result['statusCode'];
            $arrResponse['message'] = $type . ' ' . strtolower(str_replace("_", " ", $result['column_name'])) . ' is ' . $result['HUC_value'];
            $arrResponse['column_name'] = $type . ' ' . strtolower(str_replace("_", " ", $result['column_name']));
            $arrResponse['dialogState'] = 'Fulfilled';
        } else {
            $arrResponse['statusCode'] = $result['statusCode'];
            $arrResponse['message'] = 'Unalbe to found watershed information with this location details. Please contact admin.'; // $result['body'];
        }
        return $arrResponse;
    }
    
    
    
    public  function finnSession(Request $request){
//        print_r(Session::get('lex_data'));
        $post =$request->all();
        $postArray = array('text'=>$post['question'], 'type'=>$post['type']);
        Session::push('lex_data', $postArray);
        
        $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
        $arrResponse['message'] = $post['question'];
        
        return response()->json($arrResponse, 200);
    
        
    }
    
    public function  saveHUDTerm(Request $request){
        $post =$request->all(); 
        Session::put('hud_term', $post['hud_term']);
        
        $arrResponse['http_status'] = Config::get('constants.HTTP_OK');
        $arrResponse['message'] = $post['hud_term'];
        
        return response()->json($arrResponse, 200);
    }
    

}
