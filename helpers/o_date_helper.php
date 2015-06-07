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
/**
* Some code copyright Fuel CMS - see copyright below
*
* @author		David McReynolds @ Daylight Studio
* @copyright	Copyright (c) 2014, Run for Daylight LLC.
* @license		http://docs.getfuelcms.com/general/license
* @link		http://www.getfuelcms.com
*/

const MONTHS_PER_YEAR    = 12;
const WEEKS_PER_YEAR     = 52;
const DAYS_PER_WEEK      = 7;
const HOURS_PER_DAY      = 24;
const MINUTES_PER_HOUR   = 60;
const SECONDS_PER_MINUTE = 60;
const SECONDS_PER_DAY    = 86400;

function yesterday() {
	return date('U') - SECONDS_PER_DAY;
}

function tomorrow() {
	return date('U') + SECONDS_PER_DAY;
}

function english_datetime($datetime) {
	return date("F j, Y, g:ia", strtotime($datetime));
}

/**
* Returns the current datetime value in MySQL format
*
* @access	public
* @return	string
*/
function datetime_now($hms = true) {
	if ($hms) {
		return date("Y-m-d H:i:s");
	} else {
		return date("Y-m-d");
	}
}

/**
* Test for common date format
*
* @access	public
* @param	string
* @return	boolean
*/
function is_date_format($date) {
	return (is_string($date) and (!empty($date) and (int) $date != 0) and
	(is_date_english_format($date) or is_date_db_format($date)));
}

/**
* Test for MySQL date format
*
* @access	public
* @param	string
* @return	boolean
*/
function is_date_db_format($date) {
	return preg_match("#([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})#", $date);
}

/**
* Test for mm/dd/yyyy format
*
* @access	public
* @param	string
* @return	boolean
*/
function is_date_english_format($date) {
	return preg_match("#([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})#", $date);
}

/**
* Returns date in mm/dd/yyy format
*
* @access	public
* @param	string
* @param	boolean
* @param	string
* @param	string
* @return	string
*/
function english_date($date, $long = false, $timezone = null, $delimiter = '/') {
	if (!is_numeric($date) and !preg_match("/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})/", $date, $regs)) {
		return FALSE;
	}
	if (!empty($date)) {
		$date_ts = (!is_numeric($date)) ? strtotime($date) : $date;
		if (strtolower($timezone) == 'auto') {
			$timezone = date('e');
		}
		if (!$long) {
			return date("m".$delimiter."d".$delimiter."Y", $date_ts).' '.$timezone;
		} else {
			return date("m".$delimiter."d".$delimiter."Y h:i a", $date_ts).' '.$timezone;
		}
	} else {
		return FALSE;
	}
}

/**
* Returns date in 'verbose' (e.g. Jan. 1, 2010) format
*
* @access	public
* @param	string
* @return	boolean
*/
function english_date_verbose($date) {
	$date_ts = (!is_numeric($date)) ? strtotime($date) : $date;
	if (!empty($date)) {
		return date("M. d, Y", $date_ts);
	} else {
		return FALSE;
	}
}

function secondsToTime($inputSeconds) {
	$secondsInAMinute = 60;
	$secondsInAnHour  = 60 * $secondsInAMinute;
	$secondsInADay    = 24 * $secondsInAnHour;

	// extract days
	$days = floor($inputSeconds / $secondsInADay);

	// extract hours
	$hourSeconds = $inputSeconds % $secondsInADay;
	$hours = floor($hourSeconds / $secondsInAnHour);

	// extract minutes
	$minuteSeconds = $hourSeconds % $secondsInAnHour;
	$minutes = floor($minuteSeconds / $secondsInAMinute);

	// extract the remaining seconds
	$remainingSeconds = $minuteSeconds % $secondsInAMinute;
	$seconds = ceil($remainingSeconds);

	// return the final array
	$obj = array(
			'd' => (int) $days,
			'h' => (int) $hours,
			'm' => (int) $minutes,
			's' => (int) $seconds,
	);
	return $obj;
}

function seconds2human($ss) {
	$s = $ss%60;
	$m = floor(($ss%3600)/60);
	$h = floor(($ss%86400)/3600);
	$d = floor(($ss%2592000)/86400);
	$M = floor($ss/2592000);

	$output = [];

	if ($M > 0) {
		$output[] = $M.' months';
	}

	if ($d == 1) {
		$output[] = $d.' day';
	}

	if ($d > 1) {
		$output[] = $d.' days';
	}

	if ($h == 1) {
		$output[] = $h.' hour';
	}

	if ($h > 1) {
		$output[] = $h.' hours';
	}

	if ($m == 1) {
		$output[] = $m.' minute';
	}

	if ($m > 1) {
		$output[] = $m.' minutes';
	}

	if ($s == 1) {
		$output[] = $s.' second';
	}

	if ($s > 1) {
		$output[] = $s.' seconds';
	}


	return implode(',', $output);
}

