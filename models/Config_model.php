<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Orange Framework Extension
*
* This content is released under the MIT License (MIT)
*
* @package	CodeIgniter / Orange
* @author	Don Myers
* @license	http://opensource.org/licenses/MIT	MIT License
* @link	https://github.com/dmyers2004
*
* Much of this code copied from ionizecms
* @license http://doc.ionizecms.com/en/basic-infos/license-agreement
*
*/
class Config_model {
	/**
	* Content of the config file to alter
	* @var null
	*/
	protected $content = NULL;

	/**
	* Complete path to the config folder
	* @var null|string
	*/
	protected $path = NULL;

	/**
	* Name of the config file
	* @var null
	*/
	protected $config_file = NULL;

	/**
	* Constructor
	*
	* @param	string  Path to the config file folder
	*
	*/
	public function __construct() {
		if (is_dir(realpath(APPPATH.'config'))) {
			$this->path = realpath(APPPATH.'config').'/';
		}
	}

	/**
	* Opens one config file
	*
	* @param	string			config file name
	* @param 	null|string		Module name
	*/
	public function open_file($config_file) {
		$config_file = basename($config_file,'.php').'.php';

		// Gets the content of the asked file
		if (realpath($this->path.$config_file) !== false) {
			$this->content = file_get_contents($this->path.$config_file);

			$this->config_file = $config_file;

			return true;
		}

		return false;
	}

	/**
	* Sets a config value
	*
	* @param      $key
	* @param      $val
	* @param null $module_key
	*
	* @return bool
	*/
	public function set_config($key, $val, $variable = 'config') {
		if ( ! is_null($this->content)) {
				$pattern = '%(?sx)
					(
						\$'.$variable."
						\[(['\"])
						(".$key.")
						\\2\]
						\s*=\s*
					)
					(.+?);
				%";

			$type = gettype($val);

			if ($type == 'string') {
				if (strtolower($val) == 'true') {
					$val = var_export(true, true);
				} else if (strtolower($val) == 'false') {
					$val = var_export(false, true);
				} else {
					$val = "'".$val."'";
				}

				if ((strtolower($val) == 'true' OR strtolower($val) == 'false') && ! is_null($module_key)) {
					$val .= ',';
				}
			}

			if ($type == 'boolean') {
				$val = ($val ? var_export(true, true) : var_export(false, true) );

				if ( ! is_null($module_key)) {
					$val .= ',';
				}
			}

			if ($type == 'array') {
				$val = preg_replace("/[0-9]+ \=\>/i", '', var_export($val, true));
				$val = str_replace("\n", "\r\n", $val);
			}

			/*
			print_r($pattern);
			preg_match($pattern, $this->content, $matches);
			print_r($matches);			
			*/

			if ( is_null($module_key)) {
				$this->content = preg_replace($pattern, "\\1$val;", $this->content);
			} else {
				$this->content = preg_replace($pattern, "\\1$val", $this->content);
			}

			return true;
		}

		return false;
	}

	/**
	* Change a config value
	*
	* @param	String	The config file name
	* @param	String	key to change
	* @param	Mixed	value to set to the key
	* @param	String	Module name, in case of a module config file
	*
	* @return bool|int
	*/
	public function change($config_file, $key, $val, $variable = 'config') {
		if ($this->open_file($config_file)) {
			if ($this->set_config($key, $val, $variable)) {
				return $this->save();
			}
		}

		return false;
	}

	/**
	* Saves the config file
	*
	* @return bool
	*
	*/
	public function save() {
		return (!is_null($this->content)) ? (bool)file_put_contents($this->path.$this->config_file, $this->content) : false;
	}
} /* end class */