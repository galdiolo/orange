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
class o_setting_model extends Database_model {
	protected $table = 'orange_settings';
	protected $rules = [
		'id'             => ['field' => 'id','label' => 'Id','rules' => 'required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'created_on'     => ['field' => 'created_on','label' => 'Created On','rules' => 'if_empty[now(Y-m-d H:i:s)]|required|max_length[24]|valid_datetime|filter_input[24]'],
		'created_by'     => ['field' => 'created_by','label' => 'Created By','rules' => 'if_empty[user()]|required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'updated_on'     => ['field' => 'updated_on','label' => 'Updated On','rules' => 'if_empty[now(Y-m-d H:i:s)]|required|max_length[24]|valid_datetime|filter_input[24]'],
		'updated_by'     => ['field' => 'updated_by','label' => 'Updated By','rules' => 'if_empty[user()]|required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'is_editable'    => ['field' => 'is_editable','label' => 'Editable','rules' => 'if_empty[1]|one_of[0,1]|filter_int[1]|max_length[1]'],
		'is_deletable'   => ['field' => 'is_deletable','label' => 'Deletable','rules' => 'if_empty[0]|one_of[0,1]|filter_int[1]|max_length[1]'],
		'name'           => ['field' => 'name','label' => 'Name','rules' => 'required|max_length[64]|filter_input[64]|trim'],
		'value'          => ['field' => 'value','label' => 'Value','rules' => 'max_length[16384]|filter_textarea[16384]'],
		'group'          => ['field' => 'group','label' => 'Group','rules' => 'required|strtolower|max_length[64]|filter_input[64]'],
		'enabled'        => ['field' => 'enabled','label' => 'Enabled','rules' => 'if_empty[1]|one_of[0,1]|filter_int[1]|max_length[1]|less_than[2]'],
		'help'           => ['field' => 'help','label' => 'Help','rules' => 'max_length[255]|filter_input[255]'],
		'internal'       => ['field' => 'internal','label' => 'Internal','rules' => 'max_length[64]|filter_input[64]'],
		'managed'        => ['field' => 'managed','label' => 'Manged','rules' => 'if_empty[0]|one_of[0,1]|filter_int[1]|max_length[1]'],
		'show_as'				 => ['field' => 'show_as','label' => 'Show As', 'rules' => 'if_empty[0]|filter_int[1]'],
		'options'        => ['field' => 'options','label' => 'Options','rules' => 'max_length[16384]|filter_textarea[16384]'],
	];
	protected $rule_sets = [
		'insert' => 'created_on,created_by,updated_on,updated_by,is_editable,is_deletable,name,value,group,enabled,help,internal,managed,show_as,options,',
		'update' => 'id,updated_on,updated_by,name,value,group,enabled,help,internal,managed,show_as,options,is_deletable',
		'update_value' => 'value',
	];

	/* does this compound key exist? */
	public function compound_key_exists($name,$group) {
		$result = $this->_database->where(['name'=>$name,'group'=>$group])->get($this->table)->result();

		return (count($result) > 0);
	}

	/* special upsert just for packages */
	public function upsert($data) {
		$result = $this->_database->where(['name'=>$data['name'],'group'=>$data['group']])->get($this->table)->result();

		if (count($result) > 0) {
			/* update */
			$data['updated_on'] = date('Y-m-d H:i:s');
			$data['updated_by'] = ci()->user->id;

			return $this->_database->where('id',$result[0]->id)->set($data)->update($this->table);
		} else {
			/* insert */
			$data['created_on'] = date('Y-m-d H:i:s');
			$data['created_by'] = ci()->user->id;

			return $this->_database->insert($this->table, $data);
		}
	}
	
	/* special deleter since local array cache is faster than anything else out there */
	public function flush_caches() {
		log_message('debug', 'Local Array Cache Flush: settings.php');

		unlink(ROOTPATH.'/var/local_file_cache/settings.php');

		return $this;
	}

} /* end class */