/**
* Returns the time into a verbose format (e.g. 12hrs 10mins 10secs)
*
* must be passed a string in hh:mm format
*
* @access	public
* @param	string
* @param	boolean
* @return	boolean
*/
function time_verbose($time, $include_seconds = false) {
	if (is_date_format($time)) {
		$time = strtotime($time);
	}
	if (is_int($time)) {
		$time = date('H:i:s', $time);
	}

	$hms = explode(':', $time);
	if (empty($hms)) {
		return $time;
	}
	$h        = (int) $hms[0];
	$m        = (!empty($hms[1])) ? (int) $hms[1] : 0;
	$s        = (!empty($hms[2])) ? (int) $hms[2] : 0;
	$new_time = '';
	if ($h != 0) {
		$new_time .= $h.'hrs ';
	}
	if ($m != 0) {
		$new_time .= $m.'mins ';
	}
	if ($include_seconds and $s != 0) {
		$new_time .= $s.'secs';
	}

	return $new_time;
}

/**
* Converts a date from english (e.g. mm/dd/yyyy) to db format (e.g yyyy-mm-dd)
*
* @access	public
* @param	string
* @param	int
* @param	int
* @param	int
* @param	string
* @param	string
* @return	string
*/
function english_date_to_db_format($date, $hour = 0, $min = 0, $sec = 0, $ampm = 'am', $delimiter = '/') {
	$hour = (int) $hour;
	$min  = (int) $min;
	$sec  = (int) $sec;
	if ($hour > 12) {
		$ampm = 'pm';
	}
	if ($ampm == 'pm' and $hour < 12) {
		$hour += 12;
	} elseif ($ampm == 'am' and $hour == 12) {
		$hour = 0;
	}
	$date_arr = explode($delimiter, $date);
	foreach ($date_arr as $key => $val) {
		$date_arr[$key] = (int) $date_arr[$key]; // convert to integer
	}
	if (count($date_arr) != 3) {
		return 'invalid';
	}

	if (!checkdate($date_arr[0], $date_arr[1], $date_arr[2])) {
		return 'invalid'; // null will cause it to be ignored in validation
	}
	$new_date = $date_arr[2].'-'.$date_arr[0].'-'.$date_arr[1].' '.$hour.':'.$min.':'.$sec;
	$date     = date("Y-m-d H:i:s", strtotime($new_date)); // normalize
	return $date;
}

/**
* Formats a date into yyyy-mm-dd hh:mm:ss format
*
* @access	public
* @param	int
* @param	int
* @param	int
* @param	int
* @param	int
* @param	int
* @return	string
*/
// formats a date into a mysql date
function format_db_date($y = null, $m = null, $d = null, $h = null, $i = null, $s = null) {
	if (empty($m) and !empty($y)) {
		$dates = convert_date_to_array($y);
		$str   = $dates['year'].'-'.$dates['month'].'-'.$dates['day'].' '.$dates['hour'].':'.$dates['min'].':'.$dates['sec'];
	} else {
		if (empty($y)) {
			return date("Y-m-d H:i:s");
		}
		$time = time();
		$y    = is_numeric($y) ? $y : date('Y', $time);
		$m    = is_numeric($m) ? $m : date('m', $time);
		$m    = sprintf("%02s",  $m);
		$d    = is_numeric($d) ? $d : date('d', $time);
		$d    = sprintf("%02s",  $d);
		$str  = $y.'-'.$m.'-'.$d;
		if (isset($h)) {
			$h = is_numeric($h) ? $h : date('H', $time);
			$i = is_numeric($i) ? $i : date('i', $time);
			$s = is_numeric($s) ? $s : date('s', $time);
			$h = sprintf("%02s",  $h);
			$i = sprintf("%02s",  $i);
			$s = sprintf("%02s",  $s);
			$str .= ' '.$h.':'.$i.':'.$s;
		}
	}

	return $str;
}

