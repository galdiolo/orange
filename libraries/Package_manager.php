<?php

class package_manager {
	public $packages = [];
	public $package_table = 'orange_packages';
	public $config_header = "/*\nWARNING!\nThis file is directly modified by the framework\ndo not modify it unless you know what you are doing\n*/\n\n";
	public $config_onload;
	public $config_packages;

	public function __construct() {
		$packages_folder = ROOTPATH.'/modules';
		$packages_folders = glob($packages_folder.'/*',GLOB_ONLYDIR);

		$filepath = ROOTPATH.'/application/config/packages.php';

		include $filepath;

		$this->config_onload = $autoload['onload'];
		$this->config_packages = $autoload['packages'];

		foreach ($packages_folders as $package) {
			$dir_name = basename($package);

			$json_config = $this->load_config($package);

			$db_config = $this->db_read($dir_name);

			$starting_version = ($db_config['migration_version']) ? $db_config['migration_version'] : '0.0.0';

			$migration_files = $this->get_migrations_between($dir_name,$starting_version,$json_config['version']);

			$extra = [
				'migrations'=>$migration_files,
				'has_migrations'=>(count($migration_files) > 0),
				'folder'=>$dir_name,
				'is_active'=>isset($db_config['folder_name']),
				'version_check'=>$this->version_check($db_config['migration_version'],$json_config['version'])
			];

			$config = $json_config + $db_config + $extra;

			/* update packages that don't have migrations */
			if ($config['has_migrations'] == false && $config['version_check'] == 3) {
				/* no migrations - just update the veresion since the code is already up to date */
				$config['migration_version'] = $config['version'];
				$config['version_check'] = 2;

				$this->db_write_new_version($config['folder'],$config['version']);
			}

			$this->packages[$dir_name] = $config;
		}
	}

	public function index() {
		return $this->packages;
	}

	public function record($package) {
		return $this->packages[$package];
	}

	public function install($package) {
		$config = $this->packages[$package];

		/* migrations up */
		$this->run_migrations($config,'up');

		/* add to db */
		$this->db_write($config['version'],$package,true);

		/* update config */
		$this->add_packages_config('packages',ROOTPATH.'/modules/'.$package);

		if ($config['onload']) {
			$this->add_packages_config('onload',$package);
		}

		return true;
	}

	public function upgrade($package) {
		$config = $this->packages[$package];

		/* migrations up */
		$this->run_migrations($config,'up');

		$this->db_write_new_version($package,$config['version']);

		return true;
	}

	public function uninstall($package) {
		$config = $this->packages[$package];

		/* migrations down */
		$this->run_migrations($config,'down');

		/* deactive package onload and package autoload */
		$this->db_activate($package,false);

		$this->remove_packages_config('packages',ROOTPATH.'/modules/'.$package);
		$this->remove_packages_config('onload',$package);

		return true;
	}

	public function delete($package) {
		$this->db_remove($package);
		$this->remove_packages_config('packages',ROOTPATH.'/modules/'.$package);
		$this->remove_packages_config('onload',$package);

		/* delete the entire folder */
		ci()->load->helper('directory');

		$path = ROOTPATH.'/modules/'.$package;

		show_error($path);

		return true; #rmdirr($path);
	}

	public function db_activate($folder_name,$is_active) {
		return ci()->db->update($this->package_table,['is_active'=>(int)$is_active],['folder_name'=>$folder_name]);
	}

	public function db_remove($folder_name) {
		return ci()->db->delete($this->package_table,['folder_name'=>$folder_name]);
	}

	public function db_read($folder_name) {
		$results = ci()->db->where(['folder_name'=>$folder_name,'is_active'=>1])->get($this->package_table);

		return ($results->num_rows()) ? (array)$results->result()[0] : [];
	}

	public function db_write($migration_version,$folder_name,$is_active) {
		return ci()->db->replace($this->package_table,['folder_name'=>$folder_name,'migration_version'=>$migration_version,'is_active'=>(int)$is_active]);
	}

	public function db_write_new_version($folder_name,$migration_version) {
		return ci()->db->update($this->package_table,['migration_version'=>$migration_version],['folder_name'=>$folder_name]);
	}

