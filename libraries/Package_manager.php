<?php

class package_manager {
	public $packages = [];
	public $config_header = "/*\nWARNING!\nThis file is directly modified by the framework\ndo not modify it unless you know what you are doing\n*/\n\n";
	public $config_packages;
	public $model;
	public $migration_manager;
	public $messages;

	public function __construct() {
		ci()->load->library(['package/package_migration','package/package_migration_manager']);
		ci()->load->model('o_packages_model');

		$this->model = ci()->o_packages_model;
		$this->migration_manager = ci()->package_migration_manager;

		$this->prepare();
	}

	public function prepare() {
		$packages_folder = ROOTPATH.'/packages';
		$packages_folders = glob($packages_folder.'/*',GLOB_ONLYDIR);

		$filepath = ROOTPATH.'/application/config/autoload.php';

		include $filepath;

		$this->config_packages = $autoload['packages'];

		foreach ($packages_folders as $package) {
			$dir_name = basename($package);

			$json_config = $this->load_info_json($package);

			$db_config = $this->model->read($dir_name);

			$starting_version = ($db_config['migration_version']) ? $db_config['migration_version'] : '0.0.0';

			$migration_files = $this->migration_manager->get_migrations_between($dir_name,$starting_version,$json_config['version']);

			$extra = [
				'migrations'=>$migration_files,
				'has_migrations'=>(count($migration_files) > 0),
				'folder'=>$dir_name,
				'is_active'=>isset($db_config['folder_name']),
				'version_check'=>$this->migration_manager->version_check($db_config['migration_version'],$json_config['version'])
			];

			$config = $json_config + $db_config + $extra;

			/* update packages that don't have migrations */
			if ($config['has_migrations'] == false && $config['version_check'] == 3) {
				/* no migrations - just update the veresion since the code is already up to date */
				$config['migration_version'] = $config['version'];
				$config['version_check'] = 2;

				$this->model->write_new_version($config['folder'],$config['version']);
			}

			$this->packages[$dir_name] = $config;
		}

		$msgs = false;

		if (!is_writable(ROOTPATH.'/application/config/autoload.php')) {
			$msgs[] = 'package config is not writable';
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
		$this->migration_manager->run_migrations($config,'up');

		/* add to db */
		$this->model->write($config['version'],$package,true);

		/* update config */
		$this->packages_config(ROOTPATH.'/packages/'.$package,'add');

		return true;
	}

	public function upgrade($package) {
		$config = $this->packages[$package];

		/* migrations up */
		$this->migration_manager->run_migrations($config,'up');

		$this->model->write_new_version($package,$config['version']);

		return true;
	}

	public function uninstall($package) {
		$config = $this->packages[$package];

		/* migrations down */
		$this->migration_manager->run_migrations($config,'down');

		/* deactive package autoload */
		$this->model->activate($package,false);

		$this->packages_config(ROOTPATH.'/packages/'.$package,'remove');

		return true;
	}

	public function delete($package) {
		$this->model->remove($package);

		$this->packages_config(ROOTPATH.'/packages/'.$package,'remove');

		/* delete the entire folder */
		ci()->load->helper('directory');

		$path = ROOTPATH.'/packages/'.$package;

		show_error($path);

		return true; #rmdirr($path);
	}

	public function load_info_json($folder) {
		$json_file = $folder.'/info.json';

		$error = false;

		if (!file_exists($json_file)) {
			$error = true;
		} else {
			$config = json_decode(file_get_contents($json_file),true);

			if ($config === null) {
				$error = true;
			}
		}

		if ($error) {
			$config['json_error'] = true;
			$config['is_active'] = false;
		} else {
			$config['uninstall'] = (isset($config['uninstall'])) ? $config['uninstall'] : true;
			$config['type'] = (isset($config['type'])) ? $config['type'] : 'package';
		}

		return $config;
	}

	public function packages_config($new_value,$mode='add') {
		$filepath = ROOTPATH.'/application/config/autoload.php';

		include $filepath;

		$packages_array = $autoload['packages'];

		$new_value = str_replace('\''.ROOTPATH,'ROOTPATH.\'',$new_value);

		foreach ($packages_array as $k=>$v) {
			if ($new_value == $v) {
				unset($packages_array[$k]);
			}
		}

		if ($mode == 'add') {
			$packages_array[] = $new_value;
		}
		
		$packages_array = preg_replace("/[0-9]+ \=\>/i", '', var_export($packages_array,true));

		$packages_array = '$autoload[\'packages\'] = '.str_replace('\''.ROOTPATH,'ROOTPATH.\'',$packages_array).';';

		$content = file_get_contents(ROOTPATH.'/application/config/autoload.php');

		$re = "/^\\s*\\\$autoload\\['packages']\\s*=\\s*array\\s*\\((.+?)\\);/ms"; 

		preg_match_all($re,$content,$matches);

		if (!isset($matches[0][0])) {
			show_error('Regular Expression Error: autoload.php');
		}

		$content = str_replace($matches[0][0],$packages_array,$content);

		return file_put_contents($filepath,$content);
	}

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