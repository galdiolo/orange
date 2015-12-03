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

class MY_Loader extends CI_Loader {
	/* only one theme at a time can be assigned so we can save that here */
	public $current_theme = ''; /* current theme */
	/*
	add helpers here to autoload these AFTER the normal helpers are loaded
	this way our helpers can "extend" the loaded helpers
	remember to use "function_exists" in these helpers
	*/
	public $orange_extended_helpers = ['array','date','directory','file','string'];
	public $settings = null; /* local per request storage */
	public $onload_path = ROOTPATH.'/application/config/onload.php';
	public $cache = 'settings.cache';
	public $themes = '';

	public $added_paths = [];
	public $added_paths_view = [];

	/**
	* Helper Loader
	* Overridden to allow O_helpers to load after user helpers
	*
	* @param	string|string[]	$helpers	Helper name(s)
	* @return	object
	*/
	public function helper($helpers = array()) {
		/* load the helper(s) as normal */
		parent::helper($helpers);

		/* then try to load the orange helper */
		foreach ((array)$helpers as $helper) {
			/*
			Let's not waste time trying to load orange helpers we know don't exist.
			Since we created them we know if they exist or not
			*/
			$helper = str_replace('_helper','',basename($helper,'.php'));

			if (in_array($helper,$this->orange_extended_helpers)) {
				/* the orange helpers are always in the orange/helpers folder. Where else would they be? */
				$o_helper_file = __DIR__.'/../helpers/o_'.$helper.'_helper.php';

				/* if real path returns the path and it's not already loaded */
				if (!$this->_ci_helpers[$o_helper_file]) {
					/* mark it as loaded */
					$this->_ci_helpers[$o_helper_file] = true;

					/* and load it */
					include $o_helper_file;
				}
			}
		}
	}

	public function presenter(&$object=null,$presenter='',$inject=null) {
		/* what is the presenter classes name */
		$classname = ucfirst($presenter).'_presenter';

		/* does it exist? don't try to load it! */
		if (!class_exists($classname,false)) {
			/* is it part of our include path? */
			$presenter_file = stream_resolve_include_path('presenters/'.$classname.'.php');

			/* nope couldn't find it raise a fatal error */
			if (!$presenter_file) {
				show_error('Presenter Not Found "'.$classname.'"');
			}

			/* load this for later on each row */
			include $presenter_file;

			log_message('debug', "Presenter: Loaded '$classname'.");
		}

		/*
		if it's a array of objects return it as part of a Iterator
		if it's not then return it as the presenter classes only record
		*/
		$object = (is_array($object)) ? new Presenter_iterator($object,$classname,$inject) : new $classname($object,$inject);

		return $object;
	}


	/**
	* Settings
	* New Function
	* load and merge configuration from file system & database
	*
	* @param	string	settings group (config filename/database group)
	* @param	string	name of the settings to return. optional if not included all settings for the matched group returned
	* @return mixed
	*/
	public function setting($group = null, $name = null, $default = null) {
		if (!isset($this->settings)) {
			/* let's make sure the model is loaded */
			$this->model('o_setting_model');

			/* let's make sure the cache is loaded */
			$this->driver('cache', ['adapter' => ci()->config->item('cache_default'), 'backup' => ci()->config->item('cache_backup')]);

			/* set the page request cached settings */
			if (!$this->settings = ci()->cache->get($this->cache)) {
				/* setup the empty array and load'em */
				$this->settings = [];

				/* get all file configs */
				$config_files = glob(ROOTPATH.'/application/config/*.php');

				foreach ($config_files as $file) {
					$config = null;

					require $file;
					
					if (is_array($config)) {
						$this->settings[basename($file,'.php')] = $config;
					}
				}

				/* get environment configuration - if it's set */
				if (ENVIRONMENT) {
					$config_files = glob(ROOTPATH.'/application/config/'.ENVIRONMENT.'/*.php');

					foreach ($config_files as $file) {
						$config = null;

						require $file;

						if (is_array($config)) {
							$filename = basename($file,'.php');
	
							$this->settings[$filename] = array_merge_recursive($this->settings[$filename],$config);
						}
					}
				}

				/* get all database "settings" */
				$db_array = ci()->o_setting_model->get_many_by(['enabled'=>1]);

				if (is_array($db_array)) {
					foreach ($db_array as $record) {
						/* let's make sure a boolean is a boolean and a integer is a integer etc... */
						$this->settings[$record->group][$record->name] = convert_to_real($record->value);
					}
				}

				ci()->cache->save($this->cache,$this->settings,86400); /* 1 day */
			}
		}

		$rtn = $default;

		if ($name === null && isset($this->settings[$group])) {
			$rtn = $this->settings[$group];
		} elseif (isset($this->settings[$group][$name])) {
			$rtn = $this->settings[$group][$name];
		}

		return $rtn;
	}

	public function settings_flush() {
		return ci()->cache->delete($this->cache);
	}

