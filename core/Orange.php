<?php
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
	static $THEME_PATHS, $ADDED_PATHS;
	
	$cache_file = ROOTPATH.'/var/cache/packages_cache.php';

	/* load the codeigniter autoload */
	include APPPATH.'config/autoload.php';

	/* add packages - this loads the default packages */
	foreach ($autoload['packages'] as $package) {
		add_include_path($package);
	}

	/* Does the class exist? If so, we're done... */
	if (isset($_classes[$class])) {
		return $_classes[$class];
	}

	$name = false;

	/* Look for the class in the native system/libraries folder */
	if (file_exists(BASEPATH.$directory.'/'.$class.'.php')) {
		$name = 'CI_'.$class;

		if (class_exists($name, FALSE) === FALSE) {
			require_once(BASEPATH.$directory.'/'.$class.'.php');
		}
	}

	/* Is the request a class extension? If so we load it too */
	if (file_exists(ROOTPATH.'/packages/orange/'.$directory.'/'.config_item('subclass_prefix').$class.'.php')) {
		$name = config_item('subclass_prefix').$class;

		if (class_exists($name, FALSE) === FALSE) {
			require_once(ROOTPATH.'/packages/orange/'.$directory.'/'.config_item('subclass_prefix').$class.'.php');
		}
	}

	// Did we find the class?
	if ($name === false) {
		/*
		Note: We use exit() rather then show_error() in order to avoid a
		self-referencing loop with the Exceptions class
		*/
		set_status_header(503);
		echo 'Unable to locate the specified class: '.$class.'.php';
		exit;
	}

	/* Keep track of what we just loaded */
	is_loaded($class);

	$_classes[$class] = isset($param) ? new $name($param) : new $name();

	return $_classes[$class];
}

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

/* Put the current php include paths in a constant so we don't lose them or accidentally change them! */
define('ROOT_PATHS',get_include_path());

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
function add_include_path($path) {
	static $THEME_PATHS, $ADDED_PATHS;

	/* clean up our package path */
	$package_path = rtrim(realpath($path), '/').'/';

	/* if the package path is empty then it's no good */
	if ($package_path === '/' && CONFIG !== 'production') {
		die('Setup Failed - Package Not Found: "'.$path.'". Check your ENV folders.');
	}

	$php_search = ROOT_PATHS.$THEME_PATHS.PATH_SEPARATOR.APPPATH.$ADDED_PATHS.PATH_SEPARATOR.BASEPATH;

	/* is it already in the search path? */
	if (strpos($ADDED_PATHS,$php_search) !== false) {
		return explode(PATH_SEPARATOR,$php_search);
	}

	/* is this a theme package or a normal package?	*/
	if (strpos($package_path,'theme_') !== false) {
		/* does it contain the theme_ package prefix? if so then add it to the themes package */
		$THEME_PATHS  = PATH_SEPARATOR.$package_path;
	} else {
		$ADDED_PATHS .= PATH_SEPARATOR.$package_path;
	}

	/* build our php path - set our new include search path root, theme, application, packages */
	$php_search = ROOT_PATHS.$THEME_PATHS.PATH_SEPARATOR.APPPATH.$ADDED_PATHS.PATH_SEPARATOR.BASEPATH;

	/* set our new include search path */
	set_include_path($php_search);

	/* return the entire include path array */
	return explode(PATH_SEPARATOR,$php_search);
}

/**
* Remove Search Path
* New Function
* Low level function to remove already included path
*
* @param	string	include search path to remove
*/
function remove_include_path($path = '') {
	static $THEME_PATHS, $ADDED_PATHS;

	/* clean up our package path */
	$package_path = rtrim(realpath($path), '/').'/';

	/* build our php path and remove if it's present */
	$php_search = str_replace(PATH_SEPARATOR.$package_path,'',ROOT_PATHS.$THEME_PATHS.PATH_SEPARATOR.APPPATH.$ADDED_PATHS.PATH_SEPARATOR.BASEPATH);

	/* set our new include search path */
	set_include_path($php_search);

	/* return the entire include path array */
	return explode(PATH_SEPARATOR,$php_search);
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

/* export as php array for super fast loading */
function array_cache($filename=null,$data=null) {
	if (is_array($data) && $filename) {
		/* write */
		$tmpfname = tempnam(dirname($filename),'temp');
		file_put_contents($tmpfname,'<?php'.chr(10).'return '.var_export($data,true).';');
		rename($tmpfname,$filename); /* atomic */

		/* invalidate the cached item if opcache is on */
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename,true);
		}
	} else {
		/* read */
		return (file_exists($filename)) ? include $filename : false;
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