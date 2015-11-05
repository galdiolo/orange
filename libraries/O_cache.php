<?php
/**
 * Cache Class
 *
 * Partial Caching library for CodeIgniter
 *
 * @category	Libraries
 * @author		Phil Sturgeon, Heavily Modified by Don Myers
 * @link		http://philsturgeon.co.uk/code/codeigniter-cache
 * @license		MIT
 * @version		2.1
 */

/*
Uncached model call
$this->blog_m->getPosts($category_id, 'live');

cached model call
$this->cache->model('blog_m', 'getPosts', array($category_id, 'live'), 120); // keep for 2 minutes

cached library call
$this->cache->library('some_library', 'calcualte_something', array($foo, $bar, $bla)); // keep for default time (0 = unlimited)

cached array or object
$this->cache->write($data, 'cached-name');
$data = $this->cache->get('cached-name');

Delete cache
$this->cache->delete('cached-name');

Delete all cache
$this->cache->delete_all();

Delete cache group
$this->cache->write($data, 'nav_header');
$this->cache->write($data, 'nav_footer');
$this->cache->delete_group('nav_');

Delete cache item
Call like a normal library or model but give a negative $expire
delete this specific cache file

$this->cache->model('blog_m', 'getPosts', array($category_id, 'live'), -1);

add cache dependencies array of additional calls
$this->cache->add_dependencies(['Categories_model'=>['method',['user_name','name']]])->model('blog_m', 'getPosts', array($category_id, 'live'), 3600);

*/

class O_cache {
	protected $ci;
	protected $ci_load;
	protected $_path;
	protected $_contents;
	protected $_filename;
	protected $_expires;
	protected $_default_expires;
	protected $_created;
	protected $_dependencies;

	/**
	 * Constructor - Initializes and references CI
	 */
	public function __construct() {
		log_message('debug', 'Orange Cache Class Initialized.');

		$this->ci = &ci();
		$this->ci_load = &$this->ci->load;

		$this->_reset();

		/* cache is loaded in loader::setting */

		$this->_path = setting('config','cache_path');
		$this->_default_expires = setting('config','cache_ttl');
	}

	/**
	 * Call a library's cached result or create new cache
	 *
	 * @access	public
	 * @param	string
	 * @return	array
	 */
	public function library($library, $method, $arguments = [], $expires = NULL) {
		$library_name = basename($library);

		if (!class_exists(ucfirst($library_name))) {
			$this->ci_load->library($library);
		}

		return $this->_call($library_name, $method, $arguments, $expires);
	}

	/**
	 * Call a model's cached result or create new cache
	 *
	 * @access	public
	 * @return	array
	 */
	public function model($model, $method, $arguments = [], $expires = NULL) {
		$model_name = basename($model);

		if (!class_exists(ucfirst($model_name))) {
			$this->ci_load->model($model);
		}

		return $this->_call($model_name, $method, $arguments, $expires);
	}

	/**
	 * Helper functions for the dependencies property
	 */
	public function set_dependencies($dependencies) {
		if (is_array($dependencies)) {
			$this->_dependencies = $dependencies;
		} else {
			$this->_dependencies = array($dependencies);
		}

		return $this;
	}

	public function add_dependencies($dependencies) {
		if (is_array($dependencies)) {
			$this->_dependencies = array_merge($this->_dependencies, $dependencies);
		} else {
			$this->_dependencies[] = $dependencies;
		}

		return $this;
	}

	public function get_dependencies() {
		return $this->_dependencies;
	}

	/**
	 * Helper function to get the cache creation date
	 */
	public function get_created($created) {
		return $this->_created;
	}

