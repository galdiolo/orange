<?php
/*
Additional PHP functions include

empty
is_array
is_bool
is_double
is_float
is_int
is_integer
is_long
is_null
is_numeric
is_real
is_scalar
is_string
isset

*/
trait validate_string {
	/* filter */
	public function length(&$inp, $options = null) {
		/* 2k default */
		$inp = substr($inp, 0, ($options = (!$options) ? 2048 : $options));

		return true;
	}

	public function hexcolor($field, $options = null) {
		$this->set_message('hexcolor', '%s is not a hex value.');

		return (bool) preg_match('/^#?[a-fA-F0-9]{3,6}$/', $field);
	}

	public function md5($field, $options = null) {
		$options = ($options) ? $options : 32;
	
		$this->set_message(); /* default message */

		return (bool) preg_match('/^([a-fA-F0-9]{'.(int)$options.'})$/', $field);
	}

	public function alpha_extra($field, $options = null) {
		// Alpha-numeric with periods, underscores, spaces and dashes
		$this->set_message('alpha_extra', '%s may only contain alpha-numeric characters, spaces, periods, underscores, and dashes.');

		return (bool) preg_match("/^([\.\s-a-z0-9_-])+$/i", $field);
	}

	public function uri($field, $options = null) {
		$this->set_message('uri', '%s is an invalid uniform resource identifier');

		return (bool) (preg_match("#^/[0-9a-z_*/]*$#", $field));
	}

	public function url($field, $options = null) {
		$this->set_message('url', '%s is a invalid url.');

		return (bool) (preg_match('#^([\.\/-a-z0-9_*-])+$#i', $field));
	}

	public function ip($field, $options = null) {
		/* *.*.*.*, 10.1.1.*, 10.*.*.*, etc... */
		$this->set_message('%s is a invalid ip.');

		$sections = explode('.', $field);
		$match = ($options) ? explode('.', $options) : ['*','*','*','*'];

		if (count($sections) != 4 || count($match) != 4) {
			return false;
		}

		for ($idx = 0;$idx <= 3;$idx++) {
			if ($match[$idx] != '*' && $sections[$idx] != $match[$idx]) {
				return false;
			}
		}

		return true;
	}

	public function json($field, $options = null) {
		$this->set_message('json', '%s is invalid json');

		return (bool) (json_decode($field));
	}

	public function ends_with($field, $options = null) {
		$this->set_message('ends_with', '%s must end with '.$options);

		return (bool) ($options == substr($field, -strlen($options)));
	}

	public function starts_with($field, $options = null) {
		$this->set_message('starts_with', '%s must start with '.$options);

		return (bool) (substr($field, 0, strlen($options)) == $options);
	}

	public function contains($field, $options = null) {
		$this->set_message('contains', '%s must contain '.$options);

		return (bool) (strpos($field, $options) !== false) ? true : false;
	}
} /* end class */