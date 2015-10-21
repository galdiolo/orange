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

class MY_Router extends CI_Router {
	protected $name = 'orange';
	protected $package = ''; /* what package are we currently looking for a controller in? */

	/**
	* Set default controller
	*
	* @return void
	*
	* overridden because we need to add Controller and Action on controller name and method
	*
	*/
	protected function _set_default_controller() {
		if (empty($this->default_controller)) {
			show_error('Unable to determine what should be displayed. A default route has not been specified in the routing file.');
		}

		$segments = $this->controller_method($this->default_controller);

		$this->set_class($segments[0]);
		$this->set_method($segments[1]);

		// Assign routed segments, index starting from 1
		$this->uri->rsegments = [
			1 => $segments[0],
			2 => $segments[1],
		];

		log_message('debug', 'No URI present. Default controller set.');
	}

	/**
	* Validate request
	*
	* Attempts validate the URI request and determine the controller path.
	*
	* @param  array $segments URI segments
	* @return array URI segments
	*
	*	overridden because we handle multiple folder levels
	*
	*/
	public function _validate_request($segments) {
		/* http request method - this make the CI 3 method invalid */
		$request = isset($_SERVER['REQUEST_METHOD']) ? ucfirst(strtolower($_SERVER['REQUEST_METHOD'])) : 'Cli';

		/* append this to the Controller Name */
		$append = ($request == 'Cli') ? 'Cli' : '';

		/* only a file cache is supported because the normal CI cache isn't even loaded yet */
		$cache_file = ROOTPATH.'/var/local_file_cache/uri_'.md5(implode('',$segments).$request).'.php';

		/* get it from the cache? */
		if ($cached = array_cache($cache_file)) {
			$this->directory = $cached['directory'];
			$this->package = $cached['package'];

			return $cached['segments'];
		}

		/*
		we just need to see if it's there not load it
		we also ALWAYS convert - to _
		*/
		$search_path = explode(PATH_SEPARATOR, get_include_path());

		/* let's find that controller */
		foreach ($segments as $folder) {
			/* always convert - to _ */
			$segments[0] = str_replace('-', '_', $folder);

			foreach ($search_path as $path) {
				$path = rtrim($path,'/').'/';

				$this->package = str_replace(ROOTPATH.'/','',$path);

				$segments[1] = ((isset($segments[1])) ? str_replace('-', '_', $segments[1]) : 'index');

				if (file_exists($path.'controllers/'.$this->directory.ucfirst($segments[0]).$append.'Controller.php')) {
					if (!file_exists($path.'controllers/'.$this->directory.$segments[0].'/'.ucfirst($segments[1]).$append.'Controller.php')) {
						/* yes! then segment 0 is the controller */
						$segments[0] = ucfirst($segments[0]).$append.'Controller';

						/* make sure we have a method and add Action (along with the REST stuff) */
						$segments[1] .= (($request == 'Get') ? '' : $request).'Action';

						/* re-route codeigniter.php controller loading */
						if ($this->package != 'application') {
							$this->directory = '../../'.$this->package.'controllers/'.$this->directory;
						}

						array_cache($cache_file,['segments'=>$segments,'directory'=>$this->directory,'package'=>$this->package]);

						/* return the controller, method and anything else */
						return $segments;
					}
				}
			}

			/* nope! shift off the beginning as a folder level */
			$this->set_directory(array_shift($segments), true);
		}

		/*
		Not found we will patch on the error controller and action which is in our "name" folder
		If that's not found let CI handling it as any missing Controller
		*/
		/*
		ERROR controller in orange from folder
		$this->directory = '../../'.$this->name.'/controllers/';
		*/

		/* ERROR controller in application folder */
		$this->directory = '';

		return $this->controller_method($this->routes['404_override']);
	}

	public function fetch_directory() {
		/* strip out controller path re-routing */
		return ($this->package != '') ? substr($this->directory,strlen('../../'.$this->package.'controllers/')) : $this->directory;
	}

	protected function controller_method($input) {
		$segments[0] = $input;
		$segments[1] = 'index';

		/* These can only be top level controllers so does this include / to indicate a method? */
		if (strpos($input,'/') !== false) {
			$segments = explode('/',$input,2);
		}

		$segments[0] .= 'Controller';
		$segments[1] .= 'Action';

		return $segments;
	}

} /* end my router */