<?php
/**
* This naming works because of the naming
* my changes put on the controller and models
* (suffixes of Controller or _model)
*/
function codeigniter_autoload($class) {
	if ($file = stream_resolve_include_path($class.'.php')) { /* is it on any of the include pathes? */
		include_once $file;

		return true;
	} elseif ($class == 'Database_model') { /* abstract class */
		include_once ROOTPATH.'/packages/orange/models/Database_model.php';

		return true;
	} elseif (substr($class, -6) == '_model') { /* is it a CI model? */
		if (stream_resolve_include_path('models/'.$class.'.php')) {
			ci()->load->model($class);

			return true;
		}
	} elseif (substr($class, -10) == 'Controller') { /* is it a CI Controller? */
		if ($file = stream_resolve_include_path('controllers/'.$class.'.php')) {
			include $file;

			return true;
		}
	} elseif ($file = stream_resolve_include_path('libraries/'.$class.'.php')) {
		ci()->load->library($class);

		return true;
	}

	/* beat's me let the next autoloader give it a shot */
	return false;
}

/* register loader */
spl_autoload_register('codeigniter_autoload');

/* NEW - shorter syntax */
function &ci() {
	return CI_Controller::get_instance();
}

/**
* Include If Exists
* New Function
* Include a file without throwing an error
* this is pretty low level and "direct"
* The more generic function is the loaders "find"
* method. it will locate the file and autoload it if needed
*
* @param		string	$file	path to search the include directories for
* @return		mixed		false on failed to locate the absolute path (string) on file located
*/
function include_if_exists($file) {
	/* stream resolve will search include paths (in code) for a needed class */
	if ($file = stream_resolve_include_path($file)) {
		include_once $file;
	}

	return $file;
}

/**
* Add Search Path
* New Function
* Low level function to add to include path
*
* @param	string	include search path to add
* @param	bool		option to prepend the path default append
*/
function add_include_path($path, $prepend = false) {
	static $ROOT_PATHS, $ADDED_PATHS, $THEME_PATH, $APPLICATION_PATH;

	/* if they sent in an array handle it */
	if (is_array($path)) {
		foreach ($path as $path) {
			add_include_path($path, $prepend);
		}

		return;
	}

	/* clean up our package path */
	$package_path = rtrim(realpath($path), '/').'/';

	/* if the package path is empty then it's no good */
	if ($package_path === '/' && CONFIG !== 'production') {
		die('Setup Failed - Package Not Found: "'.$path.'". Check your ENV folders.');
	}

	/*
	save a copy of the root paths
	so we can append after these and
	before anything already added as needed
	*/
	if (!isset($ROOT_PATHS)) {
		$ROOT_PATHS  = get_include_path();
		$ADDED_PATHS = [];
		/* application path is always first */
		$APPLICATION_PATH = $path;
	} elseif (strpos($path,'theme_') !== false) { /* does it contain the theme_ package prefix? */
		/* there can be only 1 */
		$THEME_PATH = $path;
	} else {
		if ($prepend) {
			/* append before what we currently have */
			$ADDED_PATHS = [$package_path => $package_path] + $ADDED_PATHS;
		} else {
			/* prepend to what we have */
			$ADDED_PATHS[$package_path] = $package_path;
		}
	}

	/*
	set our new include search path
	root, theme, application, packages
	*/
	set_include_path($ROOT_PATHS.PATH_SEPARATOR.$THEME_PATH.PATH_SEPARATOR.$APPLICATION_PATH.PATH_SEPARATOR.implode(PATH_SEPARATOR, $ADDED_PATHS));
}

/**
* Remove Search Path
* New Function
* Low level function to remove already included path
*
* @param	string	include search path to remove
*/
function remove_include_path($path = '') {
	static $ROOT_PATHS, $ADDED_PATHS;

	/* clean it if it's sent */
	unset($ADDED_PATHS[rtrim(realpath($path), '/').'/']);

	/* set our new include search path */
	set_include_path($ROOT_PATHS.PATH_SEPARATOR.implode(PATH_SEPARATOR, (array) $ADDED_PATHS));
}

