<?php
trait validate_ci {
	/**
	* Required
	*
	* @param	string
	* @return	bool
	*/
	public function required($str) {
		$this->set_message('required', 'The %s field is required.');

		return is_array($str) ? (bool) count($str) : (trim($str) !== '');
	}

	// --------------------------------------------------------------------

	/**
	* Performs a Regular Expression match test.
	*
	* @param	string
	* @param	string	regex
	* @return	bool
	*/
	public function regex_match($str, $regex) {
		$this->set_message('regex_match', 'The %s field is not in the correct format.');

		return (bool) preg_match($regex, $str);
	}

	// --------------------------------------------------------------------

	/**
	* Match one field to another
	* DM adjusted
	*
	* @param	string	$str	string to compare against
	* @param	string	$field
	* @return	bool
	*/
	public function matches($str, $field) {
		$this->set_message('matches', 'The %s field does not match the %s field.');

		return isset($this->_field_data[$field]) ? ($str === $this->_field_data[$field]) : false;
	}

	// --------------------------------------------------------------------

	/**
	* Differs from another field
	*
	* @param	string
	* @param	string	field
	* @return	bool
	*/
	public function differs($str, $field) {
		$this->set_message('differs', 'The %s field must differ from the %s field.');

		return ! (isset($this->_field_data[$field]) && $this->_field_data[$field] === $str);
	}

	// --------------------------------------------------------------------

	/**
	* Is Unique
	*
	* Check if the input value doesn't already exist
	* in the specified database field.
	*
	* @param	string	$str
	* @param	string	$field
	* @return	bool
	*/
	public function is_unique($str, $field) {
		$this->set_message('is_unique', 'The %s field must contain a unique value.');

		sscanf($field, '%[^.].%[^.]', $table, $field);

		return isset($this->ci_db)
			? ($this->ci_db->limit(1)->get_where($table, [$field => $str])->num_rows() === 0)
			: false;
	}

	// --------------------------------------------------------------------

	/**
	* Minimum Length
	*
	* @param	string
	* @param	string
	* @return	bool
	*/
	public function min_length($str, $val) {
		$this->set_message('min_length', 'The %s field must be at least %s characters in length.');

		if (! is_numeric($val)) {
			return FALSE;
		}

		return (MB_ENABLED === TRUE)
			? ($val <= mb_strlen($str))
			: ($val <= strlen($str));
	}

	// --------------------------------------------------------------------

	/**
	* Max Length
	*
	* @param	string
	* @param	string
	* @return	bool
	*/
	public function max_length($str, $val) {
		$this->set_message('max_length', 'The %s field cannot exceed %s characters in length.');

		if (! is_numeric($val)) {
			return FALSE;
		}

		return (MB_ENABLED === TRUE)
			? ($val >= mb_strlen($str))
			: ($val >= strlen($str));
	}

	// --------------------------------------------------------------------

	/**
	* Exact Length
	*
	* @param	string
	* @param	string
	* @return	bool
	*/
	public function exact_length($str, $val) {
		$this->set_message('exact_length', 'The %s field must be exactly %s characters in length.');

		if (! is_numeric($val)) {
			return FALSE;
		}

		return (MB_ENABLED === TRUE)
			? (mb_strlen($str) === (int) $val)
			: (strlen($str) === (int) $val);
	}

	// --------------------------------------------------------------------

	/**
	* Valid URL
	*
	* @param	string	$str
	* @return	bool
	*/
	public function valid_url($str) {
		$this->set_message('valid_url', 'The %s field must contain a valid URL.');

		if (empty($str)) {
			return FALSE;
		} elseif (preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches)) {
			if (empty($matches[2])) {
				return FALSE;
			} elseif (! in_array($matches[1], ['http', 'https'], true)) {
				return FALSE;
			}

			$str = $matches[2];
		}

		$str = 'http://'.$str;

		// There's a bug affecting PHP 5.2.13, 5.3.2 that considers the
		// underscore to be a valid hostname character instead of a dash.
		// Reference: https://bugs.php.net/bug.php?id=51192
		if (version_compare(PHP_VERSION, '5.2.13', '==') or version_compare(PHP_VERSION, '5.3.2', '==')) {
			sscanf($str, 'http://%[^/]', $host);
			$str = substr_replace($str, strtr($host, ['_' => '-', '-' => '_']), 7, strlen($host));
		}

