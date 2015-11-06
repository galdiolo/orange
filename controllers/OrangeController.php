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

class orangeController extends APP_AdminController {
	public $controller_path = '/orange';
	public $forms = ['login'=>'email,password'];
	public $has_access = '*'; /* everyone */

	/* standard login */
	public function indexAction() {
		/* force logout */
		if (setting('auth','Force Logout on Login Page')) {
			$this->auth->logout();
		}

		$this->page
			->css('/themes/orange/assets/css/auth/login.min.css')
			->build($this->controller_path.'/index');
	}

	public function loginValidatePostAction() {
		$this->input->map($this->forms['login'], $this->data);
		$this->o_user_model->validate($this->data,'login');
		$this->output->json($this->o_user_model->errors_json);
	}

	public function loginPostAction() {
		$this->input->map($this->forms['login'],$this->data);

		if ($this->auth->login($this->data['email'], $this->data['password'])) {
			$this->wallet->success('Welcome',setting('auth','URL Dashboard'));
		}

		$this->wallet->failed('Login Failed<br>'.$this->auth->error, $this->controller_path);
	}

	/* logout */
	public function logoutAction() {
		/* dump auto login information */
		$this->auth->logout();

		$this->wallet->success('You are now logged out', setting('auth','URL Login'));
	}
} /* end controller */
