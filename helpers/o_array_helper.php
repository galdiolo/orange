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

/*
serach an array for a value in a property
return the found object or FALSE if not found
*/
if (!function_exists('array_search_object')) {
	function array_search_object($key, $value, $objects) {
		foreach ($objects as $object) {
			if ($object->$key == $value) {
				return $object;
			}
		}

		return FALSE;
	}
}

/**
* Create a associated array out of a larger array or array of objects
*/
if (!function_exists('array2list')) {
	function array2list($array, $key = 'id', $value = 'name', $orderby = '', $dir = 'a') {
		$list = [];

		foreach ($array as $row) {
			$row = (array)$row;

			if ($value == '*') {
				$list[$row[$key]] = $row;
			} else {
				$list[$row[$key]] = $row[$value];
			}
		}

		switch ($orderby) {
			case 'key':
				if ($dir == 'a') {
					ksort($list);
				} else {
					krsort($list);
				}
			break;
			case 'value':
				if ($dir == 'a') {
					asort($list);
				} else {
					arsort($list);
				}
			break;
		}

		return $list;
	}
}