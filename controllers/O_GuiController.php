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
		//$this->output->cache(60);
		
		/* Doing GUI so, load the Page Library and Orange Library (static methods used mostly for views) */
		$this->load->library(['Page','O']);

		/* Is a theme set on the controller? Try settings or just default to nothing */
		$theme = ($this->theme_folder !== null) ? $this->theme_folder : setting('application','theme',null);

		/* Set our theme folder (package) */
		$this->page->theme($theme);
		
		/* Is the body class set on the controller? */
		$body_class = ($this->body_class !== null) ? $this->body_class : $theme;

		/* Set our body class for this type of controller */
		$this->page->body_class($body_class);
	}

} /* end class */