/**
* Class registry
*
* This function acts as a singleton.  If the requested class does not
* exist it is instantiated and set to a static variable.  If it has
* previously been instantiated the variable is returned.
* only use for app, orange, base files
*
* @access	public
* @param	string	the class name being requested
* @param	string	the directory where the class should be found
* @param	string	the class name prefix
* @return	object
*
* OVERRIDDEN because the include_if_exists will search all of our include paths
* Not just APPPATH and BASEPATH
*
*/
function &load_class($class, $directory = 'libraries', $param = NULL) {
	static $_classes = array();

	/* is $_classes empty? if so it's the first time here add the packages to the search path */
	if (count($_classes) == 0) {
		include APPPATH.'config/autoload.php';

		if (file_exists(APPPATH.'config/'.CONFIG.'/autoload.php')) {
			include APPPATH.'config/'.CONFIG.'/autoload.php';
		}

		/* add application, packages, base */
		add_include_path(APPPATH);
		add_include_path($autoload['packages']);
		add_include_path(BASEPATH);
	}

	// Does the class exist? If so, we're done...
	if (isset($_classes[$class])) {
		return $_classes[$class];
	}

	$name = false;

	// Look for the class first in the local application/libraries folder
	// then in the native system/libraries folder
	$folders = explode(':',get_include_path());

	foreach ($folders as $idx=>$path) {
		$path = $folders[$idx] = rtrim($path,'/').'/';

		if (file_exists($path.$directory.'/'.$class.'.php')) {
			$name = 'CI_'.$class;

			if (class_exists($name, false) === false) {
				require $path.$directory.'/'.$class.'.php';
			}

			break;
		}
	}

	// Is the request a class extension? If so we load it too
	foreach ($folders as $path) {
		if (file_exists($path.$directory.'/'.config_item('subclass_prefix').$class.'.php')) {
			$name = config_item('subclass_prefix').$class;

			if (class_exists($name, false) === false) {
				require_once $path.$directory.'/'.$name.'.php';
			}
		}
	}

	// Did we find the class?
	if ($name === false) {
		// Note: We use exit() rather then show_error() in order to avoid a
		// self-referencing loop with the Exceptions class
		set_status_header(503);
		echo 'Unable to locate the specified class: '.$class.'.php';
		exit;
	}

	// Keep track of what we just loaded
	is_loaded($class);

	$_classes[$class] = isset($param) ? new $name($param) : new $name();

	return $_classes[$class];
}

function capture($_mvc_view_file,$_mvc_view_data=[]) {
	if (file_exists($_mvc_view_file)) {
		extract($_mvc_view_data);
		ob_start();

		require $_mvc_view_file;

		return ob_get_clean();
	}
}

/*
Since settings and auth are core
we will make a few global functions
to make them easier to access
*/
function setting($group,$key=null,$default=null) {
	return ci()->load->setting($group,$key,$default);
}

function has_access($access=null) {
	return (empty($access)) ? false : ci()->auth->has_access($access);
}

function console($var,$type='log') {
	echo '<script type="text/javascript">console.'.$type.'('.json_encode($var).')</script>';
}

function array_cache($filename=null,$data=null) {
	if (is_array($data) && $filename) {
		/* write */
		$tmpfname = tempnam(dirname($filename),'temp');
		file_put_contents($tmpfname,'<?php return '.var_export($data,true).';');
		rename($tmpfname,$filename); /* atomic */
	} else {
		/* read */
		return (file_exists($filename)) ? require $filename : false;
	}
}

/* convet to real value from string */
function convert_to_real($value) {
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

function convert_to_string($value) {
	if (is_array($value)) {
		return var_export($value,true);
	}
	
	if ($value === true) {
		return 'true';
	}

	if ($value === false) {
		return 'false';
	}
	
	return (string)$value;
}