	public function test_read_write() {
		$msgs = false;

		if (!is_writable(ROOTPATH.'/application/config/packages.php')) {
			$msgs[] = 'package config is not writable';
		}

		if (!is_writable(ROOTPATH.'/application/config/routes.php')) {
			$msgs[] = 'routes config is not writable';
		}

		return ($msgs === false) ? false : implode('<br>',$msgs);
	}

	public function get_migrations_between($package,$start_ver='0.0.0',$end_ver='999.999.999') {
		$migration_array = [];
		$migration_folder = ROOTPATH.'/modules/'.$package.'/support/migrations';

		if (is_dir($migration_folder)) {
			$migrations = glob($migration_folder.'/*.php');

			foreach ($migrations as $migration) {
				$filename = basename($migration);

				list($migration_file_version) = explode('-',str_replace('v','',$filename));

				if ($this->between_version($migration_file_version,$start_ver,$end_ver)) {
					$migration_array[] = $migration;
				}
			}
		}

		return $migration_array;
	}

	public function between_version($version,$start_version,$end_version) {
		if (version_compare($version,$end_version,'<=') || version_compare($version,$start_version,'>')) {
			return true;
		}

		return false;
	}

	public function version_check($current_version,$must_match) {
		/*
		1 = less than
		2 = exact
		3 = greater than
		*/
		$must_match = str_replace('*','0',$must_match);

		if (version_compare($must_match,$current_version,'=')) {
			return 2;
		}

		if (version_compare($must_match,$current_version,'<')) {
			return 1;
		}

		if (version_compare($must_match,$current_version,'>')) {
			return 3;
		}

		return false;
	}

	public function version_in_range($current_version,$range) {
		$regex = str_replace(['.', '*'], ['\.', '(\d+)'], '/^'.$range.'/');

		return (bool)(preg_match($regex, $current_version));
	}

	public function add_packages_config($key,$value,$mode='add') {
		$filepath = ROOTPATH.'/application/config/packages.php';

		include $filepath;

		$holder = $autoload[$key];

		if ($mode == 'add') {
			if (!in_array($value,$holder)) {
				$holder[] = $value;
			}
		} else {
			foreach ($holder as $k=>$v) {
				if ($value == $v) {
					unset($holder[$k]);
				}
			}
		}

		$autoload[$key] = $holder;

		$array = preg_replace("/[0-9]+ \=\>/i", '', var_export($autoload,true));

		$array = str_replace('\''.ROOTPATH,'ROOTPATH.\'',$array);

		return file_put_contents($filepath,'<?php '.chr(10).$this->config_header.'$autoload = '.$array.';');
	}

	public function remove_packages_config($key,$value) {
		return $this->add_packages_config($key,$value,'remove');
	}

	public function run_migrations($config,$dir) {
		/* if it's down then we need a complete set of migrations */
		$migration_files = ($dir == 'up') ? $config['migrations'] : $this->get_migrations_between($config['folder']);

		foreach ($migration_files as $migration_file) {
			$migration_filename = basename($migration_file,'.php');

			$class_name = str_replace(['.','-'],['','_'],$migration_filename);

			include $migration_file;

			if (!class_exists($class_name,false)) {
				show_error('Error: migration class named "'.$class_name.'" not found in "'.$migration_file.'"');
			}

			$migration = new $class_name($config);

			$success = true;

			if (method_exists($migration,$dir)) {
				$success = $migration->$dir();
			}

			if ($success !== true) {
				return $success;
			}
		}

		return true;
	}

	public function load_config($folder) {
		$json_file = $folder.'/info.json';

		if (!file_exists($json_file)) {
			show_error('Could not locate info.json in the folder '.$folder);
		}

		$config = json_decode(file_get_contents($json_file),true);

		if ($config === null) {
			$config['json_error'] = true;
			$config['is_active'] = false;
		}

		$this->setup_config($config,'uninstall',true);
		$this->setup_config($config,'type','package');

		return $config;
	}

	public function setup_config(&$config,$what,$default=null) {
		$config[$what] = (isset($config[$what])) ? $config[$what] : $default;
	}

	public function route($from,$to,$mode) {
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