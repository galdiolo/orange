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

if (!function_exists('after')) {
	function after($tag, $searchthis) {
		if (!is_bool(strpos($searchthis, $tag))) {
			return substr($searchthis, strpos($searchthis, $tag)+strlen($tag));
		}
	}
}

if (!function_exists('before')) {
	function before($tag, $searchthis) {
		return substr($searchthis, 0, strpos($searchthis, $tag));
	}
}

if (!function_exists('between')) {
	function between($tag, $that, $searchthis) {
		return before($that, after($tag, $searchthis));
	}
}

if (!function_exists('nthfield')) {
	function nthfield($string, $spliton, $number) {
		$number--;
	
		$list = explode($spliton, $string);
	
		return $list{$number};
	}
}

/*
lambda function

$field = '{a} + {b}';
$data = ['a'=>24,'b'=>89,'c'=>12'];

$field = 'substr({a},strlen({a}) + 1,-1)';
$field = 'ci()->user->name';

*/
if (!function_exists('formula')) {
	function formula($field, $data=[]) {
		/* put the fields in the place holders */
		foreach ($data as $key => $val) {
			$field = str_replace('{'.$key.'}', "'".str_replace("'", "\'", $val)."'", $field);
		}
	
		/* replace extras {???} with '' */
		if (preg_match_all("({([^}/]*)})", $field, $m)) {
			foreach ($m[1] as $value) {
				$field = str_replace("{".$value."}", "''", $field);
			}
		}
	
		$func = create_function('', 'return '.$field.';');
	
		return $func();
	}
}