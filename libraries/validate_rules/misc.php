<?php
trait validate_misc {
	private $true_array  = [1,'1','y','on','yes','t','true',true];
	private $false_array = [0,'0','n','off','no','f','false',false];

	public function if_empty(&$field, $option = null) {
		if (trim($field) === '' || $field === null) {
			/* save a copy for later */
			$replace = $option;

			/* either pass right thru or run use one of these values */
			if (preg_match('/(.*)\((.*?)\)/', $option, $matches)) {
				switch ($matches[1]) {
					case 'never':
						$replace = '2099-12-31 23:59:59';
					break;
					case 'now':
						$format  = ($matches[2]) ? $matches[2] : 'U';
						$replace = date($format);
					break;
					case 'user':
						$param = ($matches[2]) ? $matches[2] : 'id';
						
						if (is_object($this->ci_user)) {
							/* if it's empty make it 1 */
							$replace = (!empty($this->ci_user->$param)) ? $this->ci_user->$param : 1;
						} else {
							$replace = 1; /* default to root user id / root user default group */
						}
						
					break;
					case 'session':
						$param   = ($matches[2]) ? $matches[2] : 'id';
						$replace = $this->ci_session->userdata($param);
					break;
					case 'ip':
						$replace = ci()->input->ip_address();
					break;
				}
			}

			$field = $replace;
		}

		return TRUE;
	}

	public function is_bol($field, $options = null) {
		$this->set_message('is_bol', 'The %s is invalid.');

		/* PHP's built in function */
		if (is_bool($field)) {
			return TRUE;
		}

		/* our tests */
		return (in_array(strtolower($field), array_merge($this->true_array, $this->false_array), true)) ? true : false;
	}

	public function is_not($field, $options = null) {
		$this->set_message('is_not', '%s is not valid.');

		return ($field != $options);
	}

	public function matches_pattern($field, $options = null) {
		$pattern = ($options) ? $options : '';

		$this->set_message('matches_pattern', 'The %s does not match the required pattern.');

		return (bool) preg_match($pattern, $field);
	}

	public function one_of($field, $options = null) {
		// one_of[1,2,3,4]
		$types = ($options) ? $options : '';

		$this->set_message('one_of', '%s must contain one of the available selections.');

		return (in_array($field, explode(',', $types)));
	}

	public function not_one_of($field, $options = null) {
		// not_one_of[1,2,3,4]
		$types = ($options) ? $options : '';

		$this->set_message('not_one_of', '%s must not contain one of the available selections.');

		return (!in_array($field, explode(',', $types)));
	}

	public function check_captcha($field, $options = null) {
		// !todo -- captcha
		$this->set_message('check_captcha', 'Captcha is incorrect.');

		return TRUE;
	}

	/* this will check for any valid primary key mongoid or sql integer */
	public function is_a_primary($field, $options = null) {
		$this->set_message('is_a_primary', '%s is not a primary id.');

		$field = trim($field);

		/* is it empty? */
		if ($field == '') {
			return FALSE;
		}

		/* is it a sql primary id? */
		if (is_numeric($field)) {
			return TRUE;
		}

		/* is it a mongoid */
		if ((bool) preg_match('/^([a-fA-F0-9]{24})$/', $field)) {
			return TRUE;
		}

		return FALSE;
	}

	public function is_true_val($field, $options = null) {
		$field = strtolower($field);
		return ($field == 'y' || $field == 'yes' || $field === 1  || $field == '1' || $field== 'true' || $field == 't');
	}

	public function is_json_str($field, $options = null) {
		if (is_string($field)) {
			$json = json_decode($field, TRUE);
			return ($json !== NULL AND $field != $json);
		}

		return NULL;
	}

	public function is_closure($field, $options = null) {
		return is_object($field) && ($field instanceof Closure);
	}

	public function is_serialized_str($field, $options = null) {
		if ( !is_string($field))
			return false;
		$field = trim($field);
		if ( 'N;' == $field )
			return true;
		if ( !preg_match('/^([adObis]):/', $field, $badions))
			return false;
		switch ( $badions[1] ) :
		case 'a' :
		case 'O' :
		case 's' :
			if ( preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $field))
				return true;
			break;
		case 'b' :
		case 'i' :
		case 'd' :
			if ( preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $field))
				return true;
			break;
		endswitch;
		return false;
	}

} /* end time */