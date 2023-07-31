<?php namespace App\Repositories\Criteria;

use App\Reopositories\Contracts\RepositoryInterface as Repository;
use App\Reopositories\Contracts\ReopositoriesInterface;

abstract class Criteria {

	/**
	 *
	 * @param   $model
	 * @param   RepositoryInterface  $repository
	 * @return  mixed
	 */
	public abstract function apply($model, Repository $repository);
}
//end of class Criteria
//end of file Criteria.php