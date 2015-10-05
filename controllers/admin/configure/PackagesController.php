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
	public $libraries = 'package_manager';
	public $has_access = 'Orange::Manage Packages';
	public $type_map = [''=>'default','?'=>'danger','core'=>'warning','library'=>'success','libraries'=>'success','theme'=>'warning','package'=>'primary','plugin'=>'info','assets'=>'warning'];

	public function indexAction($filter=null) {
		/* check if it's coming from search */
		if ($filter) {
			$this->input->is_valid('alpha',$filter);
		}

		$this->page
			->data([
				'type_map'=>$this->type_map,
				'records'=>$this->package_manager->records(),
				'filter'=>$filter,
				'errors'=>$this->package_manager->messages,
			])
			->build($this->controller_path.'/index');
	}

	public function searchAction($filter=null) {
		$this->input->is_valid('alpha',$filter);

		$this->indexAction($filter);
	}

	public function configAction() {
		$this->package_manager->packages_config();

		$this->wallet->updated('Config',true);
		
		$this->indexAction();
	}

	public function onloadAction() {
		$this->load->create_onload();

		$this->wallet->updated('Onload',true);

		$this->indexAction();
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
		$folder = hex2bin($package);

		$this->page
			->data([
				'type_map'=>$this->type_map,
				'record'=>$this->package_manager->record($folder),
				'help'=>file_exists(ROOTPATH.'/packages/'.$folder.'/support/help/index.html'),
				'help_folder'=>bin2hex(basename($folder)),
			])
			->build();
	}

	public function helpAction($package) {
		$folder = hex2bin($package);

		echo file_get_contents(ROOTPATH.'/packages/'.$folder.'/support/help/index.html');
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

		$this->wallet->success('Package "'.$package.'" '.$map[$method].'.');

		/* also refresh the user data */
		$this->auth->refresh_userdata();

		return true;
	}

} /* end class */