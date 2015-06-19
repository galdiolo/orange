<?php

/*
This module can be loaded and instancated directly as long as you send in the required data.
This makes it so a command line script can run module functions

pretty much just rootpath & apppath
*/
class Module_core {
	protected $modules_file;
	protected $composer_file;
	protected $active_modules = [];
	protected $composer_packages = [];
	protected $upgrades_folder = '_upgrades';
	protected $root;
	protected $apppath;
	protected $where; /* where installed */
	public $modules = [];

	public function __construct($rootpath=null,$apppath=null) {
		return $this->init($rootpath=null,$apppath=null);
	}

	public function init($rootpath=null,$apppath=null) {
		$this->root = ($rootpath) ? $rootpath : ROOTPATH;
		$this->apppath = ($apppath) ? $apppath : APPPATH;

		$this->modules_file = $this->apppath.'config/modules.php';
		$this->composer_file = $this->root.'/composer.json';

		if (!file_exists($this->modules_file)) {
			return 'Configuration File Missing?';
		}

		/* load our module config */
		include $this->modules_file;

		foreach ($autoload['active'] as $package) {
			$this->active_modules[$package] = basename($package);
		}

		/* get the composer installed packages */
		if (file_exists($this->composer_file)) {
			$composer = json_decode(file_get_contents($this->composer_file));
			$this->composer_packages = (array)$composer->require;
		}

		/* get all of our modules details */
		$this->modules = $this->_get_configs();
		$this->modules['_upgrades'] = $this->_get_configs('_upgrades/');

		if (!is_writable($this->modules_file)) {
			$this->modules['_messages'][] = 'config/modules.php not read / writeable.';
		}

		if (!is_writable($this->root.'/var/upload_temp')) {
			$this->modules['_messages'][] = '/var/upload_temp not read / writeable.';
		}

		if (!is_writable($this->root.'/modules')) {
			$this->modules['_messages'][] = '/modules not read / writeable.';
		}

		if (!is_writable($this->root.'/modules/'.$this->upgrades_folder)) {
			$this->modules['_messages'][] = '/modules/'.$this->upgrades_folder.' not read / writeable.';
		}

		return true;
	}

	public function get_modules_config() {
		include $this->modules_file;

		return $autoload;
	}

	public function details() {
		/* this works directly on $this->modules */
		foreach ($this->modules as $key=>$module) {
			$this->_calculate_requirements($key,$module);
		}

		/* custom sort on class name */
		uasort($this->modules, function($a,$b) {
			return ($a['classname'] < $b['classname']) ? -1 : 1;
		});

		return $this->modules;
	}

	/* install a module */
	public function install($name=NULL) {
		$reply = $this->_prep($name);

		if (!is_object($reply)) {
			return $reply;
		}

		$installer = $reply;

		/* fire the installer - version 0 signifies install new */
		if (!$installer->install_module(0)) {
			return 'Error running the installer.';
		}

		/* if that's all good then fire the config modifiers */
		if ($installer->onload) {
			if ($reply = $this->update_config('public_onload',$name,false) !== true) {
				return $reply;
			}
		}

		if ($installer->onload) {
			if ($reply = $this->update_config('admin_onload',$name,false) !== true) {
				return $reply;
			}
		}

		if ($installer->autoload) {
			if ($reply = $this->update_config('packages',$name,false) !== true) {
				return $reply;
			}
		}

		if ($reply = $this->update_config('active',$name,false) !== true) {
			return $reply;
		}

		return true;
	}

	public function uninstall($name=NULL) {
		$reply = $this->_prep($name);

		if (!is_object($reply)) {
			return $reply;
		}

		$installer = $reply;

		/* fire this first incase something breaks in the uninstall script */
		if ($installer->autoload) {
			if (!$this->update_config('packages',$name,true)) {
				return 'Error removing autoload.';
			}
		}

		if ($installer->onload) {
			if (!$this->update_config('public_onload',$name,true)) {
				return 'Error removing onload.';
			}
			if (!$this->update_config('admin_onload',$name,true)) {
				return 'Error removing onload.';
			}
		}

		/* fire the installer - version 0 signifies install new */
		if (!$installer->uninstall_module(0)) {
			return 'Error running the installer.';
		}

		if (!$this->update_config('active',$name,true)) {
			return 'Error removing active.';
		}

		return true;
	}

