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

	/* is $_classes empty? if so it's the first time here add the packages to the search path */
	if (count($_classes) == 0) {
		include APPPATH.'config/autoload.php';

		if (file_exists(APPPATH.'config/'.CONFIG.'/autoload.php')) {
			include APPPATH.'config/'.CONFIG.'/autoload.php';
		}

		/* add packages */
		foreach ($autoload['packages'] as $package) {
			add_include_path($package);
		}
	}

	// Does the class exist? If so, we're done...
	if (isset($_classes[$class])) {
		return $_classes[$class];
	}

	$name = false;

	/* is this a core CI_ class? these are only in the system "basepath" folder */
	if (file_exists(BASEPATH.$directory.'/'.$class.'.php')) {
		$name = 'CI_'.$class;

		if (class_exists($name, false) === false) {
			require BASEPATH.$directory.'/'.$class.'.php';
		}
	}

	/* is this a orange extended class? these are only in the orange package folder */
	if (file_exists(ROOTPATH.'/vendor/orange/orange/'.$directory.'/'.config_item('subclass_prefix').$class.'.php')) {
		$name = config_item('subclass_prefix').$class;

		if (class_exists($name, false) === false) {
			require_once ROOTPATH.'/vendor/orange/orange/'.$directory.'/'.$name.'.php';
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
		include_once ROOTPATH.'/vendor/orange/orange/models/Database_model.php';

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

/* save these for later before we modify it */
define(ROOTPATHS,get_include_path());

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
	static $ADDED_PATHS, $ATTACHED_PACKAGES;

	$package_path = realpath($path);

	/* if the package path is empty then it's no good */
	if ($package_path === false) {
		echo 'Setup Failed - Package Not Found: "'.$path.'".';
		exit;
	}

	$package_path = $package_path.'/';

	/* is it already in the search path? */
	if (!in_array($package_path,(array)$ATTACHED_PACKAGES)) {
		$ATTACHED_PACKAGES[] = $package_path;
				
		$ADDED_PATHS .= PATH_SEPARATOR.$package_path;

		/* set our new include search path */
		set_include_path(ROOTPATHS.PATH_SEPARATOR.APPPATH.$ADDED_PATHS.PATH_SEPARATOR.BASEPATH);
	}
}

/**
* Remove Search Path
* New Function
* Low level function to remove already included path
*
* @param	string	include search path to remove
*/
function remove_include_path($path = '') {
	static $ADDED_PATHS;

	$package_path = realpath($path).'/';

	/* clean it if it's sent */
	$ADDED_PATHS = str_replace(PATH_SEPARATOR.$package_path,'',$ADDED_PATHS);

	/* set our new include search path */
	set_include_path(ROOTPATHS.PATH_SEPARATOR.APPPATH.$ADDED_PATHS.PATH_SEPARATOR.BASEPATH);
}

function split_dsn($dsntxt) {
	/* $dsn = "<driver>://<username>:<password>@<host>:<port>/<database>"; */

	$parts = explode(':',$dsntxt);

	$dsn['driver'] = $parts[0];
	$dsn['username'] = substr($parts[1],2);
	$dsn['user'] = $dsn['username'];
	
	$parts2 = explode('@',$parts[2]);

	$dsn['password'] = $parts2[0];
	$dsn['host'] = $parts2[1];
	$dsn['server'] = $dsn['host'];

	$parts3 = explode('/',$parts[3]);
	$dsn['post'] = $parts3[0];
	$dsn['database'] = $parts3[1];
	
	$dsn['short'] = $dsn['driver'].':dbname='.$dsn['database'].';host='.$dsn['host'].';port='.$dsn['post'];

	return $dsn;
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
		atomic_file_put_contents($filename,'<?php return '.var_export($data,true).';');
	} else {
		/* read */
		return (file_exists($filename)) ? include $filename : false;
	}
}

function atomic_file_put_contents($filepath,$content) {
	$tmpfname = tempnam(dirname($filepath),'temp');
	file_put_contents($tmpfname,$content);
	return rename($tmpfname,$filepath); /* atomic */
}

function opcache_flush($filename) {
	$success = true;

	/* force flush opcached filed if exists */
	if (function_exists('opcache_invalidate')) {
		//opcache_invalidate($filename,true); /* this seems to blow up the session / http request? */
		$success = opcache_compile_file($filename); /* so let's try this! */
	}
	
	return $success;
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