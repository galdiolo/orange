<?php
/*
Additional PHP functions include

*prep
trim
base64_encode
base64_decode
md5
strtolower
strtouppper
ucwords
strtotime
ucfirst
lcfirst
ltrim
rtrim

*/
trait validate_filter {
	private $true_array_filter = [1,'1','y','on','yes','t','true',true];

	public function filter(&$inp, $strip = null) {
		$inp = str_replace(str_split($strip), '', $inp);

		return TRUE;
	}

	public function filter_except(&$inp, $except = '') {
		$inp = preg_replace("/[^".preg_quote($except, "/")."]/", '', $inp);

		return TRUE;
	}

	/* SPECIAL Filter which converts a hmac back to it's value */
	public function filter_hmac(&$inp, $options = null) {
		$success = true;

		/* if it dosn't start with out HMAC prefix then just return TRUE and don't modify it */
		if (substr($inp, 0, 3) === '$H$') {
			$key = $this->ci_config->item('encryption_key');

			list($value, $hmac) = explode(chr(0), base64_decode(substr($inp, 3)), 2);

			if (md5($value.$key) === $hmac) {
				$inp = $value;
			} else {
				$inp     = null;
				$success = false;
			}
		}

		return $success;
	}

	/* transpose characters */
	public function filter_replace(&$inp, $options = null) {
		/* built the key value pair */
		$items = explode(',', $options);

		$idx    = 0;
		$keys   = [];
		$values = [];

		foreach ($items as $item) {
			$idx++;
			if ($idx % 2) {
				$keys[] = $item;
			} else {
				$values[] = $item;
			}
		}

		if (count($keys) > 0 && count($values) > 0) {
			$inp = str_replace($keys, $values, $inp);
		}

		return TRUE;
	}

	/* filters uri/url and removes any extra trailing / */
	public function filter_uri(&$inp, $length = null) {
		$inp = '/'.trim(trim(strtolower($inp)), '/');
		$inp = preg_replace("#^/^[0-9a-z_*/]*$#", '', $inp);
		$inp = $this->_filter_length($inp, $length);

		return TRUE;
	}

	public function filter_url_safe(&$inp, $length = null) {
		$strip = '~`!@$^()* {}[]|\;"\'<>,';

		$inp = $this->_filter_string($inp, false, $length, $strip);

		return TRUE;
	}

	/* Filters - remember you can combine these in the mapping etc... */
	public function filter_int(&$inp, $length = null) {
		$pos = strpos($inp, '.');

		if ($pos !== FALSE) {
			$inp = substr($inp, 0, $pos);
		}

		$inp = preg_replace('/[^\-\+0-9]+/', '', $inp);

		$prefix = ($inp[0] == '-' || $inp[0] == '+') ? $inp[0] : '';

		$inp = $this->_filter_length($prefix.preg_replace('/[^0-9]+/', '', $inp), $length);

		return TRUE;
	}

	public function filter_float(&$inp, $length = null) {
		$inp = preg_replace('/[^\-\+0-9.]+/', '', $inp);

		$prefix = ($inp[0] == '-' || $inp[0] == '+') ? $inp[0] : '';

		$inp = $this->_filter_length($prefix.preg_replace('/[^0-9.]+/', '', $inp), $length);

		return TRUE;
	}

	public function filter_bol(&$inp) {
		$inp = ($this->is_bol($inp)) ? $inp : null;

		return TRUE;
	}

	public function filter_bol2int(&$inp, $length = null) {
		$inp = (in_array(strtolower($inp), $this->true_array_filter, true)) ? 1 : 0;

		return TRUE;
	}

	public function filter_bol2bol(&$inp, $length = null) {
		$inp = (in_array(strtolower($inp), $this->true_array_filter, true)) ? true : false;

		return TRUE;
	}

	/* This will Strip tags, Line Feeds and anything else below 32 and above 127 - good for input type=text */
	public function filter_input(&$inp, $length = null) {
		$inp = $this->_filter_string($inp, false, $length);

		return TRUE;
	}

	/* This will Strip tags and anything below 32 EXCEPT linefeeds and above 127 */
	public function filter_str(&$inp, $length = null) {
		$inp = $this->_filter_string($inp, true, $length);

		return TRUE;
	}

	/* just a wrapper for filter_str -- make it look like a textarea filter */
	public function filter_textarea(&$inp, $length = null) {
		$inp = $this->_filter_string($inp, true, $length);

		return TRUE;
	}

	public function filter_filename(&$inp, $length = null) {
		$inp = preg_replace('/[^\x20-\x7F]/', '', $inp);
		$inp = str_replace(str_split('~`!@$^()* {}[]|\;"\'<>,'), ' ', $inp);
		$inp = $this->_filter_length($inp, $length);

		return TRUE;
	}

	public function filter_email(&$inp, $length = null) {
		$strip = '~!#$%^&*()+=`[]{}:";\'<>,/?|';

		$inp = str_replace(str_split($strip), '', filter_var($inp, FILTER_SANITIZE_EMAIL));

		$pos = strpos($inp, '@');

		if ($pos !== FALSE) {
			$inp = substr($inp, 0, $pos + 1).str_replace('@', '', substr($inp, $pos + 1));
		}

		$inp = $this->_filter_length($inp, $length);

		return TRUE;
	}

	public function filter_phone(&$inp, $length = null) {
		$inp = preg_replace('/[^0-9x]+/', ' ', $inp);
		$inp = preg_replace('/ {2,}/', ' ', $inp);

		$inp = $this->_filter_string(trim($inp), false, $length);

		return TRUE;
	}

	public function filter_hex(&$inp, $length = null) {
		$inp = $this->_filter_length(preg_replace('/[^0-9a-f]+/', '', strtolower(trim($inp)), $length));

		return TRUE;
	}

	public function filter_trim(&$inp, $length = null) {
		$inp = $this->_filter_length(trim($inp), $length);

		return TRUE;
	}

	public function filter_uppercase(&$inp, $length = null) {
		$inp = $this->_filter_length(strtoupper($inp), $length);

		return TRUE;
	}

	public function filter_lowercase(&$inp, $length = null) {
		$inp = $this->_filter_length(strtolower($inp), $length);

		return TRUE;
	}

	public function filter_words(&$inp, $length = null) {
		$inp = $this->_filter_length(ucwords($inp), $length);

		return TRUE;
	}

	public function filter_strtotime(&$inp, $length = null) {
		$inp = $this->_filter_length(strtotime($inp), $length);

		return TRUE;
	}

	public function filter_length(&$inp, $length = null) {
		$inp = $this->_filter_length($inp, $length);

		return TRUE;
	}

	public function _filter_string($inp, $tabfeed = false, $length = null, $extra = null) {
		if (!empty($inp)) {
			$a = [chr(9),chr(10),chr(13)];
			$b = ['##chr9##','##chr10##','##chr13##'];

			if ($tabfeed) {
				$inp = str_replace($a, $b, $inp);
			}

			$inp = preg_replace('/[^\x20-\x7F]/', '', $inp);

			if ($tabfeed) {
				$inp = str_replace($b, $a, $inp);
			}

			if ($extra) {
				$inp = str_replace(str_split($extra), '', $inp);
			}

			if ($length) {
				$inp = $this->_filter_length($inp, $length);
			}
		}

		return $inp;
	}

	public function _filter_length($inp, $length = null) {
		$length = (is_numeric($length)) ? $length : 255;

		return substr($inp, 0, $length);
	}
} /* end time */