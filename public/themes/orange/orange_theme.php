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
function orange_theme_setup(&$page,$path) {
	ci()->load->library(['theme','bootstrap_menu']);
	
	/* https://cdnjs.com/ */
	$page
		->title('Orange Framework')
		->js([
				'//cdnjs.cloudflare.com/ajax/libs/jquery/1.11.2/jquery.min.js',
				'//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.4/js/bootstrap.min.js',
				'//cdnjs.cloudflare.com/ajax/libs/jStorage/0.4.12/jstorage.min.js',
				$path.'/assets/js/orange.min.js',
		])
		->css([
			'//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.4/css/bootstrap.min.css',
			'//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css',
			$path.'/assets/css/orange.min.css',
		])
		->plugin(['flash_msg','select3','o_dialog','o_validate_form']);
}