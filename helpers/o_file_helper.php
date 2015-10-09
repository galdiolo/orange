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

/*
Convert 23gb to bytes for example 
The opposite to this is in the numbers helper
http://www.codeigniter.com/user_guide/helpers/number_helper.html#byte_format
*/
if (!function_exists('format_size_to_bytes')) {
	function format_size_to_bytes($format) {
		$units = array('B'=>0, 'K'=>1, 'KB'=>1, 'M'=>2, 'MB'=>2, 'GB'=>3, 'G'=>3, 'TB'=>4, 'T'=>4);

		$number = strtoupper(trim(preg_replace("/[^0-9\.]/", '',$format)));
		$letter = strtoupper(trim(substr($format,strlen($number))));

		return $number * pow(1024, $units[$letter]);
	}
}

/**
* Deletes files in a directory older then a certain date with the added option to exclude certain files
*
* @access	public
* @param 	string directory ie. ROOTPATH.'/var/upload_temp'
* @param 	string older then str to time ie. -1 hour
* @param 	mixed exclude
* @return	void
*/
if (!function_exists('delete_old_files')) {
	function delete_old_files($dir, $older_than, $exclude = []) {
		$files = get_dir_file_info($dir);

		if (!is_numeric($older_than)) {
			$older_than = strtotime($older_than);
		}

		if (!empty($files)) {
			foreach ($files as $file) {
				if ($file['date'] < $older_than and
					(is_null($exclude) || (is_array($exclude) && !in_array($file['name'], $exclude)) || (is_string($exclude) && !preg_match($exclude, $file['name'])))) {
					@unlink($file['server_path']);
				}
			}
		}
	}
}

/* target = file target / link = name */
if (!function_exists('relative_symlink')) {
	function relative_symlink($target,$link) {
		@unlink($link);
		
		exec('cd "'.dirname($link).'"; ln -s "'.getRelativePath($link,$target).'" "'.basename($link).'"');
	
		return (linkinfo($link) > 0);
	}
}

if (!function_exists('getRelativePath')) {
	function getRelativePath($from, $to) {
		// some compatibility fixes for Windows paths
		$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
		$to = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
		$from = str_replace('\\', '/', $from);
		$to = str_replace('\\', '/', $to);
		
		$from = explode('/', $from);
		$to = explode('/', $to);
		$relPath  = $to;
		
		foreach($from as $depth => $dir) {
			// find first non-matching dir
			if($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
			
				if($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}
	
		return implode('/', $relPath);
	}
}

/* remember to also check directory_helper */