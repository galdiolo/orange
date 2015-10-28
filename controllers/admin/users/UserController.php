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

class userController extends APP_AdminController {
	public $controller        = 'user';
	public $controller_path   = '/admin/users/user';
	public $controller_title  = 'User';
	public $controller_titles = 'Users';
	public $controller_model  = 'o_user_model';
	public $has_access 				= 'Orange::Manage Users';

	public function indexAction() {
		$this->load->library('plugin_search_sort');

		$records = $this->o_user_model->index('username');

		$this->page
			->data('records',$records)
			->build($this->controller_path.'/index');
	}

	public function newAction() {
		$this->page
			->data([
				'controller_action' => 'new',
				'password_format_copy' => setting('auth','Password Copy'),
				'record' => (object) [
					'id' => -1,
					'role_id' => setting('auth','Default Role Id'),
				],
			])
			->build($this->controller_path.'/form');
	}

	public function newPostAction() {
		$this->input->is_valid('is_a_primary', '@id');

		$data = $this->_get_data('insert');

		if ($id = $this->o_user_model->insert($data,false)) {
			$this->wallet->created($this->content_title, $this->controller_path);
		}

		$this->wallet->failed($this->content_title, $this->controller_path);
	}

	public function editAction($id = null) {
		$this->input->is_valid('is_a_primary',$id);

		$this->page
			->data([
				'controller_action'    => 'edit',
				'record'               => $this->auth->build_profile($id),
				'password_format_copy' => setting('auth','Password Copy'),
			])
			->build($this->controller_path.'/form');
	}

	public function editValidatePostAction() {
		$this->_get_data('update');

		$this->o_user_model->validate($this->data, 'update');

		$this->output->json($this->o_user_model->errors_json);
	}

	public function editPostAction() {
		$this->input->is_valid('is_a_primary', '@id');

		$data = $this->_get_data('update');

		if ($this->o_user_model->update($this->data['id'], $data, false)) {
			$this->wallet->updated($this->content_title, $this->controller_path);
		}

		$this->wallet->failed($this->content_title, $this->controller_path);
	}

	public function detailsAction($id = null) {
		$this->input->is_valid('is_a_id', $id);

		$user = $this->auth->build_profile($id);

		$profile_role_id = $user->role_id;

		if ($profile_role_id == setting('auth','Admin Role Id')) {
			$access = $this->o_access_model->get_many();
		} else {
			$access = $this->o_role_access_model->get_many_by_role_id($profile_role_id);
		}

		$this->page
			->data([
				'access'=>$access,
				'user'=>$user
			])
			->build($this->controller_path.'/details');
	}

} /* end controller */