	public function delete($name=NULL) {
		$reply = $this->_prep($name);

		if (!is_object($reply)) {
			return $reply;
		}

		$installer = $reply;

		/* it should already be removed from the config but just incase */
		if ($installer->autoload) {
			if (!$this->update_config('packages',$name,true)) {
				return 'Error removing autoload.';
			}
		}

		if ($installer->onload) {
			if (!$this->update_config('public_onload',$name,true)) {
				return 'Error removing onload.';
			}
			if (!$this->update_config('admin_onload',$name,true)) {
				return 'Error removing onload.';
			}
		}

		/* fire the install remove module */
		if (!$installer->remove_module(0)) {
			return 'Error running the installer.';
		}

		/* remove module */
		if (!$this->delete_files($this->root.'/modules/'.$name)) {
			return 'Error could not delete folder "'.$name.'".';
		}

		if (!$this->update_config('active',$name,true)) {
			return 'Error removing active.';
		}

		return true;
	}

	public function upgrade($name=NULL) {
		$reply = $this->_prep($name,$this->upgrades_folder.'/');

		if (!is_object($reply)) {
			return $reply;
		}

		/* full upgrade object */
		$upgrades_installer = $reply;

		/* just the configs from the currently installed module as object */
		$installed_config = (object)$this->_config_magic($this->root.'/modules/'.$name.'/install_'.$name.'.php');

		/* run the current modules auto stuff */
		if ($upgrades_installer->autoload) {
			if (!$this->update_config('packages',$name,true)) {
				return 'Error removing autoload.';
			}
		}

		if ($upgrades_installer->onload) {
			if (!$this->update_config('public_onload',$name,true)) {
				return 'Error removing onload.';
			}
			if (!$this->update_config('admin_onload',$name,true)) {
				return 'Error removing onload.';
			}
		}

		/* remove the current module folder */
		if (!$this->delete_files($this->root.'/modules/'.$name)) {
			return 'Error could not delete "'.$name.'".';
		}

		/* MOVE entire folder from upgrade to module */
		if (!rename($this->root.'/modules/'.$this->upgrades_folder.'/'.$name,$this->root.'/modules/'.$name)) {
			return 'Error moving module.';
		}

		/* fire the upgrade */
		if (!$upgrades_installer->upgrade_module($installed_config->version,$upgrades_installer->version)) {
			return 'Error running the upgrader.';
		}

		/* if that's all good then fire the autoload stuff again */
		if ($upgrades_installer->onload) {
			if ($reply = $this->update_config('public_onload',$name,false) !== true) {
				return $reply;
			}
			if ($reply = $this->update_config('admin_onload',$name,false) !== true) {
				return $reply;
			}
		}

		if ($upgrades_installer->autoload) {
			if ($reply = $this->update_config('packages',$name,false) !== true) {
				return $reply;
			}
		}

		return true;
	}

	public function unzip_n_move($upload) {
		/* this is one of our libraries so we know exactly where it is at */
		require __DIR__.'/Unzip.php';

		$unzip = new Unzip();

		$working = dirname($upload).'/'.md5($upload);

		mkdir($working, 0777, true);

		$unzip->extract($upload,$working);

		/* delete zip */
		@unlink($upload);

		/* Get all the folders - should only be 1 */
		$module_path = glob($working.'/*',GLOB_ONLYDIR);

		/* save the folder name */
		$module_path = $module_path[0];

		/* get the module name */
		$module_name = basename($module_path);

		/* does this actually have a install.php file */
		if (!file_exists($module_path.'/install_'.$module_name.'.php')) {
			/* no remove it immediately it's not a module */
			$this->delete_files($module_path);

			return 'Install File Missing';
		}

		/* place it in upgrade folder or in normal module folder? */
		/* return new or update on success or false on fail */
		$this->where = $this->smart_move($module_path);

		/* delete the now empty working folder */
		$this->delete_files($working);

		return true;
	}

