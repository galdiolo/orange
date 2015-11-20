<?php

class package_migration {
	public $config;
	public $name;
	public $internal;
	protected $o_access_model;
	protected $o_menubar_model;
	protected $o_setting_model;
	protected $access_cud = ['Create'=>'Create a new','Update'=>'Update a','Delete'=>'Delete a'];
	protected $access_override = ['Admin Override Update'=>'Override is_editable on record','Admin Override Delete'=>'Override is_deletable on record'];

	public function __construct($config) {
		/* models may not be loaded in CLI so load it now */
		ci()->load->model(['o_menubar_model','o_access_model']);

		$this->config = $config;
		$this->name = $config['name'];
		$this->internal = $config['internal'];

		$this->o_access_model = &ci()->o_access_model;
		$this->o_menubar_model = &ci()->o_menubar_model;
		$this->o_setting_model = &ci()->o_setting_model;
	}

	public function cli_output($output) {
		if (is_cli()) {
			echo $output.chr(10);
		}
	}

	public function up(){
		return true;
	}

	public function down(){
		return true;
	}

	public function add_menu_crud() {

	}

	public function add_menu($data=[]) {
		$defaults = [
			'is_editable'=>1,
			'is_deletable'=>0,
			'url'=>'',
			'text'=>'',
			'access_id'=>1,
			'parent_id'=>1,
			'sort'=>0,
			'target'=>'',
			'class'=>'',
			'active'=>1,
			'color'=>null,
			'icon'=>null,
			'internal'=>$this->internal,
		];

		$data = array_diff_key($defaults,$data) + array_intersect_key($data,$defaults);

		/* if they didn't send in a icon randomly pick one */
		if ($data['icon'] == null) {
			$icons = ['arrows','arrows-alt','arrow-left','arrow-right','arrow-up','arrow-down','arrows-h','arrows-v'];
			shuffle($icons);
			$data['icon'] = end($icons);
		}

		/* if they didn't send in a color randomly pick one */
		if ($data['color'] == null) {
			$data['color'] = substr(md5($this->internal),1,6);
		}

		/* parent menu id search */
		if (!is_integer($data['parent_id'])) {
			/* ok they need to specify internal:text */
			list($internal,$text) = explode(':',$parent_id);

			$row = $this->o_menubar_model->get_by(['internal'=>$internal,'text'=>$text]);

			$data['parent_id'] = (isset($row->id)) ? $row->id : 0; /* root level */
		}

		/* access id search */
		if (!is_integer($data['access_id'])) {
			$row = $this->o_access_model->get_by(['key'=>$data['access_id']]);

			$data['access_id'] = (isset($row->id)) ? $row->id : 2; /* everyone logged in */
		}

		return $this->o_menubar_model->insert($data,true);
	}

	public function remove_menu($package=null) {
		$internal = ($package) ? $package : $this->internal;

		return $this->o_menubar_model->delete_by('internal',$internal);
	}

	public function add_access($data) {
		$defaults = [
			'is_editable'=>0,
			'is_deletable'=>0,
			'name'=>'',
			'group'=>$this->name,
			/* key is auto filled in */
			'description'=>$data['name'],
			'type'=>2,
			'internal'=>$this->internal,
		];

		$data = array_diff_key($defaults,$data) + array_intersect_key($data,$defaults);

		/* special insert just for packages */
		return $this->o_access_model->upsert($data);
	}

	public function remove_access($package=null) {
		$internal = ($package) ? $package : $this->internal;

		return $this->o_access_model->delete_by('internal',$internal);
	}

	public function add_setting($data) {
		$defaults = [
			'is_editable'=>1,
			'is_deletable'=>0,
			'name'=>'',
			'value'=>'',
			'group'=>$this->name,
			'enabled'=>1,
			'help'=>'',
			'internal'=>$this->internal,
			'managed'=>1,
			'show_as'=>0, /* 0 Textarea, 1 Boolean T/F, 2 Radios (json), 3 Text Input (option width) */
			'options'=>'', /* Radio {'name': 'value', 'name2': 'value2'}, text width */ 
		];

		$data = array_diff_key($defaults,$data) + array_intersect_key($data,$defaults);

		return $this->o_setting_model->upsert($data);
	}

	public function remove_setting($package=null) {
		$internal = ($package) ? $package : $this->internal;

		return $this->o_setting_model->delete_by('internal',$internal);
	}

	public function add_route($from,$to) {
		return ci()->package_manager->route_config($from,$to,'add');
	}

	public function remove_route($from,$to) {
		return ci()->package_manager->route_config($from,$to,'remove');
	}

	public function add_symlink($asset) {
		ci()->load->helper('file');

		$asset = trim($asset,'/');

		if (!$package_folder = $this->_find_package($asset)) {
			ci()->wallet->red('Couldn\'t find package folder "'.$this->internal.'/public/'.$asset.'".','/admin/configure/packages');

			return false;
		}

		$public_folder = ROOTPATH.'/public/'.$asset;

		/* let's make the public path if it's not there */
		@mkdir(dirname($public_folder),0777,true);

		/* remove the link/file if it's there */
		$this->remove_symlink($asset);

		if (!relative_symlink($package_folder,$public_folder)) {
			ci()->wallet->red('Couldn\'t create Link "'.$this->internal.'::'.$asset.'".','/admin/configure/packages');

			return false;
		}

		return true;
	}

	public function remove_symlink($asset) {
		$asset = trim($asset,'/');

		$public_folder = ROOTPATH.'/public/'.$asset;

		return (file_exists($public_folder)) ? unlink($public_folder) : true;
	}

	protected function _find_package($path) {
		list($type,$name) = explode('/',$this->internal,2);

		return ($type == 'package') ? ROOTPATH.'/'.$this->internal.'/public/'.$path : ROOTPATH.'/vendor/'.$this->internal.'/public/'.$path;
	}

	public function query($sql,$database_config='default') {
		$success = false;

		if (!empty($sql)) {
			$db = ci()->load->database($database_config,true);

			$success = $db->query($sql);

			if (!$success) {
				log_message('error', $db->error());
			}
		}

		list($func) = strtolower(explode(' ',$sql,1));

		switch($func) {
			case 'select':
				$success = $success->row_array();
			break;
			case 'insert':
				$success = $db->insert_id();
			break;
			case 'update':
				$success = $db->affected_rows();
			break;
			case 'delete':
				$success = $db->affected_rows();
			break;
		}

		return $success;
	}

	public function drop_table($tablename) {
		return $this->query('DROP TABLE IF EXISTS `'.$tablename.'`');
	}

	public function describe_table($tablename,$database_config='default') {
		$db = ci()->load->database($database_config,true);

		$table_exists = $db->table_exists($table_name);

		if ($table_exists) {
			$fields = (array)$db->list_fields($tablename);
		} else {
			/* if the table doesn't exist return a empty array */
			$fields = [];
		}

		return $fields;
	}

	public function insert($model_name,$data,$validate=true) {
		$this->load->model($model_name);

		return $this->$model_name->insert($data,$validate);
	}

} /* end class */