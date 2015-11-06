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

		ci()->load->dbforge();

		$this->o_packages_model = &ci()->o_packages_model;
		$this->package_migration_manager = &ci()->package_migration_manager;
		$this->package_requirements = &ci()->package_requirements;

		$this->prepare();
	}
	
	/*
	parse through the folder and setup the mega array with data
	based on json files.
	*/
	public function prepare() {
		include $this->autoload;

		$this->config_packages = $autoload['packages'];
	
		$packages = $this->rglob(ROOTPATH.'/packages','info.json');
		
		$this->_prepare($packages);

		$vendors = $this->rglob(ROOTPATH.'/vendor','info.json');

		$this->_prepare($vendors);
	}
	
	protected function _prepare($packages_info) {

		foreach ($packages_info as $info) {
			$json_config = $this->load_info_json($info);

			$full_path = $key = str_replace(ROOTPATH,'',dirname($info));
			$dir_name = basename(dirname($info));

			$db_config = $this->o_packages_model->read($key);

			$db_config['db_priority'] = $db_config['priority'];

			$starting_version = ($db_config['migration_version']) ? $db_config['migration_version'] : '0.0.0';

			$migration_files = $this->package_migration_manager->get_migrations_between($dir_name,$starting_version,$json_config['version']);

			$extra = [
				'full_path'=>$full_path,
				'folder'=>$dir_name,
				'migrations'=>$migration_files,
				'has_migrations'=>(count($migration_files) > 0),
				'is_active'=>isset($db_config['folder_name']),
				'version_check'=>$this->package_migration_manager->version_check($db_config['migration_version'],$json_config['version'])
			];

			/* combined what we have so far */
			$config = array_merge($json_config,$db_config,$extra);

			/* update packages that don't have migrations */
			if ($config['has_migrations'] == false && $config['version_check'] == 3) {
				/* no migrations - just update the veresion since the code is already up to date */
				$config['migration_version'] = $config['version'];
				$config['version_check'] = 2;

				$this->o_packages_model->write_new_version($config['folder'],$config['version']);
			}

			$config['url_name'] = bin2hex($config['full_path']);

			$this->packages[$key] = $config;
		}
		
		/* calculate the package requirements - passed by ref. */
		$this->package_requirements->process($this->packages);

		/*
		finally calculate the buttons & version display for the view
		Should this be done in the first loop?
		not sure since package_requirements requires the complete array to do it's logic checks?
		*/
	
		foreach ($this->packages as $key=>$config) {
			/* calc which buttons to show so it's not done in the view (where it doesn't belong) */
			if (!$config['has_errors']) {
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
		$this->o_packages_model->write($package,$config['version'],true,$config['priority']);

		$this->create_autoload();
		$this->create_onload();

		return true;
	}

	public function upgrade($package) {
		$config = $this->packages[$package];

		/* migrations up */
		$this->package_migration_manager->run_migrations($config,'up');

		$this->o_packages_model->write_new_version($package,$config['version']);
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
		$error = false;

		if (!file_exists($json_file)) {
			$error = true;
			$error_txt = '<i class="fa fa-exclamation-triangle"></i> info.json file not found';
		} else {
			$config = json_decode(file_get_contents($json_file),true);

			if ($config === null) {
				$error = true;
				$error_txt = '<i class="fa fa-exclamation-triangle"></i> info.json is not valid json';
			}
		}

		if ($error) {
			$config['json_error'] = true;
			$config['json_error_txt'] = $error_txt;
			$config['is_active'] = false;
		} else {
			$config['json_error'] = false;
			$config['json_error_txt'] = '';
			$config['type'] = (isset($config['type'])) ? $config['type'] : 'package';
			$config['json_priority'] = (!empty($config['priority'])) ? (int)$config['priority'] : $this->default_load_priority;
			$config['priority'] = (!empty($config['priority'])) ? (int)$config['priority'] : $this->default_load_priority;
		}

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
		return ci()->load->create_autoload();
	}
	
	protected function rglob($path='',$pattern='*',$flags=0) {
		$paths = glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
		$files = glob($path.$pattern, $flags);
		
		foreach ($paths as $path) {
			$files=array_merge($files,$this->rglob($path, $pattern, $flags));
		}
		
		return $files;
	}

} /* end class */