/**
* Creates a date range string (e.g. January 1-10, 2010)
*
* @access	public
* @param	string
* @param	string
* @return	string
*/
function date_range_string($date1, $date2) {
	$date1TS = (is_string($date1)) ? strtotime($date1) : $date1;
	$date2TS = (is_string($date2)) ? strtotime($date2) : $date2;

	if (date('Y-m-d', $date1TS) == date('Y-m-d', $date2TS)) {
		return date('F j, Y', $date1TS);
	}
	if (date('m/Y', $date1TS) == date('m/Y', $date2TS)) {
		return date('F j', $date1TS).'-'.date('j, Y', $date2TS);
	} elseif (date('Y', $date1TS) == date('Y', $date2TS)) {
		return date('F j', $date1TS)."-".date('F j, Y', $date2TS);
	} else {
		return date('F j, Y', $date1TS).'-'.date('F j, Y', $date2TS);
	}
}

/**
* Creates an array containing a date range
*
* @access public
* @param string $start, any format that strtotime accepts
* @param string $end, any format that strtotime accepts
* @param string $increments, any format that strtotime accepts
* @param string $output_format, default output format is YYYY-MM-DD
* @return array
*/
function date_range_array($start = 'now', $end = '+1 year', $output_format = 'Y-m-d', $increments = '+1 month') {
	if (isset($start, $end, $output_format, $increments)) {
		$current          = strtotime($start);
		$end              = strtotime($end);
		$date_range_array = [];
		while ($current <= $end) {
			$date_range_array[] = date($output_format, $current);
			$current            = strtotime($increments, $current);
		}

		return $date_range_array;
	}
}

/**
* Creates a string based on how long from the current time the date provided.
*
* (e.g. 10 minutes ago)
*
* @access	public
* @param	string
* @param	booelan
* @return	string
*/
function pretty_date($timestamp, $use_gmt = false) {
	if (is_string($timestamp)) {
		$timestamp = strtotime($timestamp);
	}
	$now      = ($use_gmt) ? mktime() : time();
	$diff     = $now - $timestamp;
	$day_diff = floor($diff/86400);

	// don't go beyond '
	if ($day_diff < 0) {
		return;
	}

	if ($diff < 60) {
		return 'just now';
	} elseif ($diff < 120) {
		return '1 minute ago';
	} elseif ($diff < 3600) {
		return floor($diff / 60).' minutes ago';
	} elseif ($diff < 7200) {
		return '1 hour ago';
	} elseif ($diff < 86400) {
		return floor($diff / 3600).' hours ago';
	} elseif ($day_diff == 1) {
		return 'Yesterday';
	} elseif ($day_diff < 7) {
		return $day_diff." days ago";
	} else {
		return ceil($day_diff / 7).' weeks ago';
	}
}

/**
* Calculate the age between 2 dates
*
* @access	public
* @param	int
* @param	int
* @return	string
*/
function get_age($bday_ts, $at_time_ts = null) {
	if (empty($at_time_ts)) {
		$at_time_ts = time();
	}
	if (is_string($bday_ts)) {
		$bday_ts = strtotime($bday_ts);
	}

	// See http://php.net/date for what the first arguments mean.
	$diff_year   = date('Y', $at_time_ts) - date('Y', $bday_ts);
	$diff_month  = date('n', $at_time_ts) - date('n', $bday_ts);
	$diff_day    = date('j', $at_time_ts) - date('j', $bday_ts);

	// If birthday has not happened yet for this year, subtract 1.
	if ($diff_month < 0 or ($diff_month == 0 and $diff_day < 0)) {
		$diff_year--;
	}

	return $diff_year;
}

/**
* Return the difference between two dates.
*
* @todo Consider updating this to use date_diff() and/or DateInterval.
*
* @param mixed  $start    The start date as a unix timestamp or in a format
* which can be used within strtotime().
* @param mixed  $end      The ending date as a unix timestamp or in a
* format which can be used within strtotime().
* @param string $interval A string with the interval to use. Valid values
* are 'week', 'day', 'hour', or 'minute'.
* @param bool   $reformat If TRUE, will reformat the time using strtotime().
*
* @return int A number representing the difference between the two dates in
* the interval desired.
*/
function date_difference($start = null, $end = null, $interval = 'day', $reformat = false) {
	if (is_null($start)) {
		return false;
	}

	if (is_null($end)) {
		$end = date('Y-m-d H:i:s');
	}

	$times = [
		'week'        => 604800,
		'day'         => 86400,
		'hour'        => 3600,
		'minute'      => 60,
	];

	if ($reformat === true) {
		$start = strtotime($start);
		$end   = strtotime($end);
	}

	$diff = $end - $start;

	return round($diff / $times[$interval]);
}

/* End of file MY_date_helper.php */
/* Location: ./application/helpers/MY_date_helper.php */