<?php

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

		if (is_array($installer->routes)) {
			$this->routes_add($installer->routes);
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

		if (is_array($installer->routes)) {
			$this->routes_write($this->routes_remove($installer->routes));
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

		if (is_array($installer->routes)) {
			$this->routes_write($this->routes_remove($installer->routes));
		}

		return true;
	}

	public function upgrade($name=NULL) {
die('upgrade');
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

		if (is_array($upgrades_installer->routes)) {
			$this->routes_write($this->routes_remove($upgrades_installer->routes));
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




	public function routes_read() {
		include APPPATH.'/config/routes.php';

		return $route;
	}

	public function routes_write($routes=null,$new_url=null,$new_route=null) {
		$routes = ($routes) ? $routes : $this->routes_read();

		$n = chr(10);

		/* write a new file then move it in one action */
		$text  = '<?php'.$n.$n;
		$text .= '/* modified by router module don\'t change the format unless you know what you are doing! */'.$n.$n;
		$text .= '$route[\'translate_uri_dashes\'] = '.(($route['translate_uri_dashes']) ? 'true' : 'false').';'.$n.$n;

		$text .= '$route[\'default_controller\'] = \''.$route['default_controller'].'\';'.$n;
		$text .= '$route[\'404_override\'] = \''.$route['404_override'].'\';'.$n.$n;

		foreach ($routes as $k=>$r) {
			$text .= '$route[\''.$k.'\'] = \''.$r.'\';'.$n;
		}

		if ($new_url) {
			$text .= '$route[\''.$new_url.'\'] = \''.$new_route.'\';'.$n;
		}

		/* atomic single file system action rename */
		$success = file_put_contents(APPPATH.'/config/swap_routes.php',trim($text).$n);

		/* did we get a error? bail */
		if (!$success) {
			return false;
		}

		return rename(APPPATH.'/config/swap_routes.php',APPPATH.'/config/routes.php');
	}

	public function routes_add($new_url=null,$new_route=null) {
		if (is_array($new_url)) {
			foreach ($new_url as $u=>$r) {
				$this->routes_write(null,$u,$r);
			}
		} else {
			return $this->routes_write(null,$new_url,$new_route);
		}
	}

	public function routes_remove($remove_routes,$routes=null) {
		$routes = ($routes) ? $routes : $this->routes_read();

		foreach ($routes as $key=>$val) {
			foreach ($remove_routes as $k=>$v) {
				if ($key == $k && $val == $v) {
					unset($routes[$key]);
				}
			}
		}

		return $routes;
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

		return file_put_contents($modules_file,$this->create_modules_config($autoload));
	}

	protected function create_modules_config($array) {
		$n = chr(10);

		$content  = '<?php'.$n;
		$content .= '/*'.$n;
		$content .= 'WARNING!'.$n;
		$content .= 'This file is directly modified by the framework'.$n;
		$content .= 'do not modify it unless you know what you are doing'.$n;
		$content .= '*/'.$n.$n;

		foreach ($array as $key=>$elements) {
			$content .= '$autoload[\''.$key.'\'] = array('.$n;

			if (is_object($elements)) {
				foreach ((array)$elements as $k=>$e) {
					$content .= chr(9).$this->has_root($k)." => ".$this->has_root($e).",".$n;
				}
			} elseif (is_array($elements)) {
				foreach ($elements as $e) {
					$content .= chr(9).$this->has_root($e).",".$n;
				}
			}

			$content .= ');'.$n.$n;
		}

		return trim($content).$n;
	}

	/* same function as in module_core.php */
	protected function has_root($input) {
		if (!defined('ROOTPATH')) {
			define('ROOTPATH',md5(microtime()));
		}

		$wrapped = false;

		if (strpos($input,'{ROOTPATH}') !== false) {
			/* "{ROOTPATH}modules/plugin_combobox" */
			$wrapped = true;
			$input = str_replace('{ROOTPATH}','/',$input);
			$input = "ROOTPATH.'".$input."'";
		} elseif (strpos($input,ROOTPATH) !== false) {
			/* "/a/b/c/modules/plugin_combobox" */
			$wrapped = true;
			$input = str_replace(ROOTPATH,'',$input);
			$input = "ROOTPATH.'".$input."'";
		}

		if (!$wrapped) {
			$input = "'".$input."'";
		}

		return $input;
	}



	protected function delete_files($path) {
		ci()->load->helper('directory');

		return rmdirr($path);
	}