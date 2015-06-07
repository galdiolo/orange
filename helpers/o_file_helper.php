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

if (!function_exists('format_size_units')) {
	function format_size_units($bytes) {
		if ($bytes >= 1073741824) {
			$bytes = number_format($bytes / 1073741824, 2).' GB';
		} elseif ($bytes >= 1048576) {
			$bytes = number_format($bytes / 1048576, 2).' MB';
		} elseif ($bytes >= 1024) {
			$bytes = number_format($bytes / 1024, 2).' KB';
		} elseif ($bytes > 1) {
			$bytes = $bytes.' bytes';
		} elseif ($bytes == 1) {
			$bytes = $bytes.' byte';
		} else {
			$bytes = '0 bytes';
		}

		return $bytes;
	}
}

/**
* Deletes files in a directory older then a certain date with the added option to exclude certain files
*
* @access	public
* @param 	string directory
* @param 	string older then str to time
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