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

class Module_core {
	protected $modules_file;
	protected $composer_file;
	protected $composer_packages = [];
	protected $root;
	protected $apppath;
	protected $where; /* where installed */
	protected $routes = [];
	protected $migration_folder = 'support/migrations'; /* no trailing slash */
	protected $helper;

	public $migration_table = 'orange_modules';
	public $default_record = ['migration_version'=>'0.0.0','human_name'=>'','folder_name'=>'','current_version'=>'0.0.0'];
	public $active_modules = [];

	public $modules = [];

	public function __construct($rootpath=null,$apppath=null) {
		ci()->load->library('Module_helper');

		$this->helper = ci()->module_helper->init($this);

		return $this->init($rootpath=null,$apppath=null);
	}

	public function init($rootpath=null,$apppath=null) {
		$this->root = ($rootpath) ? $rootpath : ROOTPATH;
		$this->apppath = ($apppath) ? $apppath : APPPATH;

		$this->modules_file = $this->apppath.'config/modules.php';
		$this->composer_file = $this->root.'/composer.json';
		$this->modules_json = $this->root.'/modules.json';

		if (!file_exists($this->modules_file)) {
			return 'Configuration File Missing?';
		}

		/* load our module config */
		include $this->modules_file;
		
		/* what are the active modules */
		foreach ($autoload['active'] as $package) {
			$this->active_modules[$package] = basename($package);
		}

		/* get the composer installed packages */
		if (file_exists($this->composer_file)) {
			$composer = json_decode(file_get_contents($this->composer_file));
			$this->composer_packages = (array)$composer->require;
		}

		/* get all of our modules details */
		$this->modules = $this->get_configs();

		/* is the modules.php config file there and writable */
		if (!is_writable($this->modules_file)) {
			$this->modules['_messages'][] = 'config/modules.php not read / writeable.';
		}

		/* is the upload_temp folder there and writable */
		if (!is_writable($this->root.'/var/upload_temp')) {
			$this->modules['_messages'][] = '/var/upload_temp not read / writeable.';
		}
		
		/* is the modules folder there and writable */
		if (!is_writable($this->root.'/modules')) {
			$this->modules['_messages'][] = '/modules not read / writeable.';
		}

		return true;
	}

	public function get_modules_config() {
		/* load the file which contains an array $autoload[] */
		include $this->modules_file;

		return $autoload;
	}

	public function details() {
		/* custom sort on class name */
		uasort($this->modules, function($a,$b) {
			return ($a['classname'] < $b['classname']) ? -1 : 1;
		});

		return $this->modules;
	}

	/* Internal - Protected */

	protected function get_configs($folder='') {
		$modules = [];

		/* any new or modules that need updating */
		$module_folders = glob($this->root.'/modules/'.$folder.'*',GLOB_ONLYDIR);

		foreach ($module_folders as $m) {
			$filename = $m.'/install_'.basename($m).'.php';

			/* is the installer file there? */
			if (file_exists($filename)) {
				$config = $this->helper->config_magic($filename);
				$modules[$config['name']] = $config;
			}
		}

		return $modules;
	}

} /* end class */