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
	public $cache_file = ROOTPATH.'/var/cache/settings.php';
	public $onload_path = ROOTPATH.'/var/cache/onload.php';
	public $packages_cache_path = ROOTPATH.'/var/cache/packages_cache.php'; /* work in progress */

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

	public function presenter($object=null,$presenter='') {
		/* what is the presenter classes name */
		$classname = ucfirst($presenter).'_presenter';

		/* does it exist? don't try to load it! */
		if (!class_exists($classname,false)) {
			/* is it part of our include path? */
			$presenter_file = stream_resolve_include_path('presenters/'.$classname.'.php');

			/* nope couldn't find it raise a fatal error */
			if (!$presenter_file) {
				show_error('Presenter Not Found "'.$presenter.'"');
			}

			/* load this for later on each row */
			include $presenter_file;

			log_message('debug', "Presenter: Loaded '$classname'.");
		}

		/*
		if it's a array of objects return it as part of a Iterator
		if it's not then return it as the presenter classes only record
		*/
		return (is_array($object)) ? new Presenter_iterator($object,$classname) : new $classname($object);
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

			if (!$this->settings = array_cache($this->cache_file)) {
				/* setup the empty array and load'em */
				$this->settings = [];

				/* get all file configs */
				$config_files = glob(ROOTPATH.'/application/config/*.php');

				foreach ($config_files as $file) {
					$config = [];

					require $file;

					$this->settings[basename($file,'.php')] = $config;
				}

				/* get environment configuration - if it's set */
				if (CONFIG) {
					$config_files = glob(ROOTPATH.'/application/config/'.CONFIG.'/*.php');

					foreach ($config_files as $file) {
						$config = [];

						require $file;

						$filename = basename($file,'.php');

						$this->settings[$filename] = array_merge_recursive($this->settings[$filename],$config);
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

				array_cache($this->cache_file,$this->settings);
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
		$return = true;

		if (file_exists($this->cache_file)) {
			$return = unlink($this->cache_file);
		}

		/* force flush opcached filed if exists */
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($this->cache_file,true);
		}

		return $return;
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

		/* remove the current theme if any */
		remove_include_path($this->current_theme);

		$raw_path = $path;

		$path = realpath(rtrim($path,'/'));

		if ($path === FALSE) {
			show_error('The theme package path you added is not valid "'.$raw_path.'"');
		}

		$this->current_theme = $path.'/';

		/* prepend the new theme it's always first in the load order & update our paths */
		$this->add_package_path($this->current_theme);

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
	public function add_package_path($path, $view_cascade = true) {
		log_message('debug', 'my_loader::add_package_path '.$path);

		/* prepend new package in front of the others new search path style */
		$paths = add_include_path($path);

		/*
		we need to rebuild the view path each time because the file search order is very important
		ROOT_PATHS + $THEME_PATH + APPPATH + $ADDED_PATHS + BASEPATH
		*/
		$this->_ci_view_paths = [];

		/* older ci style paths */
		foreach ($paths as $path) {
			$this->_ci_view_paths[rtrim($path, '/').'/views/'] = $view_cascade;
		}

		/* get ref to config class */
		$config = & $this->_ci_get_component('config');

		/* set paths */
		$config->_config_paths   = $paths;

		$this->_ci_library_paths = $paths;
		$this->_ci_helper_paths  = $paths;
		$this->_ci_model_paths   = $paths;

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
		$combined = '<?php'.chr(10);

		/* let the packages do there start up thing */
		include APPPATH.'/config/autoload.php';

		foreach ((array)$autoload['packages'] as $package_onload_file) {
			if (file_exists($package_onload_file.'/support/onload.php')) {
				$combined .= str_replace('<?php','',file_get_contents($package_onload_file.'/support/onload.php')).chr(10);
			}
		}

		$tmpfname = tempnam(dirname($this->onload_path),'temp');
		file_put_contents($tmpfname,$combined);
		rename($tmpfname,$this->onload_path); /* atomic */

		/* force flush opcached filed if exists */
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($this->onload_path,true);
		}
	}

	public function onload_flush() {
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
	
	/* work in progress */
	public function create_autoload() {
		/* build the packages path cache file */

		/* load the database settings to determine which modules are active */
		include APPPATH.'/config/autoload.php';

		/*
			build an array of packages also load the info.json file to determine the load order
			make this a $array[$order][$name] = $name;
			if $order is empty make it 50 (order 1 - 100)
 		*/
		$packages_paths = [];
		
		/* add the root paths as level 100 (the higher = first) */
		foreach (explode(PATH_SEPARATOR,ROOT_PATHS) as $p) {
			$packages_paths[100][rtrim($p,'/')] = rtrim($p,'/');
		}
		
		/* add the packages if they don't have a priority then set it to 50 - middle of the road in loading priority */
		foreach ($autoload['packages'] as $p) {
			if (file_exists($p.'/info.json')) {
				$info = json_decode(file_get_contents($p.'/info.json'),true);

				$priority = (!empty($info['priority'])) ? $info['priority'] : 50;

				$packages_paths[$priority][$p] = $p;
			}
		}

		/* the application path comes before the default packages */
		$packages_paths[60][rtrim(APPPATH,'/')] = rtrim(APPPATH,'/');
		
		/* the basepath comes last after everything else */
		$packages_paths[10][rtrim(BASEPATH,'/')] = rtrim(BASEPATH,'/');

		krsort($packages_paths);

		$new_packages_path = [];
		$new_view_packages_path = [];

		/* build the path cache array */
		foreach ($packages_paths as $priority_records) {
			foreach ($priority_records as $path) {
				$php_path .= PATH_SEPARATOR.$path;
				$new_packages_path[$path] = $path.'/';
				$new_view_packages_path[rtrim($path, '/').'/views/'] = true;
			}
		}

		/* save it using array_cache($filename=null,$data=null) */
		$data = [
			'config'=>$new_packages_path,
			'libraries'=>$new_packages_path,
			'helpers'=>$new_packages_path,
			'models'=>$new_packages_path,
			'views'=>$new_view_packages_path,
			'php'=>trim($php_path,PATH_SEPARATOR),
		];

		/* write it out */
		array_cache($this->packages_cache_path,$data);
	}

	public function autoload_flush() {
		$return = true;

		if (file_exists($this->packages_cache_path)) {
			$return = unlink($this->packages_cache_path);
		}

		/* force flush opcached filed if exists */
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($this->packages_cache_path,true);
		}

		return $return;
	}

} /* end class */