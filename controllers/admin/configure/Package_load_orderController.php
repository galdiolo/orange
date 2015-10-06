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

class package_load_orderController extends APP_AdminController {
	public $controller = 'package_load_order';
	public $controller_path = '/admin/configure/package-load-order';
	public $controller_title = 'Package Load Order';
	public $controller_titles = 'Packages Load Order';
	public $libraries = 'package_manager';
	public $has_access = 'Orange::Manage Packages';
	public $type_map = [''=>'default','?'=>'danger','core'=>'warning','library'=>'success','libraries'=>'success','theme'=>'warning','package'=>'primary','plugin'=>'info','assets'=>'warning'];

	public function indexAction() {
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
			->build($this->controller_path.'/index');
	}

	public function resetAction() {
		$this->package_manager->reset_priorities();

		$this->package_manager->packages_config();
		$this->load->create_onload();
		
		$this->wallet->success('Load Order Reset',$this->controller_path);
	}

	public function configAction() {
		$this->package_manager->packages_config();

		$this->wallet->updated('Config',$this->controller_path);
	}
	
	public function onloadAction() {
		$this->load->create_onload();

		$this->wallet->updated('Onload',$this->controller_path);
	}

	public function savePostAction() {
		$order = $this->input->post('order');

		foreach ($order as $folder_hex=>$order) {
			$folder = hex2bin($folder_hex);

			if (!empty($order)) {
				/* tag as overridden / override if it's already overridden */
				$this->package_manager->write_new_priority($folder,(int)$order,true,true);
			}
		}

		/* force update of onload and autoload */
		$this->package_manager->packages_config();
		$this->load->create_onload();

		$this->output->json('err',false);
	}

} /* end controller */