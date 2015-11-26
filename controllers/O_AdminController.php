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
* AdminController is accessible by anyone who is logged in
* and extends PublicBaseController which has the abilities to autoload
* helpers, libraries, models
*
*/
class O_AdminController extends APP_GuiController {
	use t_admin, t_admin_create, t_admin_update, t_admin_read, t_admin_delete;

	public $theme_folder = '/projectorangebox/theme-orange';
	public $body_class = 'admin';

	/* your basic scaffolding */
	public $controller_path = null; /* url to this controller */
	public $controller = ''; /* controller name */
	public $controller_title = ''; /* used in various places */
	public $controller_titles = '';  /* used in various places */
	public $controller_model = null; /* allows autoloading */
	public $has_access = null; /* array, single, * everyone, @ everyone logged in, null will always fail therefore you must set has_access */

	public function __construct() {
		/* call our parent and let them setup */
		parent::__construct();

		/*
		settings model already loaded,
		package model only needed by package controller
		auth library loaded all the user models
		*/

		/* Therefore the only orange model left to load is the menubar */
		ci()->load->model('o_menubar_model');

		/* wallet is a extensions to sessions (ie user data) */
		$this->load->library('wallet');

		/* use the orange_default template */
		$this->page->template('_templates/orange_default');

		$this->page->data([
			'controller'=>$this->controller,
			'controller_path'=>$this->controller_path,
			'controller_title'=>$this->controller_title,
			'controller_titles'=>$this->controller_titles,
		]);

		/* test access */
		$this->has_access($this->has_access);
	}

} /* end controller */