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

class O_GuiController extends MY_Controller {
	public function __construct() {
		parent::__construct();
		
		/* turn on output caching? */
		//$this->output->cache(120);
		
		/* Doing GUI so, load the Page Library and Orange Library (static methods used mostly for views) */
		$this->load->library(['Page','O']);
		
		$theme = setting('page',$this->theme_config.' theme folder','');
		$template = setting('page',$this->theme_config.' theme default template','');
		
		if (empty($theme)) {
			show_error('Theme folder missing for '.$this->theme_config);
		}
		
		if (empty($template)) {
			show_error('Theme default template missing for '.$this->theme_config);		
		}

		$this->page
			/* Where is our theme folder */
			->theme($theme)
			/* what is the default theme template */
			->template($template)
			/* Set our body class for this type of controller */
			->body_class($this->body_class.' '.$this->theme_config);
	}

} /* end class */