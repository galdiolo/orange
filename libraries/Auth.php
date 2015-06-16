<?php
/**
* This package has been extended and modified
*
* @package	CodeIgniter / Orange
* @author	Don Myers
* @license	http://opensource.org/licenses/MIT	MIT License
* @link	https://github.com/dmyers2004
*
* @based on	Tank_auth (http://konyukhov.com/soft/)
* @based on	DX Auth by Dexcell (http://dexcell.shinsengumiteam.com/dx_auth)
*
*/
class Auth {
	protected $session_key = 'user::data';
	protected $default_msgs = [
		'forged'=>'<strong>Forged Request Detected:</strong> If you clicked on a link and arrived here...that is bad.<br><a href="/">Continue</a>',
		'denied'=>'<strong>Access Denied:</strong> Sorry you don\'t have access to this resource.<br><a href="/">Continue</a>',
		'generic'=>'<strong>System Error:</strong> Sorry a system error has occurred.<br><a href="/">Continue</a>',
	];
	public $error;
	public $msg;

	protected $ci_load;
	protected $ci_session;
	protected $ci_input;
	protected $ci_event;
	protected $ci_validate;
	protected $ci_router;
	protected $ci_output;
	protected $ci_wallet;

	protected $o_user_model;
	protected $o_role_model;
	protected $o_access_model;
	protected $o_role_access_model;

	public function __construct() {
		/* user object on service locator */
		$this->ci_load = &ci()->load;
		$this->ci_input = &ci()->input;
		$this->ci_router = &ci()->router;
		$this->ci_output = &ci()->output;
		$this->ci_event = &ci()->event;
		$this->ci_session = &ci()->session;

		$this->ci_load->library(['validate','wallet']);

		$this->ci_validate = &ci()->validate;
		$this->ci_wallet = &ci()->wallet;

		/* let's load all the other required models */
		$this->ci_load->model(['o_access_model','o_role_access_model','o_role_model','o_user_model']);

		$this->o_user_model = &ci()->o_user_model;
		$this->o_role_model = &ci()->o_role_model;
		$this->o_access_model = &ci()->o_access_model;
		$this->o_role_access_model = &ci()->o_role_access_model;

		/* a few variables must be setup test for them and store a copy as a object variable */
		$configs = ['Admin Role Id','Nobody Role Id','Default Role Id','Default Menu Id','Superuser Id'];
		
		foreach ($configs as $config) {
			if (!is_integer(setting('auth',$config,null))) {
				/* are some of the config preferences setup? */
				show_error('Authorization library '.$config.' is not setup correctly in your configuration');
			}
		}

		/* load the user profile from the session do basic validation */
		$session_data = $this->ci_session->userdata($this->session_key);
		
		/* default profile */
		$default_profile = (object)setting('auth','Default Profile',[]);

		/* set the user data */
		ci()->user = ($this->validate_profile($session_data) === true) ? $session_data : $default_profile;
	}

	public function login($email,$password) {
		$ajax = $this->ci_input->is_ajax_request();

		$success = $this->_login($email, $password, $ajax);

		$this->ci_event->trigger('auth.login',$email,$success,$ajax);

		/* error stored in ci()->user->error */
		return $success;
	}

