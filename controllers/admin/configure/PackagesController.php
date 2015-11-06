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
	public $type_map = [''=>'default','?'=>'danger','core_required'=>'warning','core'=>'warning','library'=>'success','libraries'=>'success','theme'=>'danger','package'=>'primary','plugin'=>'info','assets'=>'danger'];

	public function indexAction() {
		$this->load->library('plugin_search_sort');

		$this->page
			->data([
				'type_map'=>$this->type_map,
				'records'=>$this->package_manager->records(),
				'errors'=>$this->package_manager->messages,
			])
			->build($this->controller_path.'/index');
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
		$package = hex2bin($package);

		$this->page
			->data([
				'type_map'=>$this->type_map,
				'record'=>$this->package_manager->record($package),
/*				'help'=>file_exists(ROOTPATH.'/packages/'.$folder.'/support/help/index.html'), */
/*				'help_folder'=>bin2hex(basename($folder)), */
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

		/* also refresh the user data */
		$this->auth->refresh_userdata();

		if ($this->package_manager->$method($package) !== true) {
			$this->wallet->failed(ucfirst($method).' Error');

			return false;
		}

		$this->wallet->success('Package "'.$package.'" '.$map[$method].'.');

		return true;
	}

	/*
	* All change load order methods
	*/
	public function load_orderAction() {
		$this->package_manager->refresh_package_priority();

		$records = $this->package_manager->records();

		uasort($records,function($a,$b) {
			if ($a['priority'] == $b['priority']) {
				return 0;
			}
			return ($a['priority'] < $b['priority']) ? -1 : 1;
		});

		$this->page
			->js('/themes/orange/assets/js/packages.js')
			->data([
				'type_map'=>$this->type_map,
				'records'=>$records,
				'back_url'=>'/admin/configure/packages',
			])
			->build($this->controller_path.'/order_index');
	}

	public function resetAction() {
		/* reset priorities to package defaults */
		$this->package_manager->reset_priorities();

		$this->package_manager->packages_config();
		$this->package_manager->create_onload();

		$this->wallet->success('Load Order Reset',$this->controller_path.'/load-order');
	}

	public function configAction() {
		$this->package_manager->packages_config();

		$this->wallet->updated('Config',$this->controller_path);
	}

	public function onloadAction() {
		$this->package_manager->create_onload();

		$this->wallet->updated('Onload',$this->controller_path);
	}

	public function load_order_savePostAction() {
		$order = $this->input->post('order');

		foreach ($order as $folder_hex=>$order) {
			$folder = hex2bin($folder_hex);

			if (!empty($order)) {
				/* tag as overridden / override if it's already overridden */
				$this->package_manager->write_new_priority($folder,(int)$order,true,true);
			}
		}

		$this->wallet->updated('Order');

		/* force update of onload and autoload */
		$this->package_manager->packages_config();
		$this->package_manager->create_onload();

		$this->output->json('err',false);
	}

} /* end class */