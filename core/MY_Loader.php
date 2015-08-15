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
	public $orange_extended_helpers = ['array','date','directory','file','string'];
	public $settings = null; /* local per request storage */
	protected $merged_settings_cache_key = 'loader.settings';
	protected $cache_ttl;

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

			$this->cache_ttl = ci()->config->item('cache_ttl');

			/* set the page request cached settings */

			if (!$this->settings = ci()->cache->get($this->merged_settings_cache_key)) {
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
						$this->settings[$record->group][$record->name] = $this->_format_setting($record->value);
					}
				}

				$this->settings['config']['cache_ttl'] = $this->cache_ttl;

				ci()->cache->save($this->merged_settings_cache_key,$this->settings,$this->cache_ttl);
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

	protected function _format_setting($value) {
		/* is it JSON? if not this will return null */
		$is_json = @json_decode($value, true);

		if ($is_json !== null) {
			$value = $is_json;
		} else {
			switch(trim(strtolower($value))) {
				case 'true':
					$value = true;
				break;
				case 'false':
					$value = false;
				break;
				case 'null':
					$value = null;
				break;
				default:
					if (is_numeric($value)) {
						$value = (is_float($value)) ? (float)$value : (int)$value;
					}
			}
		}

		return $value;
	}

	public function settings_flush() {
		/* master */
		return ci()->cache->delete($this->merged_settings_cache_key);
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
	public function add_package_path($path, $append = true) {
		log_message('debug', 'my_loader::add_package_path '.$path.' '.(boolean)$append);

		/*
		prepend new package in front of the others
		new search path style
		*/
		add_include_path($path, $append);

		/* get ref to config class */
		$config = & $this->_ci_get_component('config');

		$paths = explode(PATH_SEPARATOR, get_include_path());

		/* older ci style */
		$this->_ci_view_paths = [];

		foreach ($paths as $p) {
			$this->_ci_view_paths[rtrim($p, '/').'/views/'] = true;
		}

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

} /* end class */