<?php

class package_manager {
	public $packages = [];
	public $config_header = "/*\nWARNING!\nThis file is directly modified by the framework\ndo not modify it unless you know what you are doing\n*/\n\n";
	public $config_packages;
	public $messages;
	public $default_load_priority = 50;
	public $o_packages_model;
	public $package_migration_manager;
	public $package_requirements;
	public $autoload = ROOTPATH.'/application/config/autoload.php';
	public $routes = ROOTPATH.'/application/config/routes.php';
	public $allow_delete = false;

	public function __construct() {
		ci()->load->library(['migration','package/package_migration','package/package_migration_manager','package/package_helper']);
		ci()->load->model('o_packages_model');

		/* load for migrations */
		ci()->load->dbforge();

		$this->o_packages_model = &ci()->o_packages_model;
		$this->package_migration_manager = &ci()->package_migration_manager;
		$this->package_helper = &ci()->package_helper;

		$this->prepare();

		/* check out folders */
		$msgs = false;

		if (!is_writable($this->autoload)) {
			$msgs[] = 'autoload config is not writable';
		}

		if (!is_writable($this->routes)) {
			$msgs[] = 'routes config is not writable';
		}

		$this->messages = ($msgs === false) ? false : implode('<br>',$msgs);

		/* uncomment if you need to fill your database with all packages */
		//$this->init_fill_db();
	}

	/*
	parse through the folder and setup the mega array with data
	based on json files.
	*/
	public function prepare() {
		include $this->autoload;

		$this->config_packages = $autoload['packages'];

		$packages = $this->rglob(ROOTPATH.'/packages','composer.json');

		/* did we even get any? */
		if (count($packages)) {
			$this->_prepare($packages,'framework');
		}

		$vendors = $this->rglob(ROOTPATH.'/vendor','composer.json');

		if (count($vendors)) {
			$this->_prepare($vendors,'composer');
		}

		/* calculate the package requirements - passed by ref. */
		$this->package_helper->requirements($this->packages);

		$this->package_helper->migrations($this->packages);

		$this->package_helper->buttons($this->packages);
	}

	protected function _prepare($packages_info,$type_of_package) {
		foreach ($packages_info as $info) {
			$composer_config = $this->load_info_json($info);

			if (is_array($composer_config)) {
				$key = trim(str_replace(ROOTPATH,'',dirname($info)));

				$db_config = $this->o_packages_model->read($key);

				$cr = $composer_config['composer_priority'];

				if ($cr >= 0 && $cr <= 20) {
					$human_priority = 'highest';
				} elseif ($cr >= 21 && $cr <= 40) {
					$human_priority = 'high';
				} elseif ($cr >= 41 && $cr <= 60) {
					$human_priority = 'med';
				} elseif ($cr >= 61 && $cr <= 80) {
					$human_priority = 'low';
				} elseif ($cr >= 81 && $cr <= 100) {
					$human_priority = 'lowest';
				}
				
				$extra = [
					'name'=>trim(str_replace('/',' ',$key)),
					'composer_human_priority'=>$human_priority,
					'type_of_package'=>$type_of_package,
					'db_priority'=>$db_config['priority'],
					'full_path'=>$key,
					'human'=>str_replace('/',' ',$key),
					'is_active'=>(($db_config['is_active']) ? true : false),
					'version_check'=>$this->package_migration_manager->version_check($db_config['migration_version'],$composer_config['composer_version']),
					'url_name'=>bin2hex($key),
					'composer_name'=>$composer_config['name'],
				];

				$this->packages[$key] = array_merge((array)$composer_config,(array)$db_config,(array)$extra);
			}
		}
	}

	public function records() {
		return $this->packages;
	}

	public function record($package) {
		return $this->packages[$package];
	}

	public function install_or_upgrade($package) {
		return ($this->packages[$package]['is_active']) ? $this->upgrade($package) : $this->install($package);
	}

	public function install($package) {
		$config = $this->packages[$package];

		/* migrations up */
		if ($success = $this->package_migration_manager->run_migrations($config,'up')) {
	
			/* add to db */
			$this->o_packages_model->write($package,$config['composer_version'],true,$config['priority']);
	
			$this->create_autoload();
			$this->create_onload();
		}

		return $success;
	}

