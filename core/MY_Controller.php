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
	public $catalogs = [];

	/* to store the data sent to the view */
	public $data = [];
	public $controller_model;

	/* setup our base controller */
	public function __construct() {
		parent::__construct();

		/* auto load a bunch of stuff - If it's filled in */

		/* load the other controller libraries, model, helpers */
		$this->load->library($this->libraries);
		$this->load->model($this->models);
		$this->load->helpers($this->helpers);

		/* load model catalogs into view */
		foreach ((array)$this->catalogs as $c) {
			$c = str_replace('_model','',$c);
			$this->load->model($c.'_model');

			if (method_exists($this->{$c.'_model'},'catalog')) {
				$this->load->vars($c.'_catalog',$this->{$c.'_model'}->catalog());
			}
		}
				
		/*
		does the cached onload exist? 
		if this is a multi box system it might not
		*/
		if (!file_exists($this->load->onload_path)) {
			/* no create it */
			ci()->load->create_onload($this->load->onload_path);
		}
		
		/* load the "cached" onload */
		include $this->load->onload_path;

		/* while you could have done this in your onload file - this keeps it "clean" */
		$this->event->trigger('ci.controller.startup',$this);

		/* cache driver is loaded in MY_Loader::setting since it is needed so early on */

		/* is the site open? */
		$key = setting('application','Is Open Cookie');
		$key = (!empty($key)) ? $key : md5(uniqid(true));

		if (php_sapi_name() !== 'cli') {
			if (setting('application','Site Open') !== true && $_COOKIE['ISOPEN'] !== $key) {
				$this->output->set_status_header(503, 'Site Down for Maintence');
	
				/* if it's not ajax request sent a nice page */
				if (!$this->input->is_ajax_request()) {
					echo $this->load->partial('main/site_down');
				}
	
				/* if it is a ajax request the 503 error should be handled by the ajax requesting javascript */
				exit;
			}
		}

		/* setup a default model if one is specified */
		if ($this->controller_model) {
			$this->load->model($this->controller_model);
		}

	} /* end __construct */

} /* end controller */