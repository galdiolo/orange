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
class Page {
	protected $route; /* used for auto loading of views */
	protected $template; /* template to use in build method */
	protected $theme = ''; /* current theme */
	protected $assets = []; /* combined js and css storage */
	protected $javascript_variables = []; /* javascript vairables added to page */
	protected $page_body_class = ''; /* page body class */
	protected $theme_www_path; /* themes path with theme appened */
	protected $theme_path; /* absolute path to current theme */
	protected $script_attributes;
	protected $link_attributes;
	protected $encryption_key;
	protected $short_name; /* name without path and theme_ */

	/* used external libraries - mock these */
	protected $ci_load;
	protected $ci_event;
	protected $ci_user;
	protected $ci_router;

	public function __construct() {
		$this->ci_load = &ci()->load;
		$this->ci_event = &ci()->event;
		$this->ci_user = &ci()->user;
		$this->ci_router = &ci()->router;

		/* these are cached so they should be pretty fast - this also permits the use of a default */
		$this->theme_www_path = $this->ci_load->setting('paths','WWW Themes','/themes');
		$this->template = $this->ci_load->setting('page','Default Template','_templates/default');

		$this->encryption_key = $this->ci_load->setting('config','encryption_key');

		/* load any config file entries into the views variables */
		$view_variables = $this->ci_load->setting('page','View Variables',null);

		if (is_array($view_variables)) {
			$this->ci_load->vars($view_variables);
		}

		$this->script_attributes = ['src' => '','type' => 'text/javascript','charset' => 'utf-8'];
		$this->link_attributes = ['href' => '','type' => 'text/css','rel' => 'stylesheet'];

		/* setup router variables */
		$route = trim($this->ci_router->fetch_directory().$this->ci_router->fetch_class().'/'.$this->ci_router->fetch_method(), '/');

		$this->ci_load->vars(['route_raw' => $route]);

		$this->route = strtolower(str_replace(['PostAction', 'Controller', 'Action'], '', $route));

		$this->ci_load->vars(['route' => $this->route]);

		$router_class = str_replace('/', ' ', $this->route);

		$this->ci_load->vars(['route_class' => $router_class]);

		$this->body_class($router_class);
	}

	/*
	setter for template
	$this->page->template('default');
	*/
	public function template($name = null) {
		if ($name === null) {
			return $this->template;
		}

		/* set page template */
		$this->template = $name;

		/* chain-able */
		return $this;
	}

	public function theme_name($name=null) {
		/* setter / getter */
		if ($name == null) {
			/* getter */
			return $this->short_name;
		}
		
		/* setter */
		$this->short_name = $name;
		
		/* chain-able */
		return $this;
	}

	/*
	change theme
	$this->page->theme('mytheme');
	*/
	public function theme($name=null) {
		/* get theme? */
		if ($name === null) {
			return $this->theme;
		}

		$name = ltrim($name,'/');

		/* does this theme even exist? */
		if (!$theme_path = realpath(ROOTPATH.'/packages/'.$name)) {
			if (!$theme_path = realpath(ROOTPATH.'/vendor/'.$name)) {
				show_error('Cannot locate theme '.$name);
			}
		}

		$this->short_name = substr(basename($name),6);

		/* 1 theme at a time so make this our new theme */
		$this->theme = $name;

		/* add it to the body class */
		$this->body_class($this->short_name);

		/* add it as a CI package */
		$this->ci_load->theme($theme_path);

		/* save the theme www path for later access */
		$this->theme_path = $this->theme_www_path.'/'.$name;

		$this->ci_load->vars(['theme_path'=>$this->theme_path]);

		$theme_file = $theme_path.'/support/'.$this->short_name.'_theme.php';

		/* does it have a theme init file? */
		if (file_exists($theme_file)) {
			include_once $theme_file;

			$function_name = $this->short_name.'_theme_setup';

			if (function_exists($function_name)) {
				$function_name($this,$this->theme_path);
			}
		}

		/* chain-able */
		return $this;
	}

	public function theme_path() {
		return $this->theme_path;
	}

	/*
	plugin function
	$this->page->plugin('something');
	$this->page->plugin(['something','something']);
	*/
	public function library($file = null) {
		$this->ci_load->library($file);

		/* chain-able */
		return $this;
	}

	public function route($route=null) {
		if ($route === null) {
			return $this->route;
		}

		$this->route = $route;

		return $this;
	}

	public function title($name='') {
		$this->ci_load->vars(['page_title'=>$name]);

		return $this;
	}

	/* add meta tag
		$this->page->meta('http-equiv','X-UA-Compatible','IE=edge,chrome=1');
		$this->page->meta('name','viewport','width=device-width, initial-scale=1');
		$this->page->meta('name','viewport','width=device-width, initial-scale=1');
		$this->page->meta('charset','utf-8');
	*/
	public function meta($attr,$name,$content=null) {
		$content = ($content) ? ' content="'.$content.'"' : '';

		return $this->_data_core('page_meta', '<meta '.$attr.'="'.$name.'"'.$content.'>', '>');
	}

