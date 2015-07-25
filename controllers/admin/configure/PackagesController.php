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

class packagesController extends APP_AdminController {
	public $controller = 'packages';
	public $controller_path = '/admin/configure/packages';
	public $controller_title = 'Package';
	public $controller_titles = 'Packages';
	public $libraries = ['package_migration','package_manager'];
	public $has_access = 'Orange::Manage Packages';
	public $type_map = [''=>'default','package'=>'danger','core'=>'warning','library'=>'success','libraries'=>'success','theme'=>'warning','module'=>'primary','plugin'=>'info','assets'=>'warning'];
	public $modules = [];

	public function indexAction($filter=null) {
		if ($filter) $this->input->is_valid('alpha',$filter);

		$this->page
			->data([
				'type_map'=>$this->type_map,
				'records'=>$this->package_manager->index(),
				'filter'=>$filter,
				'errors'=>$this->package_manager->test_read_write()
			])
			->build();
	}
	
	public function installAction($package=null) {
		$this->_process($package,'install');

		redirect($this->controller_path);
	}

	public function upgradeAction($package=null) {
		$this->_process($package,'upgrade');

		redirect($this->controller_path);
	}

	public function uninstallAction($package=null) {
		$this->_process($package,'uninstall');

		redirect($this->controller_path);
	}

	public function deleteAction($package=null) {
		$this->_process($package,'delete');

		redirect($this->controller_path);
	}

	public function detailsAction($package) {
		$this->page
			->data([
				'type_map'=>$this->type_map,
				'record'=>$this->package_manager->record(hex2bin($package))
			])
			->build();
	}

	protected function _process($name,$method) {
		$map = ['install'=>'installed','uninstall'=>'uninstalled','delete'=>'deleted','upgrade'=>'upgraded'];

		$package = hex2bin($name);
		
		/* dump all caches */
		$this->cache->clean();

		if ($reply = $this->package_manager->$method($package) !== true) {
			$this->wallet->failed($reply);

			return false;
		}

		$this->wallet->success('Module "'.$package.'" '.$map[$method].'.');
			
		/* also refresh the user data */
		$this->auth->refresh_userdata();

		return true;
	}
	
} /* end class */
