<?php
trait validate_file {
	public function file_size_max($file, $bytes = 0) {
		$this->set_message('file_size_max', 'File %s size is greater than '.$bytes.' bytes');

		if (!file_exists($file)) {
			$this->set_message('file_size_max', 'File Not Found.');

			return FALSE;
		}

		$size = filesize($file);

		return (bool) ($size > $bytes);
	}

	public function file_size_min($file, $bytes = 0) {
		$this->set_message('file_size_min', 'File %s size is less than '.$bytes.' bytes');

		if (!file_exists($file)) {
			$this->set_message('file_size_min', 'File Not Found.');

			return FALSE;
		}

		$size = filesize($file);

		return (bool) ($size > $bytes);
	}

	public function is_image_file($file) {
		$this->set_message('is_image_file', 'The %s is not a valid file.');

		if (!file_exists($file)) {
			$this->set_message('is_image_file', 'File Not Found.');

			return FALSE;
		}

		return (bool) (preg_match("/(.)+\\.(jp(e) {0,1}g$|gif$|png$)/i", $path));
	}

	public function is_file($field, $options = null) {
		$this->set_message('is_file', 'The %s is not a valid file.');

		return (bool) is_file($field);
	}

	public function is_dir($field, $options = null) {
		$this->set_message('is_dir', 'The %s is not a valid directory.');

		return (bool) is_dir($field);
	}

	public function filename($field, $options = null) {
		$this->set_message('filename', 'The %s is not a valid file name.');

		return (bool) preg_match("/^[0-9a-zA-Z_\-. ]+$/i", $field);
	}

	public function foldername($field, $options = null) {
		$this->set_message('foldername', 'The %s is not a valid folder name.');

		return (bool) preg_match("/^([a-zA-Z0-9_\- ])+$/i", $field);
	}

	public function readable($field, $options = null) {
		$this->set_message('readable', 'The %s is not a readable.');

		return (is_string($field) && is_readable($field));
	}

	public function writable($field, $options = null) {
		$this->set_message('writable', 'The %s is not a writable.');

		return (is_string($field) && is_writable($field));
	}

	public function allowed_types($file, $options = null) {
		// allowed_type[png,gif,jpg,jpeg]
		$types = ($options) ? $options : '';

		$this->set_message('allowed_types', '%s must contain one of the allowed file extensions.');

		$type = (array) explode(',', $types);

		$filetype = pathinfo($file, PATHINFO_EXTENSION);

		return (in_array($filetype, $type, true));
	}

	public function symbolic_link($file, $options = null) {
		$this->set_message('symbolic_link', 'The %s is not a symbolic link.');

		return (is_string($file) && is_link($file));
	}
} /* end class */