	/* return new or update on success or false on fail */
	public function smart_move($module_path) {
		$module_name = basename($module_path);

		/*
		Is there already a module with the same name and is it installed?
		if it's not already installed we can drop it right in the
		modules folder since it's NOT a upgrade
		*/
		if (file_exists($this->root.'/modules/'.$module_name) && in_array($module_name,$this->active_modules)) {
			/* treat as upgrade */

			/* remove any folder with the same name in the upgrades folder */
			$this->delete_files($this->root.'/modules/_upgrades/'.$module_name);

			/* move it into the upgrades folder*/
			$reply = rename($module_path,$this->root.'/modules/_upgrades/'.$module_name);
			$where = 'new';
		} else {
			/* treat as a new module - it can go into the regular modules folder */

			/* remove any folder with the same name in the upgrades folder and modules folder */
			$this->delete_files($this->root.'/modules/'.$module_name);
			$this->delete_files($this->root.'/modules/_upgrades/'.$module_name);

			/* move it into the modules folder */
			$reply = rename($module_path,$this->root.'/modules/'.$module_name);
			$where = 'update';
		}

		return ($reply === true) ? $where : false;
	}

	public function get_by($field,$equals) {
		foreach ($this->modules as $m) {
			if ($m[$field] == $equals) {
				return $m;
			}
		}

		return;
	}

	protected function _calculate_requirements($key,$module) {
		if (substr($key,0,1) == '_') {
			return;
		}

		$name = $module['name'];

		/* are requirements loaded yet? */
		$install_errors = [];

		/* test the requirements if it's not active then check the requirements */
		if (count($module['requires'])) {
			/* this one is easy since it's a array already */
			foreach ($module['requires'] as $module_raw_name=>$min_max_version) {
				$success = $this->check_required_module([$module_raw_name=>$min_max_version]);

				if ($success !== true) {
					$install_errors[] = $success;
				}
			}
		}

		if (count($module['requires_composer'])) {
			/* this one is easy since it's a array already */
			foreach ($module['requires_composer'] as $module_raw_name=>$version) {
				$success = $this->check_required_composer_package([$module_raw_name=>$version]);

				if ($success !== true) {
					$install_errors[] = $success;
				}
			}
		}

		/* does anyone require this module? */
		$uninstall_errors = [];

		/* let's check to see if anyone else needs me before I uninstall */
		$success = $this->check_module_isnt_required($key);

		if ($success !== true) {
			$uninstall_errors[] = $success;
		}

		$upgrade_errors = [];

		$upgrade_version = $this->modules['_upgrades'][$name]['version'];

		/* do we have a upgrade available? */
		if (!empty($upgrade_version)) {

			$upgrade_required = $this->modules['_upgrades'][$name]['requires'];

			if (is_array($upgrade_required) && count($upgrade_required) > 0) {
				/* this one is easy since it's a array already */
				foreach ($upgrade_required as $module_raw_name=>$min_max_version) {
					$success = $this->check_required_module([$module_raw_name=>$min_max_version]);

					if ($success !== true) {
						$upgrade_errors[] = $success;
					}
				}
			}

			$upgrade_composer = $this->modules['_upgrades'][$name]['requires_composer'];

			if (is_array($upgrade_composer) && count($upgrade_composer) > 0) {
				/* this one is easy since it's a array already */
				foreach ($upgrade_composer as $module_raw_name=>$version) {
					$success = $this->check_required_composer_package([$module_raw_name=>$version]);

					if ($success !== true) {
						$upgrade_errors[] = $success;
					}
				}
			}

			if (version_compare($upgrade_version,$module['version'],'<')) {
				$upgrade_errors['version'] = 'Update v'.$upgrade_version.' is older than installed v'.$module['version'];
			}

			if (version_compare($upgrade_version,$module['version'],'=')) {
				$upgrade_errors['version'] = 'Update and currently installed version are the same';
			}

		} /* end upgrade logic */

		$this->modules[$key]['install_errors'] = $install_errors;
		$this->modules[$key]['upgrade_errors'] = $upgrade_errors;
		$this->modules[$key]['uninstall_errors'] = $uninstall_errors;
		$this->modules[$key]['has_upgrade'] = $upgrade_version;
	}

	protected function _prep($name,$folder='') {
		$require = $this->root.'/modules/'.$folder.$name.'/install_'.$name.'.php';

		/* we already checked this exists but what the heck */
		if (!file_exists($require)) {
			return 'Error in module model install method. Missing "'.$name.'"';
		}

		/* fire install module */
		require $require;

		$class_name = 'install_'.$name;

		if (!class_exists($class_name)) {
			return 'Error in module model install method. Missing Class "'.$class_name.'"';
		}

		return new $class_name($name);
	}