		return (filter_var($str, FILTER_VALIDATE_URL) !== FALSE);
	}

	// --------------------------------------------------------------------

	/**
	* Valid Email
	*
	* @param	string
	* @return	bool
	*/
	public function valid_email($str) {
		$this->set_message('valid_email', 'The %s field must contain a valid email address.');

		if (function_exists('idn_to_ascii') && $atpos = strpos($str, '@')) {
			$str = substr($str, 0, ++$atpos).idn_to_ascii(substr($str, $atpos));
		}

		return (bool) filter_var($str, FILTER_VALIDATE_EMAIL);
	}

	// --------------------------------------------------------------------

	/**
	* Valid Emails
	*
	* @param	string
	* @return	bool
	*/
	public function valid_emails($str) {
		$this->set_message('valid_emails', 'The %s field must contain all valid email addresses.');

		if (strpos($str, ',') === FALSE) {
			return $this->valid_email(trim($str));
		}

		foreach (explode(',', $str) as $email) {
			if (trim($email) !== '' && $this->valid_email(trim($email)) === FALSE) {
				return FALSE;
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	* Validate IP Address
	*
	* @param	string
	* @param	string	'ipv4' or 'ipv6' to validate a specific IP format
	* @return	bool
	*/
	public function valid_ip($ip, $which = '') {
		$this->set_message('valid_ip', 'The %s field must contain a valid IP.');

		return $this->ci_input->valid_ip($ip, $which);
	}

	// --------------------------------------------------------------------

	/**
	* Alpha
	*
	* @param	string
	* @return	bool
	*/
	public function alpha($str) {
		$this->set_message('alpha', 'The %s field may only contain alphabetical characters.');

		return ctype_alpha($str);
	}

	// --------------------------------------------------------------------

	/**
	* Alpha-numeric
	*
	* @param	string
	* @return	bool
	*/
	public function alpha_numeric($str) {
		$this->set_message('alpha_numeric', 'The %s field may only contain alpha-numeric characters.');

		return ctype_alnum((string) $str);
	}

	// --------------------------------------------------------------------

	/**
	* Alpha-numeric w/ spaces
	*
	* @param	string
	* @return	bool
	*/
	public function alpha_numeric_spaces($str) {
		$this->set_message('alpha_numeric_spaces', 'The %s field may only contain alpha-numeric characters and spaces.');

		return (bool) preg_match('/^[A-Z0-9 ]+$/i', $str);
	}

	// --------------------------------------------------------------------

	/**
	* Alpha-numeric with underscores and dashes
	*
	* @param	string
	* @return	bool
	*/
	public function alpha_dash($str) {
		$this->set_message('alpha_dash', 'The %s field may only contain alpha-numeric characters, underscores, and dashes.');

		return (bool) preg_match('/^[a-z0-9_-]+$/i', $str);
	}

	// --------------------------------------------------------------------

	/**
	* Numeric
	*
	* @param	string
	* @return	bool
	*/
	public function numeric($str) {
		$this->set_message('numeric', 'The %s field must contain only numeric characters.');

		return (bool) preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $str);
	}

	// --------------------------------------------------------------------

	/**
	* Integer
	*
	* @param	string
	* @return	bool
	*/
	public function integer($str) {
		$this->set_message('integer', 'The %s field must contain an integer.');

		return (bool) preg_match('/^[\-+]?[0-9]+$/', $str);
	}

	// --------------------------------------------------------------------

	/**
	* Decimal number
	*
	* @param	string
	* @return	bool
	*/
	public function decimal($str) {
		$this->set_message('decimal', 'The %s field must contain a decimal number.');

		return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $str);
	}

	// --------------------------------------------------------------------

	/**
	* Greater than
	*
	* @param	string
	* @param	int
	* @return	bool
	*/
	public function greater_than($str, $min) {
		$this->set_message('greater_than', 'The %s field must contain a number greater than %s.');

		return is_numeric($str) ? ($str > $min) : false;
	}

	// --------------------------------------------------------------------

	/**
	* Equal to or Greater than
	*
	* @param	string
	* @param	int
	* @return	bool
	*/
	public function greater_than_equal_to($str, $min) {
		$this->set_message('greater_than_equal_to', 'The %s field must contain a number greater than or equal to %s.');

		return is_numeric($str) ? ($str >= $min) : false;
	}

	// --------------------------------------------------------------------

	/**
	* Less than
	*
	* @param	string
	* @param	int
	* @return	bool
	*/
	public function less_than($str, $max) {
		$this->set_message('less_than', 'The %s field must contain a number less than %s.');

		return is_numeric($str) ? ($str < $max) : false;
	}

	// --------------------------------------------------------------------

	/**
	* Equal to or Less than
	*
	* @param	string
	* @param	int
	* @return	bool
	*/
	public function less_than_equal_to($str, $max) {
		$this->set_message('less_than_equal_to', 'The %s field must contain a number less than or equal to %s.');

		return is_numeric($str) ? ($str <= $max) : false;
	}

	// --------------------------------------------------------------------

	/**
	* Is a Natural number  (0,1,2,3, etc.)
	*
	* @param	string
	* @return	bool
	*/
	public function is_natural($str) {
		$this->set_message('is_natural', 'The %s field must only contain digits.');

		return ctype_digit((string) $str);
	}

	// --------------------------------------------------------------------

	/**
	* Is a Natural number, but not a zero  (1,2,3, etc.)
	*
	* @param	string
	* @return	bool
	*/
	public function is_natural_no_zero($str) {
		$this->set_message('is_natural_no_zero', 'The %s field must only contain digits and must be greater than zero.');

		return ($str != 0 && ctype_digit((string) $str));
	}

	// --------------------------------------------------------------------

	/**
	* Valid Base64
	*
	* Tests a string for characters outside of the Base64 alphabet
	* as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
	*
	* @param	string
	* @return	bool
	*/
	public function valid_base64($str) {
		$this->set_message('valid_base64', 'The %s field is not valid Base64.');

		return (base64_encode(base64_decode($str)) === $str);
	}

	// --------------------------------------------------------------------

	/**
	* Prep URL
	*
	* @param	string
	* @return	string
	*/
	public function prep_url(&$str) {
		if ($str === 'http://' or $str === '') {
			$str = '';
		}

		if (strpos($str, 'http://') !== 0 && strpos($str, 'https://') !== 0) {
			$str = 'http://'.$str;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	* Strip Image Tags
	*
	* @param	string
	* @return	string
	*/
	public function strip_image_tags(&$str) {
		$str = $this->ci_security->strip_image_tags($str);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	* XSS Clean
	*
	* @param	string
	* @return	string
	*/
	public function xss_clean(&$str) {
		$str = $this->ci_security->xss_clean($str);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	* Convert PHP tags to entities
	*
	* @param	string
	* @return	string
	*/
	public function encode_php_tags(&$str) {
		$str = str_replace(['<?', '?>'], ['&lt;?', '?&gt;'], $str);

		return TRUE;
	}
}