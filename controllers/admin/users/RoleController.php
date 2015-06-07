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

class roleController extends APP_AdminController {
	public $controller        = 'role';
	public $controller_path   = '/admin/users/role';
	public $controller_title  = 'Role';
	public $controller_titles = 'Roles';
	public $controller_model  = 'o_role_model';
	public $has_access 				= 'Orange::Manage Roles';

	public function indexAction() {
		$records = $this->{$this->controller_model}->index('name');

		$this->page->data('records',$records)->build($this->controller_path.'/index');
	}

	public function newAction() {
		$this->page
			->data([
				'controller_action'  => 'new',
				'record' => (object) ['id' => -1],
				'access' => [],
				'all_access' => $this->o_access_model->catalog(),
				'access_tabs' => array_unique($this->o_access_model->catalog('id','group')),
			])
			->build($this->controller_path.'/form');
	}

	public function newValidatePostAction() {
		$this->_get_data('insert');

		$this->o_role_model->validate($this->data, 'insert');

		$errors = $this->o_role_model->errors_json;

		$errors['errors'] = str_replace('Access is not valid.','Please select one or more access resources.',$errors['errors']);

		$this->output->json($errors);
	}

	public function newPostAction() {
		$this->input->is_valid('is_a_primary', '@id');

		$data = $this->_get_data('insert');

		if ($id = $this->o_role_model->insert($data, 'insert')) {
			$this->wallet->created($this->content_title,$this->controller_path);
		}

		$this->wallet->failed($this->content_title, $this->controller_path);
	}

	public function editAction($id = null) {
		$this->input->is_valid('is_a_primary',$id);

		$this->load->helper('array');

		$this->page
			->data([
				'controller_action' => 'edit',
				'record' => $this->o_role_model->get($id),
				'access' => array2list((array)$this->o_role_access_model->get_many_by_role_id($id),'id','name'),
				'all_access' => $this->o_access_model->catalog(),
				'access_tabs' => array_unique($this->o_access_model->catalog('id','group')),
			])
			->build($this->controller_path.'/form');
	}

	public function editValidatePostAction() {
		$this->_get_data('update');

		$this->o_role_model->validate($this->data, 'update');

		$errors = $this->o_role_model->errors_json;

		$errors['errors'] = str_replace('Access is not valid.','Please select one or more access resources.',$errors['errors']);

		$this->output->json($errors);
	}

	public function editPostAction() {
		$this->input->is_valid('is_a_primary', '@id');

		$data = $this->_get_data('update');

		if ($this->o_role_model->update($data['id'], $data, 'update')) {
			$this->wallet->updated($this->content_title, $this->controller_path);
		}

		$this->wallet->failed($this->content_title, $this->controller_path);
	}

	public function deleteAction($id = null) {
		$this->input->is_valid('is_a_id', $id);

		/* change everyone that has this access to the default access */
		$err1 = $this->o_user_model->swap_roles($id,setting('auth','Default Role Id'));
		$err2 = $this->o_role_model->delete($id);
		$err3 = $this->o_role_access_model->delete_by('role_id', $id);

		$this->output->json('err', !($err1 || $err2 || $err3));
	}

	public function detailsAction($id) {
		/* if somebody is sending in bogus id's send them to a fiery death */
		$this->input->is_valid('is_a_id', $id);

		if ($id == setting('auth','Admin Role Id')) {
			$access = $this->o_access_model->get_many();
		} else {
			$access = $this->o_role_access_model->get_many_by_role_id($id);
		}

		$this->page
			->data([
				'role' => $this->o_role_model->get($id),
				'access' => $access,
				'users' => $this->o_user_model->get_many_by('role_id',$id),
			])
			->build();
	}

} /* end class */