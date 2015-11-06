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
		ci()->load->library(['migration','package/package_migration','package/package_migration_manager','package/package_requirements']);
		ci()->load->model('o_packages_model');

		/* load for migrations */
		ci()->load->dbforge();

		$this->o_packages_model = &ci()->o_packages_model;
		$this->package_migration_manager = &ci()->package_migration_manager;
		$this->package_requirements = &ci()->package_requirements;

		$this->prepare();

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
			$this->_prepare($packages);
		}

		$vendors = $this->rglob(ROOTPATH.'/vendor','composer.json');

		if (count($vendors)) {
			$this->_prepare($vendors);
		}
	}

	protected function _prepare($packages_info) {
		foreach ($packages_info as $info) {
			$composer_config = $this->load_info_json($info);

			if (is_array($composer_config)) {
				$key = trim(str_replace(ROOTPATH,'',dirname($info)));

				$db_config = $this->o_packages_model->read($key);

				$extra = [
					'db_priority'=>$db_config['priority'],
					'full_path'=>$key,
					'human'=>str_replace('/',' ',$key),
					'is_active'=>(($db_config['is_active'] == 1) ? true : false),
					'version_check'=>$this->package_migration_manager->version_check($db_config['migration_version'],$composer_config['composer_version']),
					'url_name'=>bin2hex($key),
				];

				$this->packages[$key] = array_merge((array)$composer_config,(array)$db_config,(array)$extra);
			}
		}

		/* calculate the package requirements - passed by ref. */
		$this->package_requirements->process($this->packages);

		$this->figure_migrations();

		$this->figure_buttons();

		/* check out folders */
		$msgs = false;

		if (!is_writable($this->autoload)) {
			$msgs[] = 'autoload config is not writable';
		}

		if (!is_writable($this->routes)) {
			$msgs[] = 'routes config is not writable';
		}

		$this->messages = ($msgs === false) ? false : implode('<br>',$msgs);
	}

	protected function figure_migrations() {
		foreach ($this->packages as $key=>$config) {
			/* starting version for migrations */
			$starting_version = ($config['migration_version']) ? $config['migration_version'] : '0.0.0';

			$migration_files = $this->package_migration_manager->get_migrations_between($key,$starting_version,$composer_config['composer_version']);

			$this->packages[$key]['migrations'] = $migration_files;
			$this->packages[$key]['has_migrations'] = (count($migration_files) > 0);

			/* update packages that don't have migrations */
			if ($this->packages[$key]['has_migrations'] == false && $this->packages[$key]['version_check'] == 3) {
				/* no migrations - just update the veresion since the code is already up to date */
				$this->packages[$key]['migration_version'] = $this->packages[$key]['composer_version'];
				$this->packages[$key]['version_check'] = 2;

				$this->o_packages_model->write_new_version($this->packages[$key]['full_path'],$this->packages[$key]['composer_version']);
			}
		}
	}

	protected function figure_buttons() {
		/*
		finally calculate the buttons & version display for the view
		Should this be done in the first loop?
		not sure since package_requirements requires the complete array to do it's logic checks?
		*/

		foreach ($this->packages as $key=>$config) {
			/* calc which buttons to show so it's not done in the view (where it doesn't belong) */
			if (!$config['has_error']) {
				$config['button']['install'] = (!$config['is_active']);
				$config['button']['upgrade'] = ($config['version_check'] == 3 && $config['is_active']);
				$config['button']['uninstall'] = ($config['is_active'] && !$config['is_required']);

				if ($this->allow_delete) {
					$config['button']['delete'] = (!$config['is_active'] && !$config['is_required']);
				} else {
					$config['button']['delete'] = false;
				}
			} else {
				$config['button']['info'] = true;
			}

			/* calc version display */
			$config['version_display'] = 0;

			if (!$config['json_error']) {
				if ($config['is_active']) {
					switch ($config['version_check']) {
						case 1: /* less than */
							$config['version_display'] = 4;
							$config['show_version'] = true;
						break;
						case 2:
							$config['version_display'] = 2;
							/* version in db matches migration version */
						break;
						case 3: /* greater than */
							$config['version_display'] = 3;
							$config['uninstall'] = false;
							$config['upgrade'] = true;
						break;
						default:
							$config['version_display'] = 4;
					}
				}
			}

			/* ok now put this in the array as well! */
			$this->packages[$key] = $config;
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
		$this->package_migration_manager->run_migrations($config,'up');

		/* add to db */
		$this->o_packages_model->write($package,$config['composer_version'],true,$config['priority']);

		$this->create_autoload();
		$this->create_onload();

		return true;
	}

	public function upgrade($package) {
		$config = $this->packages[$package];

		/* migrations up */
		$this->package_migration_manager->run_migrations($config,'up');

		$this->o_packages_model->write_new_version($package,$config['composer_version']);
		$this->o_packages_model->write_new_priority($package,$config['priority'],null,false);

		$this->create_autoload();
		$this->create_onload();

		return true;
	}

	public function uninstall($package) {
		$config = $this->packages[$package];

		/* migrations down */
		$this->package_migration_manager->run_migrations($config,'down');

		/* deactive package autoload */
		$this->o_packages_model->activate($package,false);

		$this->create_autoload();
		$this->create_onload();

		return true;
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

	public function refresh_package_priority() {
		/* update the database records first to reflect the info.json file */
		foreach ($this->packages as $folder_name=>$record) {
			if (!empty($record['priority'])) {
				$this->o_packages_model->write_package_priority($folder_name,$record['priority']);
			}
		}

		/* reload */
		$this->prepare();

		/* now double check the override - if the json and current value match then it's not overridden so turn it off */
		foreach ($this->packages as $folder_name=>$record) {
			$jp = (int)$record['json_priority'];
			$dp = (int)$record['db_priority'];

			if ($jp > 0 && $dp > 0) {
				$override = ($jp == $dp) ? 0 : 1;

				$this->o_packages_model->write_package_overridden($folder_name,$override);
			}
		}

		/* reload */
		$this->prepare();
	}

	/* wrapper */
	public function write_new_priority($folder_name,$priority,$overridden=1,$force=false) {
		return $this->o_packages_model->write_new_priority($folder_name,$priority,$overridden,$force);
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

	/* do a complete reset on load order */
	public function reset_priorities() {
		/* update the database records first to reflect the info.json file */
		foreach ($this->packages as $folder_name=>$record) {
			if (!empty($record['json_priority'])) {
				$this->o_packages_model->write_new_priority($folder_name,$record['json_priority'],0,true);
				$this->o_packages_model->write_package_overridden($folder_name,0);
			}
		}

		/* reload */
		$this->prepare();
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