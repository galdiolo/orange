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

class Module_install {
	protected $autoload_file;
	protected $onload_file;

	public $name;
	public $version = 0;
	public $info = '';
	public $type = '';
	public $install = false;
	public $uninstall = false;
	public $upgrade = true;
	public $remove = false;
	public $onload = false;
	public $autoload = false;
	public $required;
	public $requires_composer;
	public $table; /* used for remove_all() */
	public $routes = null;

	protected $root;
	protected $o_access_model;
	protected $o_menubar_model;
	protected $o_setting_model;
	protected $ci_load;
	protected $ci_wallet;
	protected $access_cud = ['Create'=>'Create a new','Update'=>'Update a','Delete'=>'Delete a'];
	protected $access_override = ['Admin Override Update'=>'Override is_editable on record','Admin Override Delete'=>'Override is_deletable on record'];

	public function __construct($name=null) {
		$this->root = ROOTPATH;

		$this->module = $name;

		$this->ci_load = &ci()->load;
		$this->ci_wallet = &ci()->wallet;

		$this->o_access_model = &ci()->o_access_model;
		$this->o_menubar_model = &ci()->o_menubar_model;
		$this->o_setting_model = &ci()->o_setting_model;
	}

	public function set_root($rootpath) {
		$this->root = $rootpath;
	}

	/* place holders */
	public function install_module($version=0) {
		return true;
	}

	public function uninstall_module($version=0) {
		return true;
	}

	public function remove_module($version=0) {
		return true;
	}

	public function upgrade_module($old_ver=0,$new_ver=0) {
		return true;
	}

	/* combo access + menu */
	public function add_access_n_menu($access,$access_description,$menu_text,$parent_menu_id,$url_to_controller,$module=null) {
		$module = ($module) ? $module : $this->name;

		$access_id = $this->add_access($access,$access_description,$module);

		/* add_menu($url,$text,$parent_id=1,$access_id=1,$sort=0,$class='',$color='000000',$icon='square',$module=null,$extra=[]) */
		$this->add_menu($url_to_controller,$menu_text,$parent_menu_id,$access_id,999);
	}

	/* for install.php use */
	public function add_access($name,$description,$module=null,$type=2,$extra=[]) {
		if (is_array($description)) {
			foreach ($description as $short=>$long) {
				$this->add_access($short.' '.$name,$long.' '.$name);
			}

			return;
		}

		$module = ($module) ? $module : $this->name;

		$defaults = ['is_editable'=>0,'is_deletable'=>0];
		$merged = array_merge($defaults,$extra);
		extract($merged);

		$data = [
			'is_editable'=>$is_editable, /* Lock down individual records */
			'is_deletable'=>$is_deletable, /* Lock down individual records */
			'name'=>$name,
			'description'=>$description,
			'type'=>$type, /* 0 user, 1 system, 2 module */
			'internal'=>$module,
			'group'=>$module,
		];

		/* special insert just for modules */
		return $this->o_access_model->upsert($data);
	}

	public function remove_access($module=null) {
		$module = ($module) ? $module : $this->name;

		return $this->o_access_model->delete_by_internal($module);
	}

	/* for install.php use */
	public function add_menu($url,$text,$parent_id=1,$access_id=1,$sort=0,$class='',$color=null,$icon=null,$module=null,$extra=[]) {
		$module = ($module) ? $module : $this->name;

		$defaults = ['is_editable'=>1,'is_deletable'=>0,'active'=>1];

		$merged = array_merge($defaults,$extra);

		if ($icon == null) {
			$icons = 'ship,user-secret,train,motorcycle,taxi,bus,bicycle,car,road,rocket,train,plane,ambulance';
			$icons = explode(',',$icons);
			shuffle($icons);
			$icon = array_shift($icons);
		}

		if ($color == null) {
			$color = substr(md5($module),1,6);
		}

		extract($merged);

		/* built in menus mapping */
		if (!is_integer($parent_id)) {
			$map = ['0','configure','users','content','modules','reports','help','utilities'];
			$parent_id = array_search(strtolower($parent_id),$map);
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
			'internal'=>$module,
		];

		return $this->o_menubar_model->insert($data,true);
	}

	public function remove_menu($module=null) {
		$module = ($module) ? $module : $this->name;

		return $this->o_menubar_model->delete_by('internal',$module);
	}

	/* for install.php use */
	public function add_setting($name,$value,$group,$help='',$type=0,$options='',$module=null,$extra=[]) {
		$module = ($module) ? $module : $this->name;

		$defaults = ['is_editable'=>1,'is_deletable'=>0,'enabled'=>1,'unmanaged'=>1];

		$merged = array_merge($defaults,$extra);

		extract($merged);

		$data = [
			'is_editable'=>$is_editable, /* Lock down individual records */
			'is_deletable'=>$is_deletable, /* Lock down individual records */
			'name'=>$name,
			'value'=>$value,
			'group'=>$group,
			'enabled'=>$enabled, /* not used at this time */
			'help'=>$help,
			'internal'=>$module,
			'unmanaged'=>$unmanaged, /* unmanaged setting */
			'show_as'=>$type, /* 0 textarea, 1 True/False (radios), 2 Radios (json format), 3 text input (option is length) */
			'options'=>$options,
		];

		return $this->o_setting_model->upsert($data);
	}

	public function remove_setting($module=null) {
		$module = ($module) ? $module : $this->name;

		return $this->o_setting_model->delete_by('internal',$module);
	}

	public function remove_all($module=null) {
		$module = ($module) ? $module : $this->name;

		$this->remove_access($module);
		$this->remove_menu($module);
		$this->remove_setting($module);
		$this->drop_table();
	}

	/* for install.php use */
	public function query($sql) {
		$success = false;

		if (!empty($sql)) {
			$db = $this->ci_load->database('default',true);

			$success = $db->query($sql);

			if (!$success) {
				log_message('error', $db->error());
			}
		}

		return $success;
	}

	public function create_table($sql=null) {
		$sql = ($sql) ? $sql : $this->table();

		return $this->query($sql);
	}

	public function drop_table($table=null) {
		$table = ($table) ? $table : $this->table;

		$table = explode(',',$table);

		foreach ((array)$table as $tablename) {
			if (!empty($tablename)) {
				$this->query("DROP TABLE IF EXISTS `".$tablename."`");
			}
		}

		return true;
	}

	/* $this->add_link('/themes/zerotype'); */
	public function add_link($asset) {
		$this->ci_load->helper('file');

		/* get module folder name */
		$child_folder = substr(get_called_class(),8);

		$asset = trim($asset,'/');

		$module_folder = $this->root.'/modules/'.$child_folder.'/public/'.$asset;
		$public_folder = $this->root.'/public/'.$asset;

		/* does the module exists */
		if (!realpath($module_folder)) {
			$this->ci_wallet->red('Couldn\'t find module file or folder <small>"'.$child_folder.'/public/'.$asset.'"</small>','/admin/configure/module');

			return false;
		}

		/* let's make the public path if it's not there */
		@mkdir(dirname($public_folder),0777,true);

		/* is the alias there?  */
		if (file_exists($public_folder)) {
			/* first remove it */
			unlink($public_folder);
		}

		$success = relative_symlink($module_folder,$public_folder);

		if (!$success) {
			$this->ci_wallet->red('Couldn\'t create Link ".../public/'.$asset.'".','/admin/configure/module');

			return false;
		}

		return true;
	}

	/* $this->remove_folder('themes/zerotype'); */
	public function remove_link($asset) {
		$asset = trim($asset,'/');
		$public = $this->root.'/public/'.$asset;

		return unlink($public);
	}

} /* end installer */