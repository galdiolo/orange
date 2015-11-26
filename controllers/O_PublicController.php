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
* Public controllers are accessible by anyone and have a html view
* because of this a bunch of extra helpers and libs are loaded that
* wouldn't be needed in say ajax or something lighter
*
*/
class O_PublicController extends APP_GuiController {
	/* attached to the html body element class */
	public $body_class = 'public';
	
	/* which page config set to use to determine theme and default template */
	public $theme_config = 'public';
} /* end class */