	/**
	* Partial
	* New Function
	* load a template (always returned) optional load into view variable
	*
	* @param	string	name of the view file to load
	* @param	array		data to merge with the view
	* @param	string	view varable to place the content into
	* @return	mixed		if name supplied reference to loader ($this). if name not supplied the partial contents
	*/
	public function partial($view, $data = [], $name = null) {
		log_message('debug', 'my_loader::partial '.$view);

		/* normal load view and return content */
		$partial = $this->view($view, $data, true);

		if ($name) {
			$this->_ci_cached_vars[$name] = $partial;

			return $this;
		}

		return $partial;
	}

	public function library_exists($file = null) {
		/* this would be in the libraries folder */
		$file = str_replace('.php', '', trim($file, '/'));

		/* prepare with uppercase 1st letter in the library file name */
		$file = 'libraries/'.dirname($file).'/'.ucfirst(strtolower(basename($file))).'.php';

		log_message('debug', 'my_loader::library_exists '.$file);

		return stream_resolve_include_path($file);
	}

	/**
	* Add Theme Path
	* New Function
	* Add a theme path to the search array & include array
	* NOTE:
	* use the page->theme() function not this function directly
	* (unless you know what you are doing!)
	*
	* @param	string	package path to add
	* @return	object	reference to loader to allow chaining
	*/
	public function theme($path) {
		log_message('debug', 'my_loader::theme '.$path);

		$raw_path = $path;

		$path = realpath(rtrim($path,'/'));

		if ($path === FALSE) {
			show_error('The theme package path you added is not valid "'.$raw_path.'"');
		}

		$this->current_theme = $path.'/';

		return $this;
	}

	/**
	* Add Package Path
	* OVERRIDES PARENT
	* Add a package path to the search array & include array
	*
	* @param	string	package path to add
	* @param	bool		weither to also add it to the view cascading
	*/
	public function add_package_path($path, $view_cascade = TRUE) {
		log_message('debug', 'my_loader::add_package_path '.$path);

		$package_path = realpath($path);

		/* if the package path is empty then it's no good */
		if ($package_path === false) {
			echo 'Setup Failed - Package Not Found: "'.$path.'".';
			exit;
		}

		$package_path = $package_path.'/';

		if (!in_array($package_path,$this->added_paths)) {
			/* prepend new package in front of the others new search path style */
			add_include_path($path);

			/* add it to the array of installed packages */
			$this->added_paths[$package_path] = $package_path;
			$this->added_paths_view[$package_path.'views/'] = $view_cascade;

			/* get ref to config class */
			$config = & $this->_ci_get_component('config');

			$paths = array_merge((array)APPPATH,$this->added_paths,(array)BASEPATH);

			$config->_config_paths   = $paths;

			$this->_ci_library_paths = $paths;
			$this->_ci_helper_paths  = $paths;
			$this->_ci_model_paths   = $paths;

			$this->_ci_view_paths    = array_merge([APPPATH.'views/'=>true],$this->added_paths_view);
		}

		return $this;
	}

	/**
	* Debug
	* New Function used to display the search pathes for debugging purposes
	*
	* @return array
	*/
	public function debug($which=null) {
		$config = & $this->_ci_get_component('config');

		$data['configs']   = $config->_config_paths;
		$data['libraries'] = $this->_ci_library_paths;
		$data['helpers']   = $this->_ci_helper_paths;
		$data['models']    = $this->_ci_model_paths;
		$data['views']     = $this->_ci_view_paths;
		$data['variables'] = $this->_ci_cached_vars;

		return ($which) ? $data[$which] : $data;
	}

	public function create_onload() {
		/* we need the packages model to figure out which are active */
		$this->model('o_packages_model');

		/* let's load the active packages in order */
		$records = ci()->o_packages_model->active();

		$n = chr(10);

		/* our cache files starts with */
		$combined  = '<?php'.$n;
		$combined .= '/*'.$n;
		$combined .= 'DO NOT MODIFY THIS FILE'.$n;
		$combined .= 'THIS FILE IS MANAGED COMPLETELY BY THE FRAMEWORK'.$n;
		$combined .= 'This file is all of the onloads in a single file to make attaching them a single action'.$n;
		$combined .= 'This file is automatically rebuild by the package manager as needed'.$n;
		$combined .= '*/'.$n.$n;

		foreach ($records as $p) {
			$package_folder = ROOTPATH.'/'.$p->full_path;

			if (file_exists($package_folder.'/support/onload.php')) {
				$combined .= str_replace('<?php','/* --> '.$p->full_path.' <-- */',trim(file_get_contents($package_folder.'/support/onload.php'))).$n.$n;
			}
		}

		atomic_file_put_contents($this->onload_path,$combined);

		/* force flush opcached filed if exists */
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($this->onload_path,true);
		}

		return $this;
	}

	public function delete_onload() {
		$return = true;

		if (file_exists($this->onload_path)) {
			$return = unlink($this->onload_path);
		}

		/* force flush opcached filed if exists */
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($this->onload_path,true);
		}

		return $return;
	}

} /* end class */