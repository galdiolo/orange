<?php

class package_manager {
	public $packages = [];
	public $config_header = "/*\nWARNING!\nThis file is directly modified by the framework\ndo not modify it unless you know what you are doing\n*/\n\n";
	public $config_packages;
	public $messages;
	public $default_load_priority = 50;

	public function __construct() {
		ci()->load->library(['migration','package/package_migration','package/package_migration_manager','package/package_requirements']);
		ci()->load->model('o_packages_model');

		ci()->load->dbforge();

		$this->prepare();
	}

	public function prepare() {
		$packages_folder = ROOTPATH.'/packages';
		$packages_folders = glob($packages_folder.'/*',GLOB_ONLYDIR);

		include ROOTPATH.'/application/config/autoload.php';

		$this->config_packages = $autoload['packages'];

		foreach ($packages_folders as $package) {
			$dir_name = basename($package);

			$json_config = $this->load_info_json($package);

			$db_config = ci()->o_packages_model->read($dir_name);

			$db_config['db_priority'] = $db_config['priority'];

			$starting_version = ($db_config['migration_version']) ? $db_config['migration_version'] : '0.0.0';

			$migration_files = ci()->package_migration_manager->get_migrations_between($dir_name,$starting_version,$json_config['version']);

			$extra = [
				'migrations'=>$migration_files,
				'has_migrations'=>(count($migration_files) > 0),
				'folder'=>$dir_name,
				'is_active'=>isset($db_config['folder_name']),
				'version_check'=>ci()->package_migration_manager->version_check($db_config['migration_version'],$json_config['version'])
			];

			/* combined what we have so far */
			$config = array_merge($json_config,$db_config,$extra);

			/* update packages that don't have migrations */
			if ($config['has_migrations'] == false && $config['version_check'] == 3) {
				/* no migrations - just update the veresion since the code is already up to date */
				$config['migration_version'] = $config['version'];
				$config['version_check'] = 2;

				ci()->o_packages_model->write_new_version($config['folder'],$config['version']);
			}

			$config['url_name'] = bin2hex($config['folder']);

			$this->packages[$dir_name] = $config;
		}

		ci()->package_requirements->process($this->packages);

		/*
		finally calculate the buttons & version display for the view
		Should this be done in the first loop?
		not sure since package_requirements requires the complete array to do it's logic checks?
		*/
	
		foreach ($this->packages as $key=>$config) {
			$config['is_required'] = (count((array)$config['required_error']) > 0);
			$errors = array_merge_recursive((array)$config['package_error'],(array)$config['composer_error']);
			$config['has_errors'] = (count($errors) > 0);

			/* calc which buttons to show so it's not done in the view (where it doesn't belong) */
			$config['button']['install'] = (!$config['is_active'] && !$config['json_error'] && !$config['has_errors']);
			$config['button']['upgrade'] = ($config['version_check'] == 3 && $config['is_active'] && !$config['json_error'] && !$config['has_errors']);
			$config['button']['uninstall'] = ($config['is_active'] && !$config['json_error'] && !$config['is_required']);
			$config['button']['delete'] = (!$config['is_active'] && !$config['json_error'] && !$config['is_required']);
			$config['button']['info'] = ($config['is_active'] && !$config['json_error'] && $config['is_required']);

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

		$msgs = false;

		if (!is_writable(ROOTPATH.'/application/config/autoload.php')) {
			$msgs[] = 'autoload config is not writable';
		}

		if (!is_writable(ROOTPATH.'/application/config/routes.php')) {
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
		ci()->package_migration_manager->run_migrations($config,'up');

		/* add to db */
		ci()->o_packages_model->write($config['version'],$package,true,$config['priority']);

		$this->packages_config();
		$this->create_onload();

		return true;
	}

	public function upgrade($package) {
		$config = $this->packages[$package];

		/* migrations up */
		ci()->package_migration_manager->run_migrations($config,'up');

		ci()->o_packages_model->write_new_version($package,$config['version']);
		ci()->o_packages_model->write_new_priority($package,$config['priority'],null,false);

		$this->packages_config();
		$this->create_onload();

		return true;
	}

	public function uninstall($package) {
		$config = $this->packages[$package];

		/* migrations down */
		ci()->package_migration_manager->run_migrations($config,'down');

		/* deactive package autoload */
		ci()->o_packages_model->activate($package,false);

		$this->packages_config();
		$this->create_onload();

		return true;
	}

	public function delete($package) {
		/* delete the entire folder */
		ci()->load->helper('directory');

		ci()->o_packages_model->remove($package);

		$path = ROOTPATH.'/packages/'.$package;

		$this->packages_config();
		$this->create_onload();

		return rmdirr($path);
	}

	public function refresh_package_priority() {
		/* update the database records first to reflect the info.json file */
		foreach ($this->packages as $folder_name=>$record) {
			if (!empty($record['priority'])) {
				ci()->o_packages_model->write_package_priority($folder_name,$record['priority']);
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

				ci()->o_packages_model->write_package_overridden($folder_name,$override);
			}
		}

		/* reload */
		$this->prepare();
	}

	/* wrapper */
	public function write_new_priority($folder_name,$priority,$overridden=1,$force=false) {
		return ci()->o_packages_model->write_new_priority($folder_name,$priority,$overridden,$force);
	}

	public function load_info_json($folder) {
		$json_file = $folder.'/info.json';

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
				ci()->o_packages_model->write_new_priority($folder_name,$record['json_priority'],0,true);
				ci()->o_packages_model->write_package_overridden($folder_name,0);
			}
		}

		/* reload */
		$this->prepare();
	}

	/* wrapper for loader function */
	public function create_onload() {
		ci()->load->create_onload();
	}

	/* write autoload.php */
	public function packages_config() {
		$filepath = ROOTPATH.'/application/config/autoload.php';

		$autoload_packages = ci()->o_packages_model->active();

		$package_text = '$autoload[\'packages\'] = array('.chr(10);

		$package_text .= chr(9).'/* updated: '.date('Y-m-d-H:i:s').' */'.chr(10);

		// 	ROOTPATH.'/packages/theme_zerotype',
		foreach ($autoload_packages as $ap) {
			/* let's make sure the packages is still there! */
			if (is_dir(ROOTPATH.'/packages/'.$ap->folder_name)) {
				$package_text .= chr(9).'ROOTPATH.\'/packages/'.$ap->folder_name."',".chr(10);
			}
		}

		$package_text .= ');';

		$current_content = file_get_contents($filepath);

		$re = "/^\\s*\\\$autoload\\['packages']\\s*=\\s*array\\s*\\((.+?)\\);/ms";

		preg_match_all($re,$current_content,$matches);

		if (!isset($matches[0][0])) {
			show_error('Regular Expression Error: packages_config->autoload.php');
		}

		$content = str_replace($matches[0][0],$package_text,$current_content);

		return atomic_file_put_contents($filepath,$content);
	}

	/* change route file */
	public function route_config($from,$to,$mode) {
		$filepath = ROOTPATH.'/application/config/routes.php';

		require $filepath;

		/* remove it if it's already there */
		foreach ($route as $key=>$val) {
			if ($key == $from && $val == $to) {
				unset($route[$key]);
			}
		}

		/* add mode it? */
		if ($mode == 'add' && !isset($route[$from])) {
			$route[$from] = $to;
		}

		return file_put_contents($filepath,'<?php '.chr(10).$this->config_header.'$route = '.var_export($route,true).';');
	}

} /* end class */