	protected function check_install_method() {
		/*
		place holder for future feature
		run a install method on the provided module and display a error
		in the info icon [?] link if this method returns false
		if module has install_check run it
		*/
	}

	protected function check_upgrade_method() {
		/*
		place holder for future feature
		run a upgrade method on the provided module and display a error
		in the info icon [?] link if this method returns false
		if module has upgrade_check run it
		*/
	}

	protected function check_required_module($requirements=[]) {
		foreach ($requirements as $module_folder_name=>$range) {
			/* if the module folder name is empty just return true */
			if (empty($module_folder_name)) {
				return true;
			}

			if (!file_exists($this->root.'/modules/'.$module_folder_name)) {
				return 'Module "'.$module_folder_name.'" folder not found.';
			}

			/* check the min and max */
			if (!file_exists($this->root.'/modules/'.$module_folder_name.'/install_'.$module_folder_name.'.php')) {
				return 'Module "'.$module_folder_name.'" installer file not found.';
			}

			if (!in_array($module_folder_name,$this->active_modules)) {
				return 'Required module "'.$module_folder_name.' v'.$min_version.'-'.$max_version.'" is not active.';
			}

			include_once $this->root.'/modules/'.$module_folder_name.'/install_'.$module_folder_name.'.php';

			$class_name = 'install_'.$module_folder_name;

			$module = new $class_name();

			/* if there aren't a exact match for the version then we must test */
			if (!$this->_version_in_range($module->version,$range)) {
				return 'Required module "'.$module->name.' v'.$module->version.'" does not match requirements v'.$range.'.';
			}
		}

		return true;
	}

	protected function check_required_composer_package($requires_composer=[]) {
		foreach ($requires_composer as $name=>$looking_for_version) {

			if (!array_key_exists($name,$this->composer_packages)) {
				return 'Required composer package "'.$name.' v'.$looking_for_version.'" is not loaded.';
			}

			$composer_version = $this->composer_packages[$name];

			/*
			if composer is "any" version then I guess they don't care?
			not the best thing but we have no idea of the version so there
			isn't much we can do.
			*/
			if ($composer_version == '*' || $looking_for_version == '*') {
				return true;
			}

			$composer_version = preg_replace("/[^0-9\.]/", "0",$composer_version);

			if ($this->_version_in_range($looking_for_version,$composer_version)) {
				return 'Required composer package "'.$name.' v'.$looking_for_version.'" is not loaded.';
			}
		}

		return true;
	}

	protected function check_module_isnt_required($module_name) {
		$current_module = $this->modules[$module_name];

		$errors = [];

		foreach ($this->modules as $key=>$module) {
			if (is_array($module['requires'])) {
				foreach ($module['requires'] as $name=>$range) {
					$required_module = $this->get_by('classname',$module['classname']);

					if ($name == $current_module['classname'] && $required_module['is_active'] == true) {
						$errors[] = '"'.$current_module['name'].'" can\'t be uninstalled because "'.$required_module['name'].'" requires it.';
					}
				}
			}
		}

		if (count($errors) > 0) {
			return implode('<br>',$errors);
		}

		return true;
	}

	protected function _version_check($current_version,$must_match) {
		/*
		1 = less than
		2 = exact
		3 = greater than
		*/

		$must_match = str_replace('*','0',$must_match);

		if (version_compare($must_match,$current_version,'<')) {
			return 1;
		}

		if (version_compare($must_match,$current_version,'=')) {
			return 2;
		}

		if (version_compare($must_match,$current_version,'>')) {
			return 3;
		}

		return false;
	}

	protected function _version_in_range($current_version,$range) {
		$regex = str_replace(['.', '*'], ['\.', '(\d+)'], '/^'.$range.'/');

		return (bool)(preg_match($regex, $current_version));
	}

	public function update_config($ary_key,$name,$remove) {
		/* $name = module folder name */
		if (!file_exists($this->modules_file)) {
			return 'The "application/config/modules.php" file is missing?';
		}

		/* base root file */
		$this->_replace($this->modules_file,$ary_key,ROOTPATH.'/modules/'.$name,$remove);

		/* any env folders? */
		$env_folders = glob($this->root.'/application/config/*',GLOB_ONLYDIR);

		foreach ($env_folders as $env_folder) {
			$env_file = $env_folder.'/modules.php';

			if (file_exists($env_file)) {
				$this->_replace($env_file,$ary_key,ROOTPATH.'/modules/'.$name,$remove);
			}
		}

		return true;
	}