	/* prepend
	$this->page->prepend('name','something');
	$this->page->prepend(['name'=>'something','name2'=>'something else']);
	*/
	public function prepend($key, $value = '$uNdEfInEd$') {
		return $this->data($key,$value,'<');
	}

	/* getter / setter */
	public function data($name = null, $value = '$uNdEfInEd$', $where = '#', $multi_value = false) {
		/* if name is null return all */
		if ($name === null) {
			return $this->ci_load->get_vars();
		}

		/* if $name is a array then it a array of name/value pairs */
		if (is_array($name)) {
			foreach ($name as $k=>$v) {
				$this->_data_core($k, $v, $where);
			}

			return $this;
		}

		/* if the values is a array then use the same $name */
		if (is_array($value) && $multi_value) {
			foreach ($value as $v) {
				$this->_data_core($name, $v, $where);
			}

			return $this;
		}

		/* if value is $uNdEfInEd$ then they didn't send in a value so return a value */
		if ($value === '$uNdEfInEd$') {
			return $this->ci_load->get_var($name);
		}

		/* default */
		return $this->_data_core($name, $value, $where);
	}

	/*
	append
	$this->page->append('name','something');
	$this->page->append(['name'=>'something','name2'=>'something else']);
	*/
	public function append($key, $value = '$uNdEfInEd$') {
		return $this->data($key,$value,'>');
	}

	/*
	add a css file
	$this->page->css('/assets/style.css');
	$this->page->css('/assets/style.css','<');
	$this->page->css(['/assets/style.css','/assets/style2.css'],'<');
	$this->page->css('http://www.example.com/style.css');
	$link = $this->page->css('http://www.example.com/style.css',true);
	*/
	public function css($file = '', $where = '>') {
		/* handle it if it's a array */
		if (is_array($file)) {
			foreach ($file as $f) {
				$this->css($f,$where);
			}

			return $this;
		}

		/* search the theme first then exact location */
		$file = $this->find_asset($file);

		/* has it already been added? */
		if (!isset($this->assets[$file]) && !empty($file)) {
			$html = $this->_ary2element('link', array_merge($this->link_attributes, ['href' => $file]));

			/* if where is actually true then return <link> */
			if ($where === true) {
				return $html;
			}

			$this->assets[$file] = ['ftype' => 'css','where' => $where,'file' => $file,'html' => $html];
		}

		return $this;
	}

	/*
	add js file
	$this->page->js('/assets/site.js');
	$this->page->js('/assets/site.js','<');
	$this->page->js(['/assets/site.js','/assets/site2.js'],'<');
	$script = $this->page->js('/assets/sites.js',true);
	*/
	public function js($file = '', $where = '>') {
		/* handle it if it's a array */
		if (is_array($file)) {
			foreach ($file as $f) {
				$this->js($f,$where);
			}

			return $this;
		}

		/* search the theme first then exact location */
		$file = $this->find_asset($file);

		if (!isset($this->assets[$file]) && !empty($file)) {
			$html = $this->_ary2element('script', array_merge($this->script_attributes, ['src' => $file]), '');

			/* if where is actually true then return <script> */
			if ($where === true) {
				return $html;
			}

			$this->assets[$file] = ['ftype' => 'js','where' => $where,'file' => $file,'html' => $html];
		}

		return $this;
	}

	public function js_var($key, $value = null) {
		/* handle it if it's a array */
		if (is_array($key)) {
			foreach ($key as $k=>$v) {
				$this->js_var($k,$v);
			}

			return $this;
		}

		/* raw */
		if ($value === true) {
			$this->javascript_variables[md5($key)] = $key;
		} else {
			$this->javascript_variables[$key] = 'var '.$key.'="'.str_replace('"', '\"', $value).'";';
		}

		return $this;
	}

	/* place inside <style> */
	public function style($style, $where = '>') {
		return $this->_data_core('page_style',$style,$where);
	}

	/* place inside <script> */
	public function script($script, $where = '>') {
		return $this->_data_core('page_script',$script,$where);
	}

	/*
	add a class to the page body tag
	this is great for css name spacing among other things.
	filters for repeats

	$this->page->body_class('name');
	$this->page->body_class('name name2 name3');
	*/
	public function body_class($class) {
		$this->page_body_class .= ' '.preg_replace("/[^a-z ]/",'',strtolower($class));

		return $this;
	}

	/* this will load partials, views and is chain-able (if return = false) it also fires a event! */
	public function view($view = null, $data = [], $return = false) {
		$view = ($view) ? $view : $this->route;

		/* anyone need to process something before load a view? */
		$this->ci_event->trigger('page.view',$view,$this,$data,$return); /* heavy overhead */
		$this->ci_event->trigger('page.view.'.str_replace('/', '.',trim($view,'/')),$this,$data,$return); /* more specific */

		if (is_string($return)) {
			$this->ci_load->vars([$return => $this->ci_load->view($view,$data,true)]);
		} else {
			$html = $this->ci_load->view($view, $data, $return);
		}

		if ($return === TRUE) {
			return $html;
		}

		return $this;
	}

