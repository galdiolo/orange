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
	
	public function testAction() {
		$record = $this->o_user_model->get(1);
		$record = ci()->load->presenter('role',$record);
		
		echo '<h2>single</h2>';
		xxx($record->username);
		xxx($record->is_deleted__human_date);
		xxx($record->cookies);
		xxx($record->name);
		
		$records = $this->o_user_model->index();
		$records = ci()->load->presenter('role',$records);
		
		echo '<h2>multi</h2>';
		foreach ($records as $record) {
			echo '<hr>';
			xxx($record->id);
			xxx($record->username);
			xxx($record->is_deleted__human_date);
			xxx($record->cookies);
			xxx($record->name);
			xxx($record->username__uppercase);
		}
		
	}

} /* end dashboardController */

function xxx($x) {
	echo '<pre>';
	var_dump($x);
	echo '</pre>';
}