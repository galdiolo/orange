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
class dashboardController extends APP_AdminController {
	public $controller_path   = '/admin/dashboard';
	public $has_access = '@'; /* Allow everyone logged in */

	public function indexAction() {
		if (setting('application','Refresh Profile in Dashboard')) {
			$this->auth->refresh_userdata();
		}

		if (setting('application','Show Bogus Dashboard')) {
			$this->load->partial('/admin/dashboard/bogus_dashboard',[],'bogus_dashboard');
		}

		$this->page->build();
	}

	public function refreshAction() {
		$this->auth->refresh_userdata();

		redirect($this->controller_path);
	}

} /* end dashboardController */