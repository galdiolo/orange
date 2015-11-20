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
		$this->config = $config;
		$this->name = $config['name'];
		$this->internal = $config['internal'];

		$this->o_access_model = &ci()->o_access_model;
		$this->o_menubar_model = &ci()->o_menubar_model;
		$this->o_setting_model = &ci()->o_setting_model;
	}

	public function up(){
		return true;
	}

	public function down(){
		return true;
	}

	public function add_menu($data=[]) {
		$package = ($package) ? $package : $this->name;
		$internal = (isset($data['internal'])) ? $data['internal'] : $this->internal;

		$defaults = ['target'=>'','internal'=>$internal,'url'=>'','text'=>'','parent_id'=>1,'access_id'=>1,'sort'=>0,'class'=>'','color'=>null,'icon'=>null,'package'=>$package,'is_editable'=>1,'is_deletable'=>0,'active'=>1];

		extract(array_diff_key($defaults,$data) + array_intersect_key($data,$defaults),EXTR_OVERWRITE);

		if ($icon == null) {
			$icons = ['arrows','arrows-alt','arrow-left','arrow-right','arrow-up','arrow-down','arrows-h','arrows-v'];
			shuffle($icons);
			$icon = array_shift($icons);
		}

		if ($color == null) {
			$color = substr(md5($package),1,6);
		}

		/* built in menus mapping */
		if (!is_integer($parent_id)) {
			$built_in_menus_map = ['0','configure','users','content','packages','reports','help','utilities'];
			$parent_id = array_search(strtolower($parent_id),$built_in_menus_map);
		}

		$data = [
			'access_id'=>$access_id,
			'is_editable'=>$is_editable, /* Lock down individual records */
			'is_deletable'=>$is_deletable, /* Lock down individual records */
			'url'=>$url,
			'text'=>$text,
			'parent_id'=>$parent_id,
			'sort'=>$sort,
			'class'=>$class,
			'active'=>$active,
			'color'=>$color,
			'icon'=>$icon,
			'internal'=>$internal,
			'target'=>$target,
		];

		return $this->o_menubar_model->insert($data,true);
	}

	public function remove_menu($package=null) {
		$internal = ($package) ? $package : $this->internal;

		return $this->o_menubar_model->delete_by('internal',$internal);
	}

	public function add_access($data) {
		$package = ($data['package']) ? $data['package'] : $this->name;
		$internal = (isset($data['internal'])) ? $data['internal'] : $this->internal;

		$defaults = ['internal'=>$internal,'name'=>'','description'=>'','package'=>$package,'type'=>2,'is_editable'=>0,'is_deletable'=>0];

		extract(array_diff_key($defaults,$data) + array_intersect_key($data,$defaults));

		/* if they only sent in the name use that for the description */
		$description = (!empty($description)) ? $description : $name;

		$data = [
			'is_editable'=>$is_editable, /* Lock down individual records */
			'is_deletable'=>$is_deletable, /* Lock down individual records */
			'name'=>$name,
			'description'=>$description,
			'type'=>$type, /* 0 user, 1 system, 2 package */
			'internal'=>$internal,
			'group'=>$package,
		];

		/* special insert just for packages */
		return $this->o_access_model->upsert($data);
	}

	public function remove_access($package=null) {
		$internal = ($package) ? $package : $this->internal;

		return $this->o_access_model->delete_by('internal',$internal);
	}

	public function add_setting($data) {
		$package = ($data['package']) ? $data['package'] : $this->name;
		$internal = (isset($data['internal'])) ? $data['internal'] : $this->internal;

		$defaults = ['internal'=>$internal,'name'=>'','value'=>'','group'=>$package,'help'=>'','type'=>0,'options'=>'','package'=>$package,'is_editable'=>1,'is_deletable'=>0,'enabled'=>1,'managed'=>1];

		$merged = array_merge($defaults,$data);

		extract($merged);

		$data = [
			'is_editable'=>$is_editable, /* Lock down individual records */
			'is_deletable'=>$is_deletable, /* Lock down individual records */
			'name'=>$name,
			'value'=>$value,
			'group'=>$group,
			'enabled'=>$enabled, /* not used at this time */
			'help'=>$help,
			'internal'=>$internal,
			'managed'=>$managed, /* managed setting */
			'show_as'=>$type, /* 0 textarea, 1 True/False (radios), 2 Radios (json format), 3 text input (option is length) */
			'options'=>$options,
		];

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
		list($type,$name) = explode('/',$this->package,2);

		return ($type == 'package') ? ROOTPATH.'/'.$this->package.'/public/'.$path : ROOTPATH.'/vendor/'.$this->package.'/public/'.$path;
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
		return $this->query("DROP TABLE IF EXISTS `".$tablename."`");
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

} /* end class */