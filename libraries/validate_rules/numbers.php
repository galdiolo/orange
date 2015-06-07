<?php
/*
Additional PHP functions include

*validate
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
trait validate_numbers {
	public function dollars($field, $options = null) {
		$this->set_message('dollars', 'The %s Out of Range.');

		return (bool) preg_match('#^\$?\d+(\.(\d{2}))?$#', $field);
	}

	public function percent($field, $options = null) {
		$this->set_message('percent', 'The %s Out of Range.');

		return (bool) preg_match('#^\s*(\d{0,2})(\.?(\d*))?\s*\%?$#', $field);
	}

	public function zip($field, $options = null) {
		$this->set_message('zip', 'The %s is invalid.');

		return (bool) preg_match('#^\d{5}$|^\d{5}-\d{4}$#', $field);
	}

	public function phone($field, $options = null) {
		$this->set_message('phone', 'The %s is invalid.');

		return (bool) preg_match('/^\(?[\d]{3}\)?[\s-]?[\d]{3}[\s-]?[\d]{4}$/', $field);
	}

	public function is_between($field, $options = null) {
		list($lo, $hi) = explode(',', $options, 2);

		$this->set_message('is_between', '%s must be between '.$lo.' &amp; '.$hi);

		return (bool) ($field <= $hi && $field >= $lo);
	}

	public function is_outside($field, $options = null) {
		list($lo, $hi) = explode(',', $options, 2);

		$this->set_message('is_outside', '%s must not be between '.$lo.' &amp; '.$hi);

		return (bool) ($field > $hi || $field < $lo);
	}

	public function even($field, $options = null) {
		$this->set_message('even', '%s is not a even number.');

		return ((int) $field % 2 === 0);
	}

	public function odd($field, $options = null) {
		$this->set_message('odd', '%s is not a odd number.');

		return ((int) $field % 2 !== 0);
	}

	public function int($field, $options = null) {
		$this->set_message('int', '%s is not a integer.');

		return is_numeric($field) && (int) $field == $field;
	}

	public function float($field, $options = null) {
		$this->set_message('float', '%s is not a floating number.');

		return is_float(filter_var($field, FILTER_VALIDATE_FLOAT));
	}

	public function version($field, $options = null) {
		$this->set_message('version', '%s is not a valid version number.');

		return (bool) preg_match('/^[0-9]+\.[0-9]+\.[0-9]+([+-][^+-][0-9A-Za-z-.]*)?$/', $field);
	}

	public function credit_card($field, $options = null) {
		$this->set_message('credit_card', '%s is not a valid credit card number.');

		$field = preg_replace('([^0-9])', '', $field);

		return (!empty($field)) ? $this->_verifyMod10($field) : false;
	}

	protected function _verifyMod10($input) {
		$sum   = 0;
		$input = strrev($input);
		for ($i = 0; $i < strlen($input); $i++) {
			$current = substr($input, $i, 1);
			if ($i % 2 == 1) {
				$current *= 2;
				if ($current > 9) {
					$firstDigit  = $current % 10;
					$secondDigit = ($current - $firstDigit) / 10;
					$current     = $firstDigit + $secondDigit;
				}
			}
			$sum += $current;
		}

		return ($sum % 10 == 0);
	}
} /* end time */