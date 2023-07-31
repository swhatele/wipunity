<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Exceptions\RepositoryException;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Criteria\Criteria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Container\Container as App;
use Illuminate\Support\Collection;

/**
 * Class Repository
 *
 * @abstract
 * @since	v1.0.0
 * @version	1.0.0
 */
abstract class Repository implements RepositoryInterface, CriteriaInterface {

    /**
     * Instance of App 
     * 
     * @var		App
     * @access	private
     * @since	v1.0.0
     */
    private $app;

    /**
     *
     * @var
     * @access	protected
     * @since	v1.0.0
     * */
    protected $model;

    /**
     * @var     Collection
     * @access  protected
     * @since   v1.0.0
     */
    protected $criteria;

    /**
     * @var  bool
     */
    protected $skipCriteria = false;
    //protected $dateConvert;

    //--------------------------------------------------------

    /**
     * Constructor Method 
     *
     * @access	public
     * @param	App	$app
     * @throws	Apen\Repositories\Exceptions\RepositoryException
     * @since	1.0.0
     */
    public function __construct(App $app, Collection $collection) {
        $this->app = $app;
        $this->criteria = $collection;
        $this->resetScope();
        $this->makeModel();
    }

    //--------------------------------------------------------------------

    /**
     * Initializes the class variable model.
     *
     * @access	public
     * @return	Model
     * @throws	RepositoryException
     * @since	1.0.0
     */
    public function makeModel() {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }
        return $this->model = $model;
    }

    //--------------------------------------------------------------------

    /**
     * Specifies the model class name.
     *
     * @abstract
     * @return	mixed
     * @since	1.0.0
     * */
    abstract function model();

    //-------------------------------------------------------------------- 

    /**
     * Returns all the records from the table.
     *
     * @access    public
     * @param     array   $columns
     * @return    mixed
     * @since     1.0.0
     */
    public function all($columns = array('*')) {
        $this->applyCriteria();
        return $this->model->get($columns);
    }

    //--------------------------------------------------------------------

    /**
     * Writes information into the tables.
     *
     * @access  public
     * @param   array  $data
     * @return  mixed
     * @since   1.0.0
     */
    public function create(array $data) {
        return $this->model->create($data);
    }

    //--------------------------------------------------------------------

    /**
     *
     * @access  public
     * @param   array    $data
     * @param   integer  $id
     * @param   string   $attribute
     * @return  mixed
     * @since   1.0.0
     */
    public function update(array $data, $id, $attribute = 'id') {
        return $this->model->where($attribute, '=', $id)->update($data);
    }

    //--------------------------------------------------------------------

    /**
     * 
     * @access  public
     * @param   integer  $id
     * @return  mixed
     * @since   1.0.0
     */
    public function delete($id) {
        return $this->model->destroy($id);
    }

    //--------------------------------------------------------------------

    /**
     * 
     * @access  public
     * @param   integer $id
     * @param   array   $columns
     * @return  mixed
     * @since   1.0.0
     */
    public function find($id, $columns = array('*')) {
        $this->applyCriteria();
        return $this->model->find($id, $columns);
    }

    //-------------------------------------------------------------------

    /**
     * 
     * @access  public
     * @param   string  $attribute
     * @param   mixed   $value
     * @param   array   $columns
     * @return  mixed
     * @since   1.0.0
     */
    public function findBy($attribute, $value, $columns = array('*')) {
        $this->applyCriteria();
        return $this->model->where($attribute, '=', $value)->first($columns);
    }

    //-------------------------------------------------------------------

    /**
     * Returns all the records using 
     * increasing or decreasing order from the table.
     *
     * @access    public
     * @param     array   $columns
     * @return    mixed
     * @since     1.0.0
     */
    public function findByLast($attribute, $value, $columns = array('*')) {
        $this->applyCriteria();
        return $this->model->where($attribute, '=', $value)
                        ->orderBy('id', 'desc')->first($columns);
    }

    //-------------------------------------------------------------------

    /**
     * 
     * @access  public
     * @param   string  $attribute
     * @param   mixed   $value
     * @param   array   $columns
     * @return  mixed
     * @since   1.0.0
     */
    public function findMultipleBy($attribute, $value, $columns = array('*')) {
        $this->applyCriteria();
        return $this->model->where($attribute, '=', $value)->select($columns)->get();
    }

    //-------------------------------------------------------------------

    /**
     * Find a collection of models by the given query conditions.
     *
     * @param array $where
     * @param array $columns
     * @param bool $or
     *
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function findWhere($where, $columns = ['*'], $or = false) {
        $this->applyCriteria();

        $model = $this->model;

        foreach ($where as $field => $value) {
            if ($value instanceof \Closure) {
                $model = (!$or) ? $model->where($value) : $model->orWhere($value);
            } elseif (is_array($value)) {
                if (count($value) === 3) {
                    list($field, $operator, $search) = $value;
                    $model = (!$or) ? $model->where($field, $operator, $search) : $model->orWhere($field, $operator, $search);
                } elseif (count($value) === 2) {
                    list($field, $search) = $value;
                    $model = (!$or) ? $model->where($field, '=', $search) : $model->orWhere($field, '=', $search);
                }
            } else {
                $model = (!$or) ? $model->where($field, '=', $value) : $model->orWhere($field, '=', $value);
            }
        }

        return $model->get($columns);
    }

    //-------------------------------------------------------------------
    /**
     *
     * @access  public
     * @return  $this
     * @since   1.0.0
     */
    public function resetScope() {
        $this->skipCriteria(false);
        return $this;
    }

    //-------------------------------------------------------------------

    /**
     * 
     * @access  public
     * @param   bool  $status
     * @return  $this
     * @since   1.0.0
     */
    public function skipCriteria($status = true) {
        $this->skipCriteria = $status;
        return $this;
    }

    //-------------------------------------------------------------------

    /**
     * 
     * @access  public
     * @param   return mixed
     * @since   1.0.0
     */
    public function getCriteria() {
        return $this->criteria;
    }

    //-------------------------------------------------------------------

    /**
     * 
     * @access  public
     * @param   Criteria $criteria
     * @return  mixed
     * @since   1.0.0
     */
    public function getByCriteria(Criteria $criteria) {
        $this->model = $criteria->apply($this->model, $this);
        return $this;
    }

    //-------------------------------------------------------------------

    /**
     * 
     * @access  public
     * @param   Criteria $criteria
     * @return  $this
     * @since   1.0.0
     */
    public function pushCriteria(Criteria $criteria) {
        $this->criteria->push($criteria);
        return $this;
    }

    //-------------------------------------------------------------------

    /**
     * 
     * @access   public
     * @return   $this
     * @since    1.0.0
     */
    public function applyCriteria() {
        if ($this->skipCriteria === true) {
            return $this;
        }

        foreach ($this->getCriteria() as $criteria) {
            if ($criteria instanceof Criteria) {
                $this->model = $criteria->apply($this->model, $this);
            }
        }

        return $this;
    }

    //-------------------------------------------------------------------

    /**
     * Removes the rows matching the passed conditions.
     *
     * @access   public
     * @param    string   $column
     * @param    string   $value
     * @param    string   $condition
     * @since    1.0.0
     */
    public function deleteWhere($column, $value, $condition = '=') {
        return $this->model->where($column, $condition, $value)->delete();
    }

}

//end of class Repository
//end of file Repository.php