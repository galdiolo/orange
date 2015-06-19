<?php

class install_orange extends module_install {
	public $name = 'Orange';
	public $version = '2.0.0';
	public $info = 'The Orange Framework';
	public $type = 'core';
	public $upgrade = true;
	public $autoload = true;
	public $requires = [
		'plugin_combobox'=>'1.*.*',
		'plugin_flash_msg'=>'1.*.*',
		'plugin_nestable'=>'1.*.*',
		'plugin_o_dialog'=>'1.*.*',
		'plugin_o_validate_form'=>'1.*.*',
		'plugin_select3'=>'1.*.*',
	];
	public $requires_composer = [
		"codeigniter/framework"=>"*",
		"oodle/krumo"=>"*",
		"composer/composer"=>"1.0.*",
	];
	public $theme = 'Orange - included with this module';
	public $table = 'orange_access, orange_nav, orange_role_access, orange_roles, orange_settings, orange_users';
} /* end class */