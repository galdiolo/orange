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

class MY_Output extends CI_Output {
	/**
	* Output Json Responds
	* New Function
	*
	* @param		mixed		array of key/values pairs or key or already formatted json
	* @param		mixed		if the first value is not a array this would be the value of the key value pair
	* @return $this allow chaining
	*/
	public function json($data = [], $val = null) {
		log_message('debug', 'my_output::json');

		$data = ($val !== NULL) ? [$data => $val] : $data;
		$json = (is_array($data)) ? json_encode($data) : $data;

		$this
			->nocache()
			->set_content_type('application/json', 'utf=8')
			->set_output($json);

		/* allow chaining */
		return $this;
	}

	/**
	* Send No Cache Headers
	* New Function
	*
	* @return $this allow chaining
	*/
	public function nocache() {
		log_message('debug', 'my_output::nocache');

		$this->set_header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		$this->set_header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
		$this->set_header('Cache-Control: post-check=0, pre-check=0', false);
		$this->set_header('Pragma: no-cache');

		/* allow chaining */
		return $this;
	}
} /* end MY_Output */