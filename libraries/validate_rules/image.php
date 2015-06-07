<?php
trait validate_image {
	public function max_width($file, $width = 0) {
		$this->set_message('max_width', 'Width is greater than %s.');

		if (!file_exists($file)) {
			$this->set_message('max_width', 'File Not Found.');

			return FALSE;
		}

		$size = $this->get_image_dimension($file);

		return (bool) ($size[0] <= $width);
	}

	public function max_height($file, $height = 0) {
		$this->set_message('max_height', 'Height is greater than %s.');

		if (!file_exists($file)) {
			$this->set_message('max_height', 'File Not Found.');

			return FALSE;
		}

		$size = $this->get_image_dimension($file);

		return (bool) ($size[1] <= $height);
	}

	public function min_width($file, $width = 0) {
		$this->set_message('min_width', 'Width is less than %s.');

		if (!file_exists($file)) {
			$this->set_message('min_width', 'File Not Found.');

			return FALSE;
		}

		$size = $this->get_image_dimension($file);

		return (bool) ($size[0] <= $width);
	}

	public function min_height($file, $height = 0) {
		$this->set_message('min_height', 'Height is less than %s.');

		if (!file_exists($file)) {
			$this->set_message('min_height', 'File Not Found.');

			return FALSE;
		}

		$size = $this->get_image_dimension($file);

		return (bool) ($size[1] <= $width);
	}

	public function exact_width($file, $width = 0) {
		$this->set_message('exact_width', 'Width must be %s.');

		if (!file_exists($file)) {
			$this->set_message('exact_width', 'File Not Found.');

			return FALSE;
		}

		$size = $this->get_image_dimension($file);

		return (bool) ($size[0] == $width);
	}

	public function exact_height($file, $height = 0) {
		$this->set_message('exact_height', 'Height must be %s.');

		if (!file_exists($file)) {
			$this->set_message('exact_height', 'File Not Found.');

			return FALSE;
		}

		$size = $this->get_image_dimension($file);

		return (bool) ($size[1] == $height);
	}

	public function max_dim($file, $dim = '') {
		$dim = explode(',', $dim);

		$this->set_message('max_dim', 'The width & height cannot be greater than '.$dim[0].'x'.$dim[1]);

		if (!file_exists($file)) {
			$this->set_message('max_dim', 'File Not Found.');

			return FALSE;
		}

		$size = $this->get_image_dimension($file);

		return (bool) ($size[0] < $dim[0] && $size[1] < $dim[1]);
	}

	public function min_dim($file, $dim = '') {
		$dim = explode(',', $dim);

		$this->set_message('min_dim', 'The width & height cannot be less than '.$dim[0].'x'.$dim[1]);

		if (!file_exists($file)) {
			$this->set_message('min_dim', 'File Not Found.');

			return FALSE;
		}

		$size = $this->get_image_dimension($file);

		return (bool) ($size[0] > $dim[0] && $size[1] > $dim[1]);
	}

	public function exact_dim($file, $dim = '') {
		$dim = explode(',', $dim);

		$this->set_message('exact_dim', 'The width & height must be '.$dim[0].'x'.$dim[1]);

		if (!file_exists($file)) {
			$this->set_message('exact_dim', 'File Not Found.');

			return FALSE;
		}

		$size = $this->get_image_dimension($file);

		return (bool) ($size[0] == $dim[0] && $size[1] == $dim[1]);
	}

	/**
	* Internal Functions
	*/
	public function get_image_dimension($file_name) {
		if (function_exists('getimagesize')) {
			$d    = @getimagesize($file_name);

			return $d;
		}

		show_error('Get Image Size Function Not Supported');
	}
} /* end trait */