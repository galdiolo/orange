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

class MY_Input extends CI_Input {
	public function is_valid($rules = '',$input = null,$human_die=true) {
		$human = 'field';
		$die = true;

		if (is_bool($human_die)) {
			$die = $human_die;
		} elseif(is_string($human_die)) {
			$human = $human_die;

			/* don't die because they sent in a Human readable field name */
			$die = false;
		}
		/* post variable? */
		if ($input{0} == '@') {
			$input = $this->_fetch_from_array($_POST,substr($input,1));
		}

		/* either get a reply or die here */
		$reply = ci()->validate->die_on_fail($die)->single($rules,$input,$human);

		/*
		if we havn't died yet then what should we return?

		if die is true then we are just testing multiple inputs so allow chaining
		if die is false then they sent in a human field name so return the reply (false)
		this will also populate the validate error string (which is why the human is needed)
		*/
		return ($die) ? $this : $reply;
	}

	/**
	* Fetch an item from the PUT array
	* New Function
	*
	* @param	string	$index			Index for item to be fetched from the input stream
	* @param	bool		$xss_clean	Whether to apply XSS filtering
	* @return	mixed
	*/
	public function put($index = null, $xss_clean = false) {
		log_message('debug', 'my_input::put');

		return $this->input_stream($index, $xss_clean);
	}

	/**
	* Map a array either server post
	* or sent in post to another array
	* with option to rename
	* the array key in the new array
	* New Function
	*
	* @param mixed $fields array of fields to match or comma seperated list of field to match
	* @param byref data variable to place the new array
	* @post array optional to used as the starting array
	*
	* the input fields may contain the "as" format (like sql)
	* to use a different key name
	*
	* $fields = 'vara,varb,varc as vard';
	* $data = [];
	* $post = ['vara'=>'foo','varb'=>'bar','varc'=>'foobar','vard'='barfoo'];
	*
	* map($fields,$data,$post);
	*
	* data contains
	* $data = ['vara'=>'foo','varb'=>'bar','vard'=>'foobar'];
	*
	*/
	public function map($fields, &$data, $post = null) {
		log_message('debug', 'my_input::map');

		if (!is_array($fields)) {
			$fields = explode(',', $fields);
		}

		foreach ($fields as $field) {
			$post_field = $from_field = $field;

			if (strpos($field, ' as ') !== FALSE) {
				list($post_field, $from_field) = explode(' as ', $field, 2);
			}

			$data[$from_field] = ($post) ? $post[$post_field] : $this->post($post_field);
		}

		return $this; /* allow chaining */
	}

} /* end MY_Input class */