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

/**
* Recursively copies from one directory to another
*
* @access	public
* @param 	string
* @param 	string
* @return	array
*/
if (!function_exists('copyr')) {
	function copyr($source, $dest) {
		// Simple copy for a file
		if (is_file($source)) {
			return copy($source, $dest);
		}

		// Make destination directory
		if (!is_dir($dest)) {
			mkdir($dest);
		}

		// If the source is a symlink
		if (is_link($source)) {
			$link_dest = readlink($source);

			return symlink($link_dest, $dest);
		}

		// Loop through the folder
		$dir = dir($source);
		while (FALSE !== $entry = $dir->read()) {
			// Skip pointers
			if ($entry == '.' or $entry == '..') {
				continue;
			}

			// Deep copy directories
			if ($dest !== "$source/$entry") {
				copyr("$source/$entry", "$dest/$entry");
			}
		}

		// Clean up
		$dir->close();

		return TRUE;
	}
}

/**

* Removes a directory and all its content if present.
* If $dir is a file instead of a directory it gets deleted.
*
* @access		public
* @param		string $dir
* @return		boolean
*
* @author 	Damiano Venturin
* @since		Jun 22, 2012
*/
if (!function_exists('rmdirr')) {
	function rmdirr($dir) {
		//checks
		if (!is_string($dir) || empty($dir)) {
			return $false;
		}
		if (!is_dir($dir) || is_link($dir)) {
			return unlink($dir);
		}

		foreach (scandir($dir) as $file) {
			if ($file == '.' || $file == '..') {
				continue;
			}

			if (!rmdirr($dir.'/'.$file)) {
				chmod($dir.'/'.$file, 0777);

				if (!rmdirr($dir.'/'.$file)) {
					return false;
				}
			};
		}

		return rmdir($dir);
	}
}

/**
* Recursively changes the permissions of a folder structure
*
*	from php.net/chmod
* @access	public
* @param 	string
* @param 	octal
* @return	boolean
*/
if (!function_exists('chmodr')) {
	function chmodr($path, $filemode) {
		if (!is_dir($path)) {
			return chmod($path, $filemode);
		}

		$dh = opendir($path);

		while (($file = readdir($dh)) !== FALSE) {
			if ($file != '.' and $file != '..') {
				$fullpath = $path.'/'.$file;
				if (is_link($fullpath)) {
					return FALSE;
				} elseif (!is_dir($fullpath) and !chmod($fullpath, $filemode)) {
					return FALSE;
				} elseif (!chmodr($fullpath, $filemode)) {
					return FALSE;
				}
			}
		}

		closedir($dh);

		if (chmod($path, $filemode)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}

/**
* Returns an array of file names from a directory
*
* @access	public
* @param 	string
* @param 	boolean
* @param 	mixed
* @param 	boolean
* @return	array
*/
if (!function_exists('directory_to_array')) {
	function directory_to_array($directory, $recursive = true, $exclude = [], $append_path = true, $no_ext = false, $_first_time = true) {
		static $orig_directory;
		if ($_first_time) {
			$orig_directory = $directory;
		}
		$array_items = [];
		if ($handle = @opendir($directory)) {
			while (FALSE !== ($file = readdir($handle))) {
				if (strncmp($file, '.', 1) !== 0    and
					(empty($exclude) or (is_array($exclude) and !in_array($file, $exclude)) or (is_string($exclude) and !preg_match($exclude, $file)))
					) {
					if (is_dir($directory."/".$file)) {
						if ($recursive) {
							$array_items = array_merge($array_items, directory_to_array($directory."/".$file, $recursive, $exclude, $append_path, $no_ext, false));
						}
					} else {
						if ($no_ext) {
							$period_pos = strrpos($file, '.');
							if ($period_pos) {
								$file = substr($file, 0, $period_pos);
							}
						}
						$file_prefix = (!$append_path) ? substr($directory, strlen($orig_directory)) : $directory;
						$file        = $file_prefix."/".$file;
						$file        = str_replace("//", "/", $file); // replace double slash
						if (substr($file, 0, 1) == '/') {
							$file = substr($file, 1);
						} // remove begining slash
						if (!empty($file) and !in_array($file, $array_items)) {
							$array_items[] = $file;
						}
					}
				}
			}
			closedir($handle);
		}

		return $array_items;
	}
}

/**
* Lists the directories only from a give directory
*
* @access	public
* @param 	string
* @param 	mixed
* @param 	boolean
* @param 	boolean
* @param 	boolean
* @return	array
*/
if (!function_exists('list_directories')) {
	function list_directories($directory, $exclude = [], $full_path = true, $is_writable = false, $_first_time = true) {
		static $orig_directory;
		static $dirs;
		if ($_first_time) {
			$orig_directory = $directory;
		}

		if ($handle = opendir($directory)) {
			while (FALSE !== ($file = readdir($handle))) {
				if (strncmp($file, '.', 1) !== 0    and
					((is_array($exclude) and !in_array($file, $exclude)) or (is_string($exclude) and !empty($exclude) and !preg_match($exclude, $file)))
					) {
					$file_path = $directory."/".$file;
					if (is_dir($file_path)) {
						if ($is_writable and !is_writable($file_path)) {
							continue;
						}
						$dir_prefix = (!$full_path) ? substr($directory, strlen($orig_directory)) : $directory;
						$dir        = $dir_prefix."/".$file;
						$dir        = str_replace("//", "/", $dir); // replace double slash
						if (substr($dir, 0, 1) == '/') {
							$dir = substr($dir, 1);
						} // remove begining slash
						if (!isset($dirs)) {
							$dirs = [];
						}
						if (!in_array($dir, $dirs)) {
							$dirs[] = $dir;
						}
						list_directories($file_path, $exclude, $full_path, $is_writable, false);
					}
				}
			}
			closedir($handle);
		}

		return $dirs;
	}
}

/* End of file MY_directory_helper.php */
/* Location: ./application/helpers/MY_directory_helper.php */