	/**
	 * Retrieve Cache File
	 *
	 * @access	public
	 * @param	string
	 * @param	boolean
	 * @return	mixed
	 */
	public function get($filename = NULL, $use_expires = true) {
		/* Check if cache was requested with the function or uses this object */
		if ($filename !== NULL) {
			$this->_reset();
			$this->_filename = $filename;
		}

		/* Check directory permissions */
		if (!is_dir($this->_path) OR ! is_really_writable($this->_path)) {
			return FALSE;
		}

		/* Build the file path */
		$filepath = $this->_path.strtolower($this->_filename).'.cache';

		/* Check if the cache exists, if not return FALSE */
		if (!@file_exists($filepath)) {
			return FALSE;
		}

		/* Check if the cache can be opened, if not return FALSE */
		if (!$fp = @fopen($filepath, FOPEN_READ)) {
			return FALSE;
		}

		/* Lock the cache */
		flock($fp, LOCK_SH);

		/* If the file contains data return it, otherwise return NULL */
		if (filesize($filepath) > 0) {
			$this->_contents = unserialize(fread($fp, filesize($filepath)));
		} else {
			$this->_contents = NULL;
		}

		/* Unlock the cache and close the file */
		flock($fp, LOCK_UN);
		fclose($fp);

		/* Check cache expiration, delete and return FALSE when expired */
		if ($use_expires && !empty($this->_contents['__cache_expires']) && $this->_contents['__cache_expires'] < time()) {
			$this->delete($filepath);
			return FALSE;
		}

		/* Check Cache dependencies */
		if (isset($this->_contents['__cache_dependencies'])) {

			foreach ($this->_contents['__cache_dependencies'] as $dep) {
				$cache_created = filemtime($filepath);

				/* If dependency doesn't exist or is newer than this cache, delete and return FALSE */
				if (!file_exists($this->_path.$dep.'.cache') or filemtime($this->_path.$dep.'.cache') > $cache_created) {
					log_message('debug', 'Missing or too new dependency cache file '.$this->_path.$dep.'.cache');

					/* delete this cache file since it's not valid */
					$this->delete($filepath);

					return FALSE;
				}
			}
		}

		/* Instantiate the object variables */
		$this->_expires		= isset($this->_contents['__cache_expires']) ? $this->_contents['__cache_expires'] : NULL;
		$this->_dependencies = isset($this->_contents['__cache_dependencies']) ? $this->_contents['__cache_dependencies'] : NULL;
		$this->_created		= isset($this->_contents['__cache_created']) ? $this->_contents['__cache_created'] : NULL;

		/* Cleanup the meta variables from the contents */
		$this->_contents = @$this->_contents['__cache_contents'];

		/* Return the cache */
		log_message('debug', 'Cache retrieved: '.$filepath);

		return $this->_contents;
	}

	/**
	 * Write Cache File
	 *
	 * @access	public
	 * @param	mixed
	 * @param	string
	 * @param	int
	 * @param	array
	 * @return	void
	 */
	public function write($contents = NULL, $filename = NULL, $expires = NULL, $dependencies = []) {
		/* Check if cache was passed with the function or uses this object */
		if ($contents !== NULL) {
			$this->_reset();
			$this->_contents = $contents;
			$this->_filename = $filename;
			$this->_expires = $expires;
			$this->_dependencies = $dependencies;
		}

		/* Put the contents in an array so additional meta variables */
		/* can be easily removed from the output */
		$this->_contents = array('__cache_contents' => $this->_contents);

		/* Check directory permissions */
		if (!is_dir($this->_path) OR ! is_really_writable($this->_path)) {
			return;
		}

		/* check if filename contains dirs */
		$subdirs = explode(DIRECTORY_SEPARATOR, $this->_filename);

		if (count($subdirs) > 1) {
			array_pop($subdirs);
			$test_path = $this->_path.implode(DIRECTORY_SEPARATOR, $subdirs);

			/* check if specified subdir exists */
			if (!@file_exists($test_path)) {
				/* create non existing dirs, asumes PHP5 */
				if (!@mkdir($test_path, DIR_WRITE_MODE, TRUE)) {
					return FALSE;
				}
			}
		}

		/* Set the path to the cachefile which is to be created */
		$cache_path = $this->_path.strtolower($this->_filename).'.cache';

		/* Open the file and log if an error occures */
		if (!$fp = @fopen($cache_path, FOPEN_WRITE_CREATE_DESTRUCTIVE)) {
			log_message('error', 'Unable to write Cache file: '.$cache_path);
			return;
		}

		/* Meta variables */
		$this->_contents['__cache_created'] = time();

		/* convert the dependencies into cache signature's */
		$this->_contents['__cache_dependencies'] = $this->convert_dependencies($this->_dependencies);

		/* Add expires variable if its set */
		if (!empty($this->_expires)) {
			$this->_contents['__cache_expires'] = $this->_expires + time();
		} elseif (!empty($this->_default_expires) ) {
			/* ...or add default expiration if its set */
			$this->_contents['__cache_expires'] = $this->_default_expires + time();
		}

		/* Lock the file before writing or log an error if it failes */
		if (flock($fp, LOCK_EX)) {
			fwrite($fp, serialize($this->_contents));
			flock($fp, LOCK_UN);
		} else {
			log_message('error', 'Cache was unable to secure a file lock for file at: '.$cache_path);
			return;
		}

		fclose($fp);
		@chmod($cache_path, DIR_WRITE_MODE);

		/* Log success */
		log_message('debug', 'Cache file written: '.$cache_path);

		/* Reset values */
		$this->_reset();
	}