	protected function _replace($modules_file,$key,$value,$remove) {
		/* this loads the file */
		include $modules_file;

		/* add new value */
		if ($remove) {
			foreach ($autoload[$key] as $idx=>$val) {
				if ($autoload[$key][$idx] == $value) {
					unset($autoload[$key][$idx]);
				}
			}
		} else {
			$autoload[$key][] = $value;
		}

		/* let's make sure it's not a dup */
		$autoload[$key] = array_unique($autoload[$key]);

		return $this->write($modules_file,$autoload);
	}

	public function write($modules_file,$ary) {
		$n = chr(10);
	
		$content = '<?php'.$n.'/*'.$n.'WARNING!'.$n.'This file is directly modified by the framework'.$n.'do not modify it unless you know what you are doing'.$n.'*/'.$n.$n;

		foreach ($ary as $key=>$elements) {
			$content .= '$autoload[\''.$key.'\'] = array('.$n;
		
			foreach ((array)$elements as $k=>$e) {
				$k = str_replace("'","\'",$k);
				$e = str_replace("'","\'",$e);

				if (is_numeric($k)) {
					$e = str_replace(ROOTPATH,'',$e);
					$content .= chr(9).'ROOTPATH.\''.$e.'\','.$n;
				} else {
					$content .= chr(9)."'".$k."' => '".$e."',".$n;
				}
			}
		
			$content .= ');'.$n.$n;
		}

		return file_put_contents($modules_file,$content);
	}

	protected function _normalize($text) {
		/* normalize source code */

		/* trim any beginning spaces or trailing spaces */
		$text = trim($text);

		/* remove runs of spaces or tabs */
		$text = preg_replace('/[ \t]+/',' ',$text);

		/* Removes multi-line comments and does not create a blank line, also treats white spaces/tabs */
		$text = preg_replace('!\/\*[\s\S]*?\*\/!s', '', $text);

		/* Removes single line '//' comments, treats blank characters */
		$text = preg_replace('![ \t]*//.*[ \t]*[\r\n]!', '', $text);

		/* Strip blank lines */
		$text = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $text);

		/* normalize line endings */
		$text = str_replace(chr(13).chr(10),chr(10),$text);

		/* remove spaces at the begining of the lines */
		$text = str_replace([chr(32).chr(10),chr(10).chr(32)],chr(10),$text);

		return $text;
	}

	protected function _get_configs($folder='') {
		$modules = [];

		/* any new or modules that need updating */
		$mlist = glob($this->root.'/modules/'.$folder.'*',GLOB_ONLYDIR);

		foreach ($mlist as $m) {
			if (substr(basename($m,0,1)) != '_') {
				$filename = $m.'/install_'.basename($m).'.php';

				if (file_exists($filename)) {
					$config = $this->_config_magic($filename);

					$modules[$config['name']] = $config;
				}
			}
		}

		return $modules;
	}

	protected function _config_magic($filename) {
		$content = file_get_contents($filename);
		$content = $this->_normalize($content); /* flatten this thing out */
		$content = str_replace('public $','$',$content); /* convert class parameters to regular vars */

		$lines = explode(chr(10),$content);

		array_shift($lines); /* shift off <?php */
		array_shift($lines); /* shift off class... */

		$parameters = '';

		foreach ($lines as $line) {
			if (substr($line,0,9) == 'function ' || substr($line,0,7) == 'public ' || substr($line,0,1) == '}') {
				break;
			}

			$parameters .= $line.chr(10);
		}

		$map = 'name,version,info,help,type,install,uninstall,upgrade,remove,onload,autoload,requires,theme,table,notes,requires_composer';
		$map = explode(',',$map);

		foreach ($map as $n) {
			$array .= '"'.$n.'"=>$'.$n.',';
		}

		$parameters .= 'return ['.$array.'];';

		$func = create_function('',$parameters);

		$config = $func();

		$config['installer'] = $filename;
		$config['filename'] = basename($filename,'.php');
		$config['classname'] = substr(basename($filename,'.php'),8);
		$config['is_active'] = in_array($config['classname'],$this->active_modules);

		return $config;
	}

	protected function delete_files($path) {
		ci()->load->helper('directory');

		return rmdirr($path);
	}

} /* end class */