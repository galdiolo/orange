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
	/* https://cdnjs.com/ */
	$page
		->library(['theme','bootstrap_menu','Plugin_flash_msg','Plugin_select3','Plugin_o_dialog','Plugin_o_validate_form'])
		->title('Orange Framework')
		->js([
				'//cdnjs.cloudflare.com/ajax/libs/jquery/1.11.3/jquery.min.js',
				'//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.5/js/bootstrap.min.js',
				'//cdnjs.cloudflare.com/ajax/libs/jStorage/0.4.12/jstorage.min.js',
				$path.'/assets/js/orange.min.js',
		])
		->css([
			'//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.5/css/bootstrap.min.css',
			'//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.4.0/css/font-awesome.min.css',
			$path.'/assets/css/orange.min.css',
		]);
}