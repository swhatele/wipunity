<?php namespace App\Repositories\Contracts;

interface RepositoryInterface {
	
	public function all($columns = array('*'));

	public function create(array $data);

	public function update(array $data, $id);

	public function delete($id);

	public  function find($id, $columns = array('*'));

	public function findBy($field, $value, $columns = array('*'));
}
//end of interface RepositoryInterface
//end of file RepositoryInterface.php