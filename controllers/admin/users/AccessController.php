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

class AccessController extends APP_AdminController {
	public $controller        = 'access';
	public $controller_path   = '/admin/users/access';
	public $controller_title  = 'Access';
	public $controller_titles = 'Access';
	public $controller_model  = 'o_access_model';
	public $controller_help 	= 'Access are used to control access to different sections.';
	public $has_access 				= 'Orange::Manage Access';
	public $libraries         = 'plugin_combobox';

	public function indexAction() {
		$records = $this->o_access_model->index('group,name');
		$records = $this->_format_tabs($records,'group');

		$this->page
			->data('records',$records)
			->build();
	}

	public function detailsAction($id = null) {
		$this->input->is_valid('is_a_id', $id);

		$this->page
			->data([
				'access' => $this->o_access_model->get($id),
				'roles' => $this->o_role_access_model->get_many_by_access_id($id),
			])
			->build();
	}

} /* end class */