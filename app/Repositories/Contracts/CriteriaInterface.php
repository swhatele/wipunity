<?php namespace App\Repositories\Contracts;

use App\Repositories\Criteria\Criteria;

/**
 * Criteria Interface.
 */

interface CriteriaInterface {

	/**
	 * @param   bool $status
	 * @return  $this
	 */
	public function skipCriteria($status = true);

	/**
	 * @return  mixed
	 */
	public function getCriteria();

	/**
	 * @param  Criteria $criteria
	 * @param  $this
	 */
	public function getByCriteria(Criteria $criteria);

	/**
	 * @param  Criteria $criteria
	 * @param  $this
	 */
	public function pushCriteria(Criteria $criteria);

	/**
	 *
	 */
	public function applyCriteria();
}
//end of interface CriteriaInterface
//end of file CriteriaInterface.php