	public function upgrade($package) {
		$config = $this->packages[$package];

		/* migrations up */
		if ($success = $this->package_migration_manager->run_migrations($config,'up')) {
	
			$this->o_packages_model->write_new_version($package,$config['composer_version']);
			$this->o_packages_model->write_new_priority($package,$config['priority'],null,false);
	
			$this->create_autoload();
			$this->create_onload();
		}

		return $success;
	}

	public function uninstall($package) {
		$config = $this->packages[$package];

		/* migrations down */
		if ($success = $this->package_migration_manager->run_migrations($config,'down')) {
			$this->o_packages_model->activate($package,false);
	
			$this->create_autoload();
			$this->create_onload();
		}

		return $success;
	}

	public function delete($package) {
		if (!$this->allow_delete) {
			show_error('Delete not allowed.');
		}

		/* delete the entire folder */
		ci()->load->helper('directory');

		$this->o_packages_model->remove($package);

		$path = ROOTPATH.'/packages/'.$package;

		$this->create_autoload();
		$this->create_onload();

		return rmdirr($path);
	}

	public function load_info_json($json_file) {
		$config = json_decode(file_get_contents($json_file),true);

		/* error decoding json */
		if ($config === null || !isset($config['orange'])) {
			return false;
		}

		/* from orange */
		$config['type'] = (isset($config['orange']['type'])) ? $config['orange']['type'] : 'package';
		$config['priority'] = (!empty($config['orange']['priority'])) ? (int)$config['orange']['priority'] : $this->default_load_priority;

		$config['composer_priority'] = (!empty($config['orange']['priority'])) ? (int)$config['orange']['priority'] : $this->default_load_priority;
		$config['composer_version'] = (!empty($config['orange']['version'])) ? $config['orange']['version'] : '?';

		return $config;
	}

	/* wrapper for loader function */
	public function create_onload() {
		return ci()->load->create_onload();
	}

	/* write autoload.php */
	public function create_autoload() {
		$autoload_packages = $this->o_packages_model->active();

		$package_text = '$autoload[\'packages\'] = array('.chr(10);

		$package_text .= chr(9).'/* updated: '.date('Y-m-d-H:i:s').' */'.chr(10);

		// 	ROOTPATH.'/packages/theme_zerotype',
		foreach ($autoload_packages as $ap) {
			/* let's make sure the packages is still there! */
			if (is_dir(ROOTPATH.$ap->full_path)) {
				$package_text .= chr(9).'ROOTPATH.\''.$ap->full_path."',".chr(10);
			}
		}

		$package_text .= ');';

		$current_content = file_get_contents($this->autoload);

		$re = "/^\\s*\\\$autoload\\['packages']\\s*=\\s*array\\s*\\((.+?)\\);/ms";

		preg_match_all($re,$current_content,$matches);

		if (!isset($matches[0][0])) {
			show_error('Regular Expression Error: packages_config->autoload.php');
		}

		$content = str_replace($matches[0][0],$package_text,$current_content);

		return atomic_file_put_contents($this->autoload,$content);
	}

	/* change route file */
	public function route_config($from,$to,$mode) {
		require $this->routes;

		/* remove it if it's already there */
		foreach ($route as $key=>$val) {
			if ($key == $from && $val == $to) {
				unset($route[$key]);
			}
		}

		/* add mode? */
		if ($mode == 'add' && !isset($route[$from])) {
			$route[$from] = $to;
		}

		return file_put_contents($this->routes,'<?php '.chr(10).$this->config_header.'$route = '.var_export($route,true).';');
	}

	protected function rglob($path='',$pattern='*',$flags=0) {
		$paths = glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
		$files = glob($path.$pattern, $flags);

		foreach ($paths as $path) {
			$files = array_merge($files,$this->rglob($path, $pattern, $flags));
		}

		return $files;
	}

	protected function init_fill_db() {
		foreach ($this->packages as $p) {
			$this->o_packages_model->write($p['full_path'],$p['composer_version'],true,$p['composer_priority']);
		}
	}

} /* end class */