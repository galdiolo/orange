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
class o_access_model extends Database_model {
	protected $table = 'orange_access';
	protected $rules = [
		'id'             => ['field' => 'id','label' => 'Id','rules' => 'required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'created_on'     => ['field' => 'created_on','label' => 'Created On','rules' => 'if_empty[now(Y-m-d H:i:s)]|required|max_length[24]|valid_datetime|filter_input[24]'],
		'created_by'     => ['field' => 'created_by','label' => 'Created By','rules' => 'if_empty[user()]|required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'updated_on'     => ['field' => 'updated_on','label' => 'Updated On','rules' => 'if_empty[now(Y-m-d H:i:s)]|required|max_length[24]|valid_datetime|filter_input[24]'],
		'updated_by'     => ['field' => 'updated_by','label' => 'Updated By','rules' => 'if_empty[user()]|required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'is_editable'    => ['field' => 'is_editable','label' => 'Editable','rules' => 'if_empty[1]|one_of[0,1]|filter_int[1]|max_length[1]'],
		'is_deletable'   => ['field' => 'is_deletable','label' => 'Deletable','rules' => 'if_empty[1]|one_of[0,1]|filter_int[1]|max_length[1]'],
		'name'           => ['field' => 'name','label' => 'Name','rules' => 'required|max_length[255]|filter_input[255]'],
		'description'    => ['field' => 'description','label' => 'Description','rules' => 'max_length[255]|filter_input[255]'],
		'group'          => ['field' => 'group','label' => 'Group','rules' => 'required|max_length[128]|filter_input[128]'],
		'key'            => ['field' => 'key','label' => 'Key','rules' => 'max_length[255]|filter_input[255]'],
	];
	protected $rule_sets = [
		'insert' => 'created_on,created_by,updated_on,updated_by,is_editable,is_deletable,name,description,group,key',
		'update' => 'id,updated_on,updated_by,name,description,group,key',
	];

	public function insert($data, $skip_validation = false) {
		$data['key'] = strtolower($data['group'].'::'.$data['name']);

		return parent::insert($data,$skip_validation);
	}

	public function update($primary_value, $data, $skip_validation = false) {
		$data['key'] = strtolower($data['group'].'::'.$data['name']);

		return parent::update($primary_value,$data,$skip_validation);
	}

	public function delete($id=null) {
		$this->o_role_access_model->delete_by_access_id($id);

		return parent::delete($id);
	}
	
	/*
	internal is often used by modules to store a identifier to the module which inserted the record
	this then in turn can be used to delete all access matching this identifier
	*/
	public function delete_by_internal($internal) {
		/* get all internal and manually remove any relationships in role->access */
		$records = $this->get_many_by('internal',$internal);

		foreach ($records as $record) {
			$this->o_role_access_model->delete_by_access_id($record->id);
		}

		return $this->delete_by('internal',$internal);
	}
	
	/* upsert used by the module install/upgrade */
	public function upsert($data) {
		$key = strtolower($data['group'].'::'.$data['name']);
	
		if ($this->exists('key',$key)) {
			/* update */
			$data['updated_on'] = date('Y-m-d H:i:s');
			$data['updated_by'] = ci()->user->id;

			$this->_database->where('key',$key)->set($data)->update($this->table);
		} else {
			/* insert */
			$data['created_on'] = date('Y-m-d H:i:s');
			$data['created_by'] = ci()->user->id;
			$data['key'] = $key;

			$this->_database->insert($this->table, $data);
		}
		
		$record = $this->_database->get_where($this->table,['key'=>$key])->result();
		
		return $record[0]->id;
	}

} /* end class */