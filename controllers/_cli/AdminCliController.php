<?php

class AdminCliController extends O_CliController {
	public $values;

	public function __construct() {
		parent::__construct();
		
		/* because we are bootstrapping this manually load menu */
		$this->load->model('o_menubar_model');
		$this->load->library('package/Package_migration');

		$attr = $_SERVER['argv'];
		$attr = array_slice($attr,3,null,true);
		
		foreach ($attr as $x) {
			list($name,$value) = explode('=',$x,2);

			$this->values[$name] = $value;
		}
	}

	public function menuCliAction() {
		$this->package_migration->add_menu($this->values);
	}

	public function accessCliAction() {
		$this->package_migration->add_access($this->values);
	}

	public function settingCliAction() {
		$this->package_migration->add_setting($this->values);
	}

	public function routeCliAction() {
	}

	public function symlinkCliAction() {
	}

	public function remove_menuCliAction($internal) {	
		$this->package_migration->remove_menu($internal);
	}

	public function remove_accessCliAction($internal) {	
		$this->package_migration->remove_access($internal);
	}

	public function remove_settingCliAction($internal) {	
		$this->package_migration->remove_setting($internal);
	}

	public function remove_routeCliAction() {	
	}

	public function remove_symlinkCliAction() {	
	}



} /* end class */