	/* login heavy lifter */
	protected function _login($login, $password) {
		/* TEST -- did they send anything in? */
		if ((strlen(trim($login)) == 0) or (strlen(trim($password)) == 0)) {
			$this->error = 'Please enter your login credentials before pressing Submit.';

			log_message('debug', 'auth->user '.$this->error);

			return false;
		}

		/* basic trigger to let listeners know we are trying to init login */
		$this->ci_event->trigger('user.login.init',$login);

		/* Do we allow a User Login Test override? If so fire trigger */
		if (setting('auth','Allow User Login Test Trigger',false) === true) {
			$trigger_error = '';
			$trigger_success = null;

			$this->ci_event->trigger('user.login.test',$login,$password,$trigger_error,$trigger_success);

			if ($trigger_success === false) {
				log_message('debug', 'auth->user '.$trigger_error);

				$this->error = $trigger_error;

				return false;
			} elseif ($trigger_success === true) {
				log_message('debug', 'auth->user '.$trigger_error);

				$this->error = $trigger_error;

				return true;
			}
		}

		/* TEST -- ok does this login exists? */
		if (is_null($user = $this->o_user_model->get_user_by_email($login))) {
			log_message('debug', 'Auth Get User Function returned NULL');

			$this->error = 'Incorrect Login and/or Password';

			return false;
		}

		/* TEST -- another safety check - is user a object */
		if (!is_object($user)) {
			log_message('debug', 'Auth $user not an object');

			$this->error = 'Incorrect Login and/or Password';

			return false;
		}

		/* TEST -- another safety check - is the user id a integer less than 1 */
		if ((int) $user->id === 0) {
			log_message('debug', 'Auth $user->id is 0 (no users id is 0)');

			$this->error = 'Incorrect Login and/or Password';

			return false;
		}

		/* TEST -- OK the password check user the PHP built in password hasher */
		if (password_verify($password, $user->password) !== true) {
			/* this is the real password wrong error */
			$this->ci_event->trigger('user.login.fail',$login);

			$this->error = 'Incorrect Login and/or Password';

			log_message('debug', 'auth->user '.$this->error);

			return false;
		}

		/* TEST -- Logged in but, has this user been activated? */
		if ((int) $user->is_active !== 1) {
			$this->error = 'You are not active';
			
			log_message('debug', 'auth->user '.$this->error);

			return false;
		}

		/* now build the complete profile (user id object) */
		$user = $this->build_profile($user->id);

		if ($this->validate_profile($user) !== true) {
			$this->error = 'User profile could not be built.';
			
			log_message('debug', 'auth->user '.$this->error);

			return false;
		}

		/* update the login attempts and login info */
		$this->o_user_model->update_login_info($user->id);

		/* save this user to the session variable for later */
		$this->ci_session->set_userdata([$this->session_key => $user]);

		/* it's good! */
		$this->ci_event->trigger('user.login.success',$user);

		if (!is_object($user)) {
			show_error('Fatal user creation error');
		}

		/* setup the user object on CI */
		ci()->user = &$user;

		/* Everything is good we are all done! */
		return true;
	}

	public function forged() {
		$errors = $this->ci_validate->error_string(' ', '');

		$this->ci_event->trigger('auth.forged',$errors);

		$this->error('forged');

		exit(1); /* fail safe */
	}

	public function denied($logged_url='') {
		/* try to create a more complete details */
		$route = trim($this->ci_router->fetch_directory().$this->ci_router->fetch_class().'/'.$this->ci_router->fetch_method(), '/');
		$username = (ci()->user->username) ? ci()->user->username : 'n/a';
		$error_text = 'Access Denied: route:'.$route.' username:'.$username.' url:'.$logged_url;

		/* Call our Security Logger */
		$this->ci_event->trigger('auth.denied',$route,$username,$error_text);

		$this->error('denied');

		exit(1); /* fail safe */
	}

	/* used to test regular access */
	public function has_access($access=null,$user_access=null) {
		log_message('debug', 'auth::has_access '.implode(', ',(array)$access));

		/* if they didn't send in anything then return false - fail */
		if ($access === null) {
			log_message('debug', 'auth::has_access access null');

			show_error('has_access $access is empty. This is probably because you don\'t have it set on your controller');

			return false; /* fail */
		}

		/* is there Role the admin role? */
		if (ci()->user->is_admin === TRUE) {
			return true;
		}

		/* if user access is null then use the current users access */
		$user_access = (is_array($user_access)) ? $user_access : ci()->user->access;

		foreach ((array)$access as $a) {
			/* everyone */
			if ($a === '*') {
				return true;
			}

			/* everyone logged in */
			if ($a === '@') {
				if (ci()->user->is_active === true) {
					return true;
				}
			}

			/* did they send in a primary id? */
			$id = !is_int($a) ? ctype_digit($a) : true;

			if (in_array($a,$user_access,true) && !$id) {
				/* access value match */
				return true;
			} elseif (array_key_exists($a,$user_access) && $id) {
				/* access id match */
				return true;
			}
		}

		/* nope! */
		log_message('debug', 'Access failed to "'.$this->ci_input->server('PHP_SELF').'" by '.ci()->user->username);

		return false;
	}

	/**
	* Logout user from the site
	*
	* @return	void
	*/
	public function logout($dump_auto_login = true) {
		$this->ci_event->trigger('user.logout',$dump_auto_login);

		/* clear all places we have a user available */
		$this->ci_session->set_userdata([$this->session_key => [], 'session_id' => '']);
		$this->ci_load->vars([$this->session_key => []]);

		return true; /* this can't fail? but let's return success anyway */
	}

	public function validate_profile($profile) {
		/* validate 1 by 1 because after the first fail no sense in continuing the logic */
		if (is_object($profile)) {
			if ((int) $profile->id > 0) {
				if ((int) $profile->role_id > 0) {
					return true;
				}
			}
		}

		return false;
	}

