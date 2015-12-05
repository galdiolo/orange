<?php
/*
CREATE TABLE `orange_packages` (
	`full_path` varchar(255) NOT NULL,
	`migration_version` varchar(16) DEFAULT '0.0.0',
	`is_active` tinyint(1) unsigned DEFAULT '1',
	`priority` tinyint(4) NOT NULL DEFAULT '55',
	PRIMARY KEY (`full_path`),
	KEY `idx_full_path` (`full_path`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8
*/

class package_manager {
	public $packages = [];
	public $config_header = "/*\nWARNING!\nThis file is directly modified by the framework\ndo not modify it unless you know what you are doing\n*/\n\n";
	public $messages;
	public $default_load_priority = 55;
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

		/* check out folders */
		$msgs = false;

		if (!is_writable($this->autoload)) {
			$msgs[] = 'autoload config is not writable';
		}

		if (!is_writable($this->routes)) {
			$msgs[] = 'routes config is not writable';
		}

		$this->messages = ($msgs === false) ? false : implode('<br>',$msgs);

		$this->prepare();
	}

	/*
	parse through the folder and setup the mega array with data
	based on json files.
	*/
	public function prepare() {
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
		/* this is now done by composer */
		$this->package_helper->requirements($this->packages);

		$this->package_helper->migrations($this->packages);

		$this->package_helper->buttons($this->packages);

		/* sort all nice based on namespace */
		uasort($this->packages,function($obj1,$obj2) {
			if ($obj1['composer']['name'] == $obj2['composer']['name']) {
				return 0;
			}

			return ($obj1['composer']['name'] < $obj2['composer']['name']) ? -1 : 1;
		});

		/* return incase somebody else called us */
		return $this->packages;
	}

	protected function _prepare($packages_info,$type_of_package) {
		foreach ($packages_info as $info) {
			$key = trim(str_replace(ROOTPATH,'',dirname($info)));

			/* load json composer file */
			$composer_config = json_decode(file_get_contents($info),true);

			/* did we get a error or does it not have a description */
			if ($composer_config !== null) {
				if ($composer_config['description']) {

					$db_config = (array)$this->o_packages_model->get($key);

					if (isset($composer_config['orange'])) {
						$composer_config['orange']['priority'] = ($composer_config['orange']['priority']) ? $composer_config['orange']['priority'] : $this->default_load_priority;
					}

					$cr = $composer_config['orange']['priority'];

					$cr = (in_array($cr,range(0,20)) ? 'highest' : $cr);
					$cr = (in_array($cr,range(21,40)) ? 'high' : $cr);
					$cr = (in_array($cr,range(41,60)) ? 'normal' : $cr);
					$cr = (in_array($cr,range(61,80)) ? 'low' : $cr);
					$cr = (in_array($cr,range(81,100)) ? 'lowest' : $cr);

					$extra = [
						'human_priority'=>$cr,
						'url_name'=>bin2hex($key),
						'key'=>$key,
						'is_active'=>(($db_config['is_active']) ? true : false),
						'folder'=>$type_of_package,
						'name'=>$folder.'/'.$filename,
					];

					$this->packages[$key] = $extra + ['composer'=>$composer_config,'database'=>$db_config];
				}
			}
		}
	}

	public function records() {
		return $this->packages;
	}

	public function record($package) {
		return $this->packages[$package];
	}

	public function activate($key,$force=false) {
		log_message('debug', 'Package Manager Activate');

		/* need to install this into the database */
		$package = $this->packages[$key];

		if (!is_array($package)) {
			log_message('debug', 'Activate package not an array');

			return false;
		}

		$package_name = $package['composer']['name'];

		log_message('debug', 'Activate '.$package_name);

		/* is this already in the database and we just need to activate it again? */
		if (count($package['database']) > 0) {
			/* activate in database and bail */
			if (!$this->o_packages_model->activate($key,true)) {
				log_message('debug', 'Activate error record '.$package_name);

				return false;
			}

			return true;
		}

		/* if it's new then we need to go through the complete install */

		/* add it to the database */
		$version = ($package['composer']['orange']['version']) ? $package['composer']['orange']['version'] : '1.0.0';
		$priority = ($package['composer']['orange']['priority']) ? $package['composer']['orange']['priority'] : $this->default_load_priority;

		/* add to db */
		if (!$this->o_packages_model->add($key,$version,true,$priority)) {
			log_message('debug', 'Activate error add record '.$package_name);

			return false;
		}

		/* migrations up from 0.0.0 to what every the orange package version number is */
		if (!$this->package_migration_manager->run_migrations_up($package)) {
			log_message('debug', 'Activate error run migrations '.$package_name);

			return false;
		}

		/* update autoload to reflect the changes */
		if (!$this->create_autoload()) {
			log_message('debug', 'Activate error create autoload '.$package_name);

			return false;
		}

		/* update onload to reflect the changes */
		if (!$this->create_onload()) {
			log_message('debug', 'Activate error create onload '.$package_name);

			return false;
		}

		$this->flush();

		return true;
	}

	public function deactivate($key,$force=false) {
		log_message('debug', 'Package Manager Deactivate');

		/* need to install this into the database */
		$package = $this->packages[$key];

		if (!is_array($package)) {
			log_message('debug', 'Deactivate package not an array');

			return false;
		}

		$package_name = $package['composer']['name'];

		log_message('debug', 'Deactivate '.$package_name);

		/* deactive in database */
		if (!$this->o_packages_model->activate($key,false)) {
			log_message('debug', 'Deactivate error record '.$package_name);

			return false;
		}

		/* update autoload to reflect the changes */
		if (!$this->create_autoload()) {
			log_message('debug', 'Deactivate error create autoload '.$package_name);

			return false;
		}

		/* update onload to reflect the changes */
		if (!$this->create_onload()) {
			log_message('debug', 'Deactivate error create onload '.$package_name);

			return false;
		}

		$this->flush();

		return true;
	}

	public function migrate($key,$force=false) {
		log_message('debug', 'Package Manager Upgrade');

		$package = $this->packages[$key];

		if (!is_array($package)) {
			log_message('debug', 'Upgrade package not an array');

			return false;
		}

		$package_name = $package['composer']['name'];

		log_message('debug', 'Upgrade '.$package_name);

		/* migrations up from 0.0.0 to the current orange version */
		if (!$this->package_migration_manager->run_migrations_up($package)) {
			log_message('debug', 'Upgrade error run migration up '.$package_name);

			return false;
		}

		/* update the database with the new current version number */
		if (!$this->o_packages_model->version($key,$package['composer']['orange']['version'])) {
			log_message('debug', 'Upgrade error write new version '.$package_name);

			return false;
		}

		/* update the priority if needed */
		if (!$this->o_packages_model->priority($key,$package['composer']['orange']['priority'])) {
			log_message('debug', 'Upgrade error write new priority '.$package_name);

			return false;
		}

		/* update autoload to reflect the changes */
		if (!$this->create_autoload()) {
			log_message('debug', 'Upgrade error create autoload '.$package_name);

			return false;
		}

		/* update onload to reflect the changes */
		if (!$this->create_onload()) {
			log_message('debug', 'Upgrade error create onload '.$package_name);

			return false;
		}

		$this->flush();

		return true;
	}

	public function uninstall($key,$force=false) {
		log_message('debug', 'Package Manager Uninstall');

		$package = $this->packages[$key];

		if (!is_array($package)) {
			log_message('debug', 'Uninstall package not an array');

			return false;
		}

		$package_name = $package['composer']['name'];

		log_message('debug', 'Uninstall '.$package_name);

		/* migrations down to 0.0.0 from the currently installed version */
		if (!$this->package_migration_manager->run_migrations_down($package)) {
			log_message('debug', 'Uninstall run migratons down error '.$package_name);

			return false;
		}

		/* remove the entire record from the database */
		if (!$this->o_packages_model->delete($package['key'])) {
			log_message('debug', 'Uninstall Delete error '.$package_name);

			return false;
		}

		if (!$this->create_autoload()) {
			log_message('debug', 'Uninstall create autoload error '.$package_name);

			return false;
		}

		if (!$this->create_onload()) {
			log_message('debug', 'Uninstall create onload error '.$package_name);

			return false;
		}

		$this->flush();

		return true;
	}

	public function flush($all=false) {
		usleep(2500);
		ci()->cache->clean();
		usleep(2500);
		ci()->load->settings_flush();
		usleep(2500);
		ci()->auth->refresh_userdata();
		usleep(2500);
		
		if ($all) {
			$this->create_onload();
			usleep(2500);
			$this->create_autoload();
			usleep(2500);
		}
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
		
		/* opcache flushed if needed by atomic function */
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

} /* end class */