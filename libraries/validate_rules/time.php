<?php
trait validate_time {
	/* filter */
	public function convert_date(&$field, $options = null) {
		$options = ($options) ? $options : 'Y-m-d H:i:s';

		$field = date($options,strtotime($field));

		return true;
	}

	public function valid_time($field, $options = null) {
		$this->set_message('valid_time', '%s is a invalid time.');

		return (bool) (strtotime($field) > 1);
	}

	public function valid_date($field, $options = null) {
		$this->set_message('valid_date', '%s is a invalid date.');

		/* basic format check */
		if (!preg_match('/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}$/', $field)) {
			return false;
		}

		list($d, $m, $y) = explode('/', $field);

		return checkdate($d, $m, $y);
	}

	public function valid_datetime($field, $options = null) {
		$this->set_message();

		/*
		optionally we are saying 0000-00-00 00:00:00 is valid
		this could be helpful as a "default" or "empty" value
		*/

		return ($field == '0000-00-00 00:00:00') ? true : (strtotime($field) > 1);
	}

	public function valid_dob($field, $options = null) {
		$yrs = ($options) ? $options : 18;

		$this->set_message('valid_dob', '%s must be more than '.$yrs.' years.');

		/* is this a valid date? strtotime */
		if (!strtotime($field)) {
			return false;
		}
		
		/* less than the time */
		if (strtotime($field) > strtotime('-'.$yrs.' year', time())) {
			return false;
		}
		
		/* greater than a super old person */
		if (strtotime($field) < strtotime('-127 year', time())) {
			return false;
		}

		return true;
	}
	
	/*
	is_after_date (now format F j, Y, g:ia)
	is_after_date[F j,Y]
	is_after_date[-1 year@M-D-Y]
	*/
	public function is_after_date($field, $options = null) {
		$format = 'F j, Y, g:ia';
		$time = strtotime('now');
		$error = 'now';

		if (strpos($options,'@') !== false) {
			list($time,$format) = explode('@',$options,2);
			$time = strtotime($time);
			$error = date($format,$time);
		}

		$this->set_message('is_after_date', '%s must be after '.$error.'.');

		return (!strtotime($field)) ? false : (strtotime($field) > $time);
	}

	/*
	is_before_date (now format F j, Y, g:ia)
	is_before_date[F j,Y]
	is_before_date[-1 year@M-D-Y]
	*/
	public function is_before_date($field, $options = null) {
		$format = 'F j, Y, g:ia';
		$time = strtotime('now');
		$error = 'now';

		if (strpos($options,'@') !== false) {
			list($time,$format) = explode('@',$options,2);
			$time = strtotime($time);
			$error = date($format,$time);
		}

		$this->set_message('is_before_date', '%s must be before '.$error.'.');

		return (!strtotime($field)) ? false : (strtotime($field) < $time);
	}

	/* is_between_date[2014-12-25,2015-12-25] */
	public function is_between_dates($field, $options = null) {
		list($after, $before) = explode(',', $options);

		$this->set_message('is_between_dates', '%s must be between '.date('F j, Y', strtotime($after)).' and '.date('F j, Y', strtotime($before)).'.');
		
		/* are either of these not valid times? */
		if (!strtotime($after) || !strtotime($before)) {
			return false;
		}

		$is_after  = (strtotime($field) > strtotime($after)) ? true : false;
		$is_before = (strtotime($field) < strtotime($before)) ? true : false;

		return (bool) ($is_after && $is_before);
	}
} /* end class */