	/*
	Used to retrieve and build ANY Profile (by (int)id)
	ONLY ever use this method to keep everything consistant!
	this returns a profile (object)
	it doesn't attach it to the current user or anything like that
	*/
	public function build_profile($user_id) {
		/* integer value of role id must be greater than 0 */
		if ((int) $user_id < 1) {
			/* display error and exit since we can't build this profile "bad things" could happen if we proceed */
			$this->error('generic','Could Not Build Profile. 001');
			exit(1); /* just incase */
		}

		$user = $this->o_user_model->get((int)$user_id);

		/* did we get a object? */
		if (!is_object($user)) {
			/* display error and exit since we can't build this profile "bad things" could happen if we proceed */
			$this->error('generic','Could Not Build Profile. 002');
			exit(1); /* just incase */
		}

		/* clear out a bunch of un-needed stuff and save this for later */
		$remove = ['password','created_on','created_by','updated_on','updated_by','group_id','is_editable','is_deletable','is_deleted'];

		foreach ($remove as $r) {
			unset($user->$r);
		}

		/* make sure these are typecast properly */
		$user->is_admin = false;
		$user->id = (int) $user->id;
		$user->role_id = (int)$user->role_id;
		$user->is_active = (boolean)((int)$user->is_active === 1);

		/* integer value of role id must be greater than 0 */
		if ($user->role_id < 1) {
			/* display error and exit since we can't build this role "bad things" could happen if we proceed */
			$this->error('generic','Could Not Build Profile. 003');
			exit;
		}

		/* ok load up this role */
		$role_record = $this->o_role_model->get($user->role_id);

		/* did we get a object? */
		if (!is_object($role_record)) {
			/* display error and exit since we can't build this role "bad things" could happen if we proceed */
			$this->error('generic','Could Not Build Profile. 004');
		}

		$user->role_name = (string) $role_record->name;
		$user->role_description = (string) $role_record->description;

		/* setup groups */
		if ($user->role_id === (int)setting('auth','Admin Role Id')) {
			$user->is_admin = true;
			$access = $this->o_access_model->get_many();
		} else {
			$access = $this->o_role_access_model->get_many_by_role_id($user->role_id);
		}

		foreach ($access as $a) {
			$user->access[(int)$a->id] = $a->key;
		}

		return $user; /* (object) */
	}

	/* Refresh the *Current Users* Profile */
	public function refresh_userdata() {
		$profile = $this->build_profile(ci()->user->id);

		if ($this->validate_profile($profile) === true) {
			ci()->user = &$profile;
			$this->ci_session->set_userdata([$this->session_key => $profile]);
		} else {
			$this->error('generic','Fatal: Error refreshing your user profile.');
		}
	}

	/* handle forged, denied, generic errors */
	public function error($which='generic',$msg=null) {
		/*
		handle fatal errors like denied, forged, generic in a single function
		support for: ajax / html & http status number + message or redirect + flash_msg
		*/

		$this->ci_event->trigger('auth.display.error',$which,$msg);

		/* convert everything to lowercase and grab the default msg */
		$which = strtolower($which);

		/* it must be in this array or use generic */
		$which = (in_array($which,['forged','denied','generic'])) ? $which : 'generic';

		/* grab the built in default message */
		$msg = ($msg) ? $msg : $this->default_msgs[$which];

		/* now convert it to human and read it from the config (which is human "cased") */
		$which = ucfirst($which);

		/* grab the config settings */
		$flash_msg = setting('auth',$which.' Flash Msg');

		$http_status = setting('auth',$which.' HTTP Status');
		$http_status = ((int)$http_status > 1) ? $http_status : 404;

		$redirect_url = setting('auth',$which.' Redirect URL');
		$redirect_url = (empty($redirect_url)) ? '/' : $redirect_url;

		$type = setting('auth',$which.' Type');

		if ($this->ci_input->is_ajax_request()) {
			/* dump standard json error */
			$this->ci_output->json(['err' => true, 'errors_array' => [$msg], 'errors' => $msg])->_display();
		} else {
			/* HTML output */
			if (is_object($this->ci_wallet) && !empty($flash_msg)) {
				$this->ci_wallet->red($flash_msg);
			}

			/* if the type == redirect then do it else fall back to standard error */
			if ($type == 'redirect') {
				redirect($redirect_url);
			} else {
				show_error($msg,$http_status);
			}
		}

		exit(1); /* fail safe */
	}

} /* end class */