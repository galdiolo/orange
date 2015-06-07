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
class o_user_model extends Database_model {
	protected $table = 'orange_users';
	protected $soft_delete = true;
	protected $rules = [
		'id'             => ['field' => 'id','label' => 'Id','rules' => 'required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'created_on'     => ['field' => 'created_on','label' => 'Created On','rules' => 'if_empty[now(Y-m-d H:i:s)]|required|max_length[24]|valid_datetime|filter_input[24]'],
		'created_by'     => ['field' => 'created_by','label' => 'Created By','rules' => 'if_empty[user()]|required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'updated_on'     => ['field' => 'updated_on','label' => 'Updated On','rules' => 'if_empty[now(Y-m-d H:i:s)]|required|max_length[24]|valid_datetime|filter_input[24]'],
		'updated_by'     => ['field' => 'updated_by','label' => 'Updated By','rules' => 'if_empty[user()]|required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'is_editable'    => ['field' => 'is_editable','label' => 'Editable','rules' => 'if_empty[1]|one_of[0,1]|filter_int[1]|max_length[1]'],
		'is_deletable'   => ['field' => 'is_deletable','label' => 'Deletable','rules' => 'if_empty[1]|one_of[0,1]|filter_int[1]|max_length[1]'],
		'is_deleted'     => ['field' => 'is_deleted','label' => 'Deleted','rules' => 'if_empty[0000-00-00 00:00:00]|required|max_length[24]|valid_datetime|filter_input[24]'],

		'username' => ['field' => 'username','label' => 'User Name','rules' => 'required|xss_clean'],
		'password' => ['field' => 'password','label' => 'Password','rules' => 'required|password|max_length[255]|filter_input[255]'],

		'email' => ['field' => 'email','label' => 'Email','rules' => 'required|strtolower|valid_email|max_length[255]|filter_input[255]'],
		'is_active' => ['field' => 'is_active','label' => 'Active','rules' => 'if_empty[0]|one_of[0,1]|filter_int[1]|max_length[1]|less_than[2]'],

		'role_id' => ['field' => 'role_id','label' => 'Role Id','rules' => 'if_empty[0]|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'last_login' => ['field' => 'last_login','label' => 'Last Login','rules' => 'max_length[24]|valid_datetime|filter_input[24]'],
		'last_ip' => ['field' => 'last_ip','label' => 'Last Ip','rules' => 'max_length[16]|filter_input[16]'],

		/* special for vaildation */
		'confirm_password' => ['field' => 'confirm_password','label' => 'Confirmation Password','rules' => 'required|matches[password]'],
		'login_password' => ['field' => 'password','label' => 'Password','rules' => 'required|max_length[255]|filter_input[255]'],
		'login_email' => ['field' => 'email','label' => 'Email','rules' => 'required|max_length[255]|filter_input[255]'],
	];

	protected $rule_sets = [
		'insert' => 'created_on,created_by,updated_on,updated_by,username,email,password,confirm_password,role_id,is_active',
		'update' => 'id,updated_on,updated_by,username,email,role_id,is_active,password,confirm_password',
		'update_no_password' => 'id,updated_on,updated_by,username,email,role_id,is_active',
		'login' => 'email,password',
	];

	public function __construct() {
		parent::__construct();

		/* attach a custom validation function onto validate library */
		$this->load->library('validate');

		$this->validate->attach('password', function ($validate_obj, $field) {
			$validate_obj->set_message('password', 'Your password is not in the correct format.');

			return (bool) preg_match(setting('auth','Password regex'), $field);
		});

		/* setup the rules from the config */
		$this->rules['username']['rules'] .= '|min_length['.setting('auth','Username Min Length').']|max_length['.setting('auth','Username Max Length').']|filter_input['.setting('auth','Username Max Length').']';

		/* does the username need to be unquie? */
		if (!setting('auth','Allow Same Username')) {
			$this->rules['username']['rules'] .= '|is_uniquem[o_user_model.username.id]';
		}

		if (!setting('auth','Allow Same Email')) {
			$this->rules['email']['rules'] .= '|is_uniquem[o_user_model.email.id]';
		}
	}

	/* validate can be insert,update,register,login */
	public function validate($data = null, $skip_validation = null) {
		switch ($skip_validation) {
			case 'update':
				if (empty($data['password'].$data['confirm_password'])) {
					$skip_validation = 'update_no_password';
				}
			break;
			case 'login':
				/* swap out the rules */
				$this->rules['password'] = $this->rules['login_password'];
				$this->rules['email'] = $this->rules['login_email'];
			break;
		}

		return parent::validate($data, $skip_validation);
	}

	public function delete($id=null) {
		$this->update($id,['is_active'=>0],true);
		
		/* soft delete */
		return parent::delete($id);
	}

	/* override insert */
	public function insert($data, $skip_validation = false) {
		$this->flush_caches();

		if (isset($data[$this->primary_key])) {
			unset($data[$this->primary_key]);
		}

		if ($skip_validation !== TRUE) {
			$rule = ($skip_validation === FALSE) ? 'insert' : $skip_validation;

			/* return FALSE on failure data validated & filtered */
			$data = $this->validate($data, $rule);
		}

		/* did validation pass? */
		if ($data !== FALSE) {
			/* unset this if it's set */
			unset($data['confirm_password']);

			$data['password'] = $this->hash_password($data['password']);

			$this->_database->insert($this->table, $data);

			return $this->_database->insert_id();
		}

		return FALSE;
	}

	/* override update */
	public function update($primary_value, $data, $skip_validation = false) {
		$this->flush_caches();

		if ($skip_validation !== TRUE) {
			$rule = ($skip_validation === FALSE) ? 'update' : $skip_validation;

			/* return FALSE on failure data validated & filtered */
			$data = $this->validate($data, $rule);
		}

		if ($data !== FALSE) {
			/* unset this if it's set */
			unset($data['confirm_password']);

			if (isset($data['password'])) {
				$data['password'] = $this->hash_password($data['password']);
			}

			return $this->_database->where($this->primary_key, $primary_value)->set($data)->update($this->table);
		}

		return FALSE;
	}

	public function update_password($user_id,$password,$hashed=false) {
		$password = ($hashed) ? $password : $this->hash_password($password);
		return $this->_database->where($this->primary_key, $user_id)->set(['password'=>$password])->update($this->table);
	}

	public function hash_password($password) {
		/* use new PHP password hasher */
		return password_hash($password, PASSWORD_DEFAULT);
	}

	public function get_user_by_login($login) {
		$this->_database->where('LOWER(username)=', strtolower($login));
		$this->_database->or_where('LOWER(email)=', strtolower($login));

		$query = $this->_database->get($this->table);

		return ($query->num_rows() == 1) ? $query->row() : null;
	}

	public function get_user_by_username($username) {
		$this->_database->where('LOWER(username)=', strtolower($username));

		$query = $this->_database->get($this->table);

		return ($query->num_rows() == 1) ? $query->row() : null;
	}

	public function get_user_by_email($email) {
		$this->_database->where('LOWER(email)=', strtolower($email));

		$query = $this->_database->get($this->table);

		return ($query->num_rows() == 1) ? $query->row() : null;
	}

	public function update_login_info($user_id) {
		return $this->_database->set('last_ip', $this->input->ip_address())->set('last_login', date('Y-m-d H:i:s'))->where('id', $user_id)->update($this->table);
	}

	public function swap_roles($from_role_id,$to_role_id) {
		return $this->_database->set('role_id',$to_role_id)->where('role_id',$from_role_id)->update($this->table);
	}

	public function activate($user_id) {
		return $this->_database->where($this->primary_key, $user_id)->set(['is_active'=>1])->update($this->table);
	}


} /* end class */