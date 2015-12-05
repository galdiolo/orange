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

Ajax Handler

load the library
$this->load->library('widget');

grab the command from the posted input
$command = $this->input->post('command');

send it into the widget request method
$html = $this->widget->request($command);

fill codeigniters output and allow it to be sent
$this->output->set_output($html);

Of course you can put that all in 1 line!

public function widget_handlerPostAction() {
	$this->load->library('widget');

	$this->output->set_output($this->widget->request($this->input->post('command')));
}


Render in PHP before sent to the browser
<?=Widget::show('blog/posts:entry limit="5" sort="publish_on" dir="desc"') ?>

Added to the HTML to have the html loaded dynamically
<command widget="blog/posts:entry" sort="publish_on" dir="desc" wkey="<?=$widget_token ?>">

Added to the HTML to have the above html created dynamically

<?=Widget::command('blog/posts:entry',['limit'=>5,'sort'=>'publish_on','dir'=>'desc']) ?>

*/
class Widget {
	/* per page request token */
	static public $token_set = false;

	/* where is the Ajax Handler - attached to main controller by default */
	public $handler_url = '/main/widget_handler';

	public function __construct() {
		/* if it's not a ajax request (ie browser request) refresh the token */
		if (!ci()->input->is_ajax_request()) {
			/* create a new page request if not ajax (ie in page requests) */
			self::$token_set = sha1(uniqid());

			/* save it for each ajax request */
			ci()->session->set_userdata('widget_token',self::$token_set);
		} else {
			self::$token_set = ci()->session->userdata('widget_token');
		}

		/*
		add it to a page variable incase we want to add it manually
		add the dynamic ajax requester
		*/
		ci()->page
			->data('widget_token',self::$token_set)
			->script('$(function(){$(\'command\').each(function(){var t=this;$.post(\''.$this->handler_url.'\',{"command":$(this).wrap(\'<span>\').parent().html()},function(responds){$(t).parent().html(responds);});});});');
	}


	public function request($command) {
		$command = str_replace('</command>','',$command);
		$command = str_replace('command widget="','',$command);

		$first_quote = strpos($command,'"');
		$command = substr($command,0,$first_quote).substr($command,$first_quote+1);

		$reply = 'Token Error';

		/* grab the token from the ajax request */
		$bol = preg_match("/wkey=\"([0-9a-fA-F]*)\"/", $command, $matches);
		
		/* did it return 1 entry? */
		if ($bol === 1) {
			/* does the token match the session token */
			if ($matches[1] === self::$token_set) {
				/* let's take the token out */
				$command = str_replace($matches[0],'',$command);
				
				/* run the widget */
				$reply = self::show($command);
			}
		}

		return $reply;
	}

	/**
   * Runs a callback method and returns the contents to the view, allowing
   * you to create re-usable, cacheable "widgets" for your views.
   *
   * Example:
   *     Widgets::show('blog/posts:list limit="5" sort="publish_on" dir="desc"');
   *
   * blog/posts is the library (in folder blog in this example)
   * list is the method
   * parameters
   *
   * optional parameter cache="3600" to set the cache length
   *
   * @param string $command
   * @return mixed|void
   */
	public static function show($command) {
		/*
		Users should be allowed to customize the cache name
		so they can account for user role, logged in status,
		or simply be able to easily clear the cache items elsewhere.
		*/

		$cache_name = 'widget_'.md5($command);

		if (!$output = ci()->cache->get($cache_name)) {
			/* remove < > ? */
			$command = trim($command,'<>');

			/* find that first space */
			$first_space = strpos($command,' ');

			/* split off the library and method */
			list($class, $method) = explode(':',substr($command,0,$first_space));

			/* add widget to the begining of the class name */
			$classname = 'Widget_'.basename($class);

			/* use the simple xml parser to convert "attrubutes" to values */
			try {
				$params = new SimpleXMLElement('<element '.substr($command,$first_space + 1).' />');
			} catch (Exception $e) {
				return 'Error parsing parameters for '.trim(dirname($class).'/'.$classname,'/').'::'.$method;
			}

			$params = (array)$params;

			/*
			Let PHP try to autoload it through any available autoloaders
			(including Composer and user's custom autoloaders). If we
			don't find it, then assume it's a CI library that we can reach.
			*/
			if (class_exists($classname)) {
				$obj = new $classname();
			} else {
				$classfile = trim(dirname($class).'/'.$classname,'/');

				ci()->load->library($classfile);

				$classname = strtolower($classname);

				$obj =& ci()->$classname;
			}

			if (!method_exists($obj, $method)) {
				return 'can\'t find '.$class.':'.$method;
			}

			/* Call the class with our parameters */
			$output = $obj->{$method}($params['@attributes']);

			/* cache length - use parameter cache="0" for no cache */
			$cache_ttl = (isset($params['@attributes']['cache'])) ? (int)$params['@attributes']['cache'] : setting('config','cache_ttl');

			/* cache it */
			if ($cache_ttl > 0) {
				ci()->cache->save($cache_name, $output, $cache_ttl);
			}
		}

		return $output;
	}

	/* <command widget="blog/posts:no_cache_entry" limit="5" sort="publish_on" dir="desc"> */
	public static function command($call,$options=[]) {
		$options_text = '';

		foreach ($options as $k=>$v) {
			$options_text .= $k.'="'.$v.'" ';
		}

		return '<command widget="'.$call.'" '.$options_text.'wkey="'.self::$token_set.'">';
	}

	/* merge incoming data with the defaults - only allow key in the default - strip the rest */
	public static function merge($data=[],$defaults=[]) {
		return array_diff_key($defaults,$data) + array_intersect_key($data,$defaults);
	}
	
} /* end class */