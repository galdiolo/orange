<?php
/**
* Orange Framework Extension
*
* This content is released under the MIT License (MIT)
*
* @package	CodeIgniter / Orange
* @author	Don Myers
* @license	http://opensource.org/licenses/MIT	MIT License
* @link	https://github.com/dmyers2004
*/
class o_role_access_model extends Database_model {
	protected $table = 'orange_role_access';
	protected $rules = [
		'role_id' => ['field' => 'role_id','label' => 'Role Id','rules' => 'if_empty[0]|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'access_id' => ['field' => 'access_id','label' => 'Access Id','rules' => 'if_empty[0]|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
	];
	protected $rule_sets = [
		'insert' => 'role_id,access_id',
		'update' => 'role_id,access_id',
	];

	/* special insert/update */
	public function save($role_id, $access) {
		$this->delete_by_role_id($role_id);

		foreach ((array) $access as $access_id) {
			$this->insert(['role_id' => $role_id, 'access_id' => $access_id]);
		}
	}

	public function delete_by_role_id($role_id) {
		return $this->delete_by('role_id', $role_id);
	}

	public function delete_by_access_id($access_id) {
		return $this->delete_by('access_id', $access_id);
	}

	public function get_many_by_role_id($role_id) {
		$access_table = ci()->o_access_model->table();

		return $this->_database
			->where($this->table.'.role_id', $role_id)
			->join($access_table, $access_table.'.id = '.$this->table.'.access_id')
			->get($this->table)
			->result();
	}

	public function get_many_by_access_id($access_id) {
		$role_table = ci()->o_role_model->table();

		return $this->_database
			->where($this->table.'.access_id', $access_id)
			->join($role_table, $role_table.'.id = '.$this->table.'.role_id')
			->get($this->table)
			->result();
	}
} /* end class */