	/* lot of looping here so we only call it once on build or manually */
	public function prep() {
		$this->ci_event->trigger('page.prep',$this);

		/* user id - default 0 */
		$userid = 'public';

		/* is the user even attached to the CI super object? */
		if (isset($this->CI->user)) {
			/* ok let's set the user id */
			$userid = md5($this->ci_user->id.$this->encryption_key);

			/* add active / not-active class to the page body */
			$this->body_class($this->ci_user->is_active ? 'active' : 'not-active');

			/* let's attach the user data to a view variable */
			$this->ci_load->vars(['userdata' => $this->CI->user]);
		}

		/* make sure classes are unique on the page body */
		$this->ci_load->vars(['page_body_class' => trim(implode(' ',array_keys(array_flip(explode(' ',$this->page_body_class)))))]);

		$base_url = trim(base_url(), '/');

		/* add a few more */
		$this->js_var('base_url',$base_url);
		$this->js_var('appid', md5($base_url));
		$this->js_var('controller_path', '/'.str_replace('/index', '', $this->route));
		$this->js_var('userid', $userid);

		/* fast and loose */
		$this->ci_load->vars(['javascript_variables' => implode('', $this->javascript_variables)]);

		/* add assets */
		foreach ($this->assets as $record) {
			$this->_data_core('page_'.$record['ftype'], $record['html'], $record['where']);
		}

		/*
		$combined_css = false;
		$combined_js = false;

		foreach ($this->assets as $record) {
			if (substr($record['file'],0,2) != '//') {
				switch ($record['ftype']) {
					case 'js':
						$combined_js .= file_get_contents($record['path']);
					break;
					case 'css':
						$combined_css .= file_get_contents($record['path']);
					break;
				}
			} else {
				$this->_data_core('page_'.$record['ftype'], $record['html'], $record['where']);
			}
		}

		if ($combined_css !== false) {
			$file = '/min/'.md5($combined_css).'.css';
			file_put_contents(ROOTPATH.'/public'.$file,$combined_css);
			$this->_data_core('page_css','<link href="'.$file.'" rel="stylesheet" type="text/css">', $record['where']);
		}

		if ($combined_js !== false) {
			$file = '/min/'.md5($combined_js).'.js';
			file_put_contents(ROOTPATH.'/public'.$file,$combined_js);
			$this->_data_core('page_js','<script src="'.$file.'"></script>','>');
		}
		*/

		return $this;
	}

	/* final output - fires a event */
	public function build($view = null, $layout = null) {
		/* anyone need to process something before build? */
		$this->ci_event->trigger('page.build',$this,$view,$layout);

		/* prep all the looping / array stuff */
		$this->prep();

		/* did they send in a view file for "page_center"? */
		$view = ($view) ? $view : $this->route;
		
		/* convert all controller automagic loads to underscores */
		$view = str_replace('-', '_',$view);

		/* fire off a view specific event */
		$this->ci_event->trigger('page.build.'.str_replace('/', '.',trim($view,'/')), $this, $layout);

		/* load the page center - data already attached directly to view variables array */
		$this->ci_load->vars(['page_center' => $this->ci_load->view($view, [], true)]);

		/* final output */
		$this->ci_load->view((($layout) ? $layout : $this->template), [], false);

		/* chaining not supported and if you try it will throw a error to "remind" the user */
	}

	/* !protected functions */
	protected function _data_core($name, $value, $where = '#') {
		switch ($where) {
			case '<': // Prepend value to a page variable
				$value = $value.$this->ci_load->get_var($name);
			break;
			case '>': // Append value to a page variable
				$value = $this->ci_load->get_var($name).$value;
			break;
			case '-': // Remove (make empty) a page variable
				$value = str_replace($value, '', $this->ci_load->get_var($name));
			break;
		}

		/* add it to the view variables */
		$this->ci_load->vars([$name => $value]);

		/* allow chaining */
		return $this;
	}

	protected function _ary2element($element, $attribs, $wrapper = false) {
		$output = '<'.$element.' ';

		foreach ($attribs as $name => $value) {
			if (!empty($value)) {
				$output .= $name.'="'.trim($value).'" ';
			}
		}

		$output = trim($output);

		return ($wrapper === false) ? $output.'/>' : $output.'>'.$wrapper.'</'.$element.'>';
	}

	protected function find_asset($www_path) {
		/* search:
		*
		* /public/{current_theme}/*
		* /public/*
		*
		*/

		return (file_exists(ROOTPATH.'/public'.$this->theme_path.$www_path)) ? $this->theme_path.$www_path : $www_path;
	}

} /* end class */