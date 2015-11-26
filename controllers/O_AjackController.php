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
Extends load order

O_AjackController -> MY_Controller -> CI_Controller

security validation can be done with $this->auth->has_access()
or simply has_access() (global function wrapper)

For example:
has_access('*'); = everyone
has_access('@'); = everyone active (logged and is_active)
has_access('foo bar'); = everyone with the access of "foo bar"

optionally you can include the access as a array as the second variable 
if for some reason you are testing someone other then the logged in user.

*/
class O_AjackController extends MY_Controller {
	public function __construct() {
		parent::__construct();

		/* Ajax only requests - show a error */
		if (!$this->input->is_ajax_request()) {
			show_404();
		}		
	}
} /* end class */