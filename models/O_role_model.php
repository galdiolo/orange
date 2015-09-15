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
class o_role_model extends Database_model {
	protected $table = 'orange_roles';
	protected $rules = [
		'id'              => ['field' => 'id','label' => 'Id','rules' => 'required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'created_on'      => ['field' => 'created_on','label' => 'Created On','rules' => 'if_empty[now(Y-m-d H:i:s)]|required|max_length[24]|valid_datetime|filter_input[24]'],
		'created_by'      => ['field' => 'created_by','label' => 'Created By','rules' => 'if_empty[user()]|required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'updated_on'      => ['field' => 'updated_on','label' => 'Updated On','rules' => 'if_empty[now(Y-m-d H:i:s)]|required|max_length[24]|valid_datetime|filter_input[24]'],
		'updated_by'      => ['field' => 'updated_by','label' => 'Updated By','rules' => 'if_empty[user()]|required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'is_editable'     => ['field' => 'is_editable','label' => 'Editable','rules' => 'if_empty[1]|one_of[0,1]|filter_int[1]|max_length[1]'],
		'is_deletable'    => ['field' => 'is_deletable','label' => 'Deletable','rules' => 'if_empty[1]|one_of[0,1]|filter_int[1]|max_length[1]'],
		'name'            => ['field' => 'name','label' => 'Name','rules' => 'required|max_length[64]|filter_input[64]'],
		'description'     => ['field' => 'description','label' => 'Description','rules' => 'max_length[255]|filter_input[255]'],

		'access'					=> ['field' => 'access','label' => 'Access','rules' => 'is_array'],
	];
	protected $rule_sets = [
		'insert' => 'created_on,created_by,updated_on,updated_by,is_editable,is_deletable,name,description,access',
		'update' => 'id,updated_on,updated_by,name,description,access',
	];

	public function insert($data, $skip_validation = false) {
		$this->flush_caches();

		if (isset($data[$this->primary_key])) {
			unset($data[$this->primary_key]);
		}

		/* return FALSE on failure data validated & filtered */
		$data = $this->validate($data,$skip_validation);

		$groups = $data['access'];

		if ($data !== FALSE) {
			unset($data['access']);

			/* passed by ref */
			parent::protect_attributes($data);

			$this->_database->insert($this->table, $data);

			$this->log_last_query();

			$insert_id = $this->_database->insert_id();

			/* ok now update the groups - if any */
			$this->o_role_access_model->save($insert_id,$groups);

			return (int) $insert_id;
		}

		return FALSE;
	}

	public function update($primary_value, $data, $skip_validation = false) {
		$this->flush_caches();

		/* return FALSE on failure data validated & filtered */
		$data = $this->validate($data,$skip_validation);

		$groups = $data['access'];

		if ($data !== FALSE) {
			unset($data['access']);

			/* passed by ref */
			parent::protect_attributes($data);

			$result = $this->_database->where($this->primary_key, $primary_value)->set($data)->update($this->table);

			$this->log_last_query();

			/* ok now update the groups - if any */
			$this->o_role_access_model->save($primary_value,$groups);

			return $result;
		}

		return FALSE;
	}

} /* end class */