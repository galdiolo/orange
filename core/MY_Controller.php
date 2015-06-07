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
*
* Extends load order
*
* APP_PublicController -> O_PublicController -> APP_GuiController -> O_GuiController -> MY_Controller -> CI_Controller
* APP_AdminController -> O_AdminController -> APP_GuiController -> O_GuiController -> MY_Controller -> CI_Controller
*
*/
class MY_Controller extends CI_Controller {
	public $theme_folder;
	public $body_class;

	/* the childern controllers can set these to have additional objects autoloaded */
	public $libraries = [];
	public $helpers = [];
	public $models = [];
	public $plugins = [];

	/* to store the data sent to the view */
	public $data = [];
	public $controller_model;
	public $onload_visibility = 'public';

	public function __construct() {
		/* setup our base controller */
		parent::__construct();

		/* auto load a bunch of stuff - If it's filled in */

		/* load the other controller libraries, model, helpers */
		$this->load->library($this->libraries);
		$this->load->model($this->models);
		$this->load->helpers($this->helpers);
		
		/* let the modules do there start up thing */
		include APPPATH.'/config/modules.php';

		foreach ($autoload[$this->onload_visibility.'_onload'] as $module_onload_file) {
			/* try to load it but, don't complain if it's not there */
			include $module_onload_file.'/support/onload.php';
		}
		
		/* while you could have done this in your onload file - this keeps it "clean" */
		$this->event->trigger('ci.controller.startup',$this);

		/*
		cache driver is loaded in MY_Loader::setting 
		since it is needed so early on
		*/

		/* is the site open? */
		if (setting('application','Site Open') != 1 && !$_COOKIE['ISOPEN']) {
			$this->output->set_status_header(503, 'Site Down for Maintence');

			/* if it's not ajax request sent a nice page */
			if (!$this->input->is_ajax_request()) {
				echo $this->load->partial('main/site_down');
			}

			/* if it is a ajax request the 503 error should be handled by the ajax requesting javascript */
			exit(0);
		}

		/* setup a default model if one is specified */
		if ($this->controller_model) {
			$this->load->model($this->controller_model);
		}

	}
} /* end controller */