	/**
	 * Delete Cache File
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function delete($filename = NULL) {
		if ($filename !== NULL) {
			$this->_filename = $filename;
		}

		$this->_filename = strtolower($this->_filename);

		$file_path = $this->_path.$this->_filename.'.cache';

		if (file_exists($file_path)) {
			unlink($file_path);
		}

		/* Reset values */
		$this->_reset();
	}

	/**
	 * Delete a group of cached files
	 *
	 * Allows you to pass a group to delete cache. Example:
	 *
	 * <code>
	 * $this->cache->write($data, 'nav_title');
	 * $this->cache->write($links, 'nav_links');
	 * $this->cache->delete_group('nav_');
	 * </code>
	 *
	 * @param 	string $group
	 * @return 	void
	 */
	public function delete_group($group = null) {
		if ($group === null) {
			return FALSE;
		}

		$this->ci_load->helper('directory');

		$group = strtolower($group);

		$map = directory_map($this->_path, TRUE);

		foreach ($map AS $file) {
			if (strpos($file, $group)  !== FALSE) {
				unlink($this->_path.$file);
			}
		}

		/* Reset values */
		$this->_reset();
	}

	/**
	 * Delete Full Cache or Cache subdir
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function delete_all($dirname = '') {
		if (empty($this->_path)) {
			return FALSE;
		}

		$this->ci_load->helper('file');

		$dirname = strtolower($dirname);

		if (file_exists($this->_path.$dirname)) {
			delete_files($this->_path.$dirname, TRUE);
		}

		/* Reset values */
		$this->_reset();
	}

	/**
	 * Initialize Cache object to empty
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _reset() {
		$this->_contents = NULL;
		$this->_filename = NULL;
		$this->_expires = NULL;
		$this->_created = NULL;
		$this->_dependencies = [];
	}

	protected function get_signature($object_name,$method,$arguments) {
		$this->ci_load->helper('security');

		return strtolower($object_name).DIRECTORY_SEPARATOR.do_hash($method.serialize($arguments), 'sha1');
	}

	/* convert dependencies into cache file signatures */
	protected function convert_dependencies($dependencies) {
		$new_array = [];

		foreach ($dependencies as $object_name=>$d) {
			$new_array[] = $this->get_signature($object_name,$d[0],$d[1]);
		}

		return $new_array;
	}

	/* Depreciated, use model() or library() */
	protected function _call($property, $method, $arguments = [], $expires = NULL) {

		if (!is_array($arguments)) {
			$arguments = (array)$arguments;
		}

		/* Clean given arguments to a 0-index array */
		$arguments = array_values($arguments);

		$cache_file = $this->get_signature($property,$method,$arguments);

		$added_dependencies = $this->_dependencies;

		/* See if we have this cached or delete if $expires is negative */
		if ($expires >= 0) {
			$cached_response = $this->get($cache_file);
		} else {
			$this->delete($cache_file);

			return;
		}

		/* Not FALSE? Return it */
		if ($cached_response !== FALSE && $cached_response !== NULL) {
			return $cached_response;
		} else {
			/* Call the model or library with the method provided and the same arguments */
			$new_response = call_user_func_array([$this->ci->$property, $method], $arguments);

			$this->write($new_response, $cache_file, $expires, $added_dependencies);

			return $new_response;
		}
	}

} /* End of Class */