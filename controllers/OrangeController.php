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
			->css($this->page->theme_path().'/assets/css/auth/login.min.css')
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

	/*
	only callable from the command line
	php index.php orange/setup
	this makes sure all the needed folders are there
	and with the right permissions
	*/
	public function setupCliAction() {
		/* setup the var "temp stuff" folder */
		echo 'Setting up /var folders'.chr(10);
		@mkdir(ROOTPATH.'/var',0777);
		@mkdir(ROOTPATH.'/var/cache',0777);
		@mkdir(ROOTPATH.'/var/captcha',0777);
		@mkdir(ROOTPATH.'/var/email',0777);
		@mkdir(ROOTPATH.'/var/logs',0777);
		@mkdir(ROOTPATH.'/var/sessions',0777);
		@mkdir(ROOTPATH.'/var/upload_temp',0777);
		@mkdir(ROOTPATH.'/var/migrations',0777);

		/* setup the public folders */
		echo 'Setting up /public/assets folders'.chr(10);
		@mkdir(ROOTPATH.'/public/assets',0777);
		@mkdir(ROOTPATH.'/public/assets/css',0777);
		@mkdir(ROOTPATH.'/public/assets/js',0777);
		@mkdir(ROOTPATH.'/public/assets/images',0777);
		@mkdir(ROOTPATH.'/public/assets/fonts',0777);
		@mkdir(ROOTPATH.'/public/assets/vendor',0777);

		echo 'Setting up /public/themes folders'.chr(10);
		@mkdir(ROOTPATH.'/public/themes',0777);

		/* setup some other public folder folders */
		echo 'Setting up /public/images folders'.chr(10);
		@mkdir(ROOTPATH.'/public/images',0777);

		/* change the permission on a few files GIT seems to jack up sometimes */
		echo 'Changing Access on /application/config folders'.chr(10);
		exec('chmod 777 "'.ROOTPATH.'/application/config"');
		exec('chmod 777 "'.ROOTPATH.'/application/config/autoload.php"');
		exec('chmod 777 "'.ROOTPATH.'/application/config/modules.php"');
		exec('chmod 777 "'.ROOTPATH.'/application/config/routes.php"');

		echo 'Changing Access on /modules folders'.chr(10);
		exec('chmod 777 "'.ROOTPATH.'/modules"');
		exec('chmod 777 "'.ROOTPATH.'/modules/_upgrades"');

		echo 'Changing Access on /var folders'.chr(10);
		exec('chmod -R 777 "'.ROOTPATH.'/var"');

		echo 'Changing Access on /public/themes folders'.chr(10);
		exec('chmod -R 777 "'.ROOTPATH.'/public/themes"');
	}

} /* end controller */
