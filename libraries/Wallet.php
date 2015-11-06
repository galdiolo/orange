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
*	NOTE: Some of the ideas and/or code for the Wallet methods
* are from various projects
* Unfortneulty I did not keep detailed records of where a
* idea and/or code may have came from
* If you see a bit of code and have a public repro
* which you are the maintainer of and can provide me a direct
* link I will add credit where credit is due!
*
*/
class Wallet {
	protected $messages = [];
	protected $request = [];

	protected $msg_key = 'internal::wallet::msg';
	protected $snap_key_prefix = 'internal::wallet::snap::';
	protected $url_key = 'internal::wallet::url';
	protected $breadcrumb_key = 'internal::wallet::breadcrumbs';
	protected $stash_key = 'internal::wallet::stash';

	protected $ci_load;
	protected $ci_session;
	protected $ci_input;

	protected $default_breadcrumb_style = [
		'crumb_divider'=>'<span class="divider"> / </span>',
		'tag_open'=>'<ul class="breadcrumb">',
		'tag_close'=>'</ul>',
		'crumb_open'=>'<li>',
		'crumb_last_open'=>'<li class="active">',
		'crumb_close'=>'</li>',
	];
	protected $default_msgs = [
		'success'=>'Request Completed',
		'failed'=>'Request Failed',
		'denied'=>'Access Denied',
		'created'=>'Record Created',
		'updated'=>'Record Updated',
		'deleted'=>'Record Deleted',
	];

	/* used for flash messages */
	protected $initial_pause;
	protected $pause_for_each;

	public function __construct() {
		/* let's make sure by the time we get here session is already setup */
		if (!isset(ci()->session)) {
			show_error('In order to use wallet session must already be loaded and setup.');
		}

		$this->ci_load = &ci()->load;
		$this->ci_session = &ci()->session;
		$this->ci_input = &ci()->input;

		/* store whatever is in the session from the last page into a variable for the current page */
		$this->ci_load->vars(['wallet_messages'=>[
			'messages'       => $this->ci_session->flashdata($this->msg_key),
			'initial_pause'  => $this->ci_load->setting('wallet','initial_pause',3),
			'pause_for_each' => $this->ci_load->setting('wallet','pause_for_each',1000),
		]]);

		$default_msgs = $this->ci_load->setting('wallet','default_msgs',null);

		if (is_array($default_msgs)) {
			$this->default_msgs = $default_msgs;
		}

		$default_breadcrumb_style = $this->ci_load->setting('wallet','default_breadcrumb_style',null);

		if (is_array($default_breadcrumb_style)) {
			$this->default_breadcrumb_style = $default_breadcrumb_style;
		}
	}

	/*
	Page Request Shared Storage

	This data is lost between page requests
	*/
	public function pocket($name,$value=null) {
		$return = $this;

		if ($value) {
			$this->request[$name] = $value;
		}	else {
			$return = (isset($this->request[$name])) ? $this->request[$name] : null;
		}

		return $return;
	}

	/**
	* Add or change snapdata
	* Snap Data is available
	* until it's read
	*
	* @param	mixed
	* @param	string
	* @return void
	*/
	public function snapdata($newdata = [], $newval = '') {
		if (is_string($newdata)) {
			$newdata = [$newdata => $newval];
		}

		if (count($newdata) > 0) {
			foreach ($newdata as $key => $val) {
				$this->ci_session->set_userdata($this->snap_key_prefix.$key, $val);
			}
		}

		return $this;
	}

	/**
	* Fetch a specific snapdata item from the session array
	* removed after read
	*
	* @param	string
	* @return	string
	*/
	public function get_snapdata($key) {
		/* read the snap data */
		$data = $this->ci_session->userdata($this->snap_key_prefix.$key);

		/* unset/remove the snap data */
		$this->ci_session->unset_userdata($this->snap_key_prefix.$key);

		/* return the snap data */
		return $data;
	}

	/**
	* Fetch a specific snapdata item from the session array
	* DO NOT removed after read
	*
	* @param	string
	* @return	string
	*/
	public function keep_snapdata($key) {
		/* read and return the snap data */
		return $this->ci_session->userdata($this->snap_key_prefix.$key);
	}

	/* save url for use later */
	public function save_previous_page() {
		return $this->save_this_page(parse_url($this->ci_input->server('HTTP_REFERER'), PHP_URL_PATH));
	}

	/* save this page's url for use later */
	public function save_this_page($url=null) {
		$url = ($url) ? $url : $this->ci_input->server('REQUEST_URI');

		return $this->snapdata($this->url_key,$url);
	}

	public function clear_saved_page() {
		$who_cares = $this->get_snapdata($this->url_key);

		return $this;
	}

	/* get url from last save if nothing is saved then return the default sent in */
	public function saved_page($default_url='') {
		$previous_url = $this->get_snapdata($this->url_key);

		return (!empty($previous_url)) ? $previous_url : $default_url;
	}

	/* append a new bread crumb on the end */
	public function breadcrumb($page=null,$href=null) {
		/* no page or href provided */
		if ($page && $href) {
			$breadcrumbs = $this->ci_session->userdata($this->breadcrumb_key);

			/* Prepend site url */
			$href = site_url($href);

			/* push breadcrumb */
			$breadcrumbs[] = ['page'=>$page,'href'=>$href];

			$this->ci_session->set_userdata($this->breadcrumb_key,$breadcrumbs);
		}

		return $this;
	}

	/* return all bread crumbs */
	public function breadcrumbs() {
		return $this->ci_session->userdata($this->breadcrumb_key);
	}

	/* clear all breadcrumbs */
	public function eat_breadcrumbs() {
		$this->ci_session->unset_userdata($this->breadcrumb_key);

		return $this;
	}

	/* pop a bread crumb off the end and return it */
	public function eat_breadcrumb($which='last') {
		$breadcrumbs = $this->ci_session->userdata($this->breadcrumb_key);

		if ($which == 'first') {
			/* shift of the first */
			$crumb = array_shift($breadcrumbs);
		} else {
			/* pop off the last */
			$crumb = array_pop($breadcrumbs);
		}

		$this->ci_session->set_userdata($this->breadcrumb_key,$breadcrumbs);

		return $crumb;
	}

	public function breadcrumbs_as_html($style=[]) {
		$style = array_merge($this->default_breadcrumb_style,$style);

		$breadcrumbs = $this->breadcrumbs();
		$output = '';

		if (is_array($breadcrumbs)) {
			/* set output variable */
			$output .= $style['tag_open'];

			/* construct output */
			foreach ($breadcrumbs as $key => $crumb) {
				$keys = array_keys($breadcrumbs);

				/* if it's the last use last opening */
				if (end($keys) == $key) {
					$output .= $style['crumb_last_open'].$crumb['page'].$style['crumb_close'];
				} else {
					$output .= $style['crumb_open'].'<a href="'.$crumb['href'].'">'.$crumb['page'].'</a>'.$style['crumb_divider'].$style['crumb_close'];
				}
			}

			/* return output */
			$output .= $style['tag_close'];
		}

		/* no crumbs */
		return $output;
	}

	/* flash messages */
	public function msg($msg = '', $type = 'yellow', $sticky = false, $redirect = null) {
		ci()->event->trigger('wallet.msg',$msg,$type,$sticky,$redirect);

		/* show it on this page? if redirect is true */
		if ($redirect === true) {
			/* are there any messages set via session or other? */
			$wallet_messages = $this->ci_load->get_var('wallet_messages');
			
			$current_msgs = (array)$wallet_messages['messages'];

			/* add to them */
			$current_msgs[] = ['msg' => trim($msg),'type' => $type,'sticky' => $sticky];
			
			/* put it back in the page variables */
			$this->ci_load->vars(['wallet_messages' => [
				'messages'       => $current_msgs,
				'initial_pause'  => $this->ci_load->setting('wallet','initial_pause',3),
				'pause_for_each' => $this->ci_load->setting('wallet','pause_for_each',1000),
			]]);
		} else {
			/* show it on the next page (flash msg style) */
			$this->messages[] = ['msg' => trim($msg),'type' => $type,'sticky' => $sticky];
			$this->ci_session->set_flashdata($this->msg_key, $this->messages);

			/* redirect to another page immediately */
			if (is_string($redirect)) {
				redirect($redirect);

				exit; /* shouldn't be needed but just incase */
			}
		}

		return $this;
	}

	public function success($msg,$redirect=null) {
		$msg = (!empty($msg)) ? $msg : $this->default_msgs['success'];

		return $this->msg($msg,'blue',false,$redirect);
	}

	public function failed($msg=null,$redirect=null) {
		$msg = (!empty($msg)) ? $msg : $this->default_msgs['failed'];

		return $this->msg($msg,'red',true,$redirect);
	}

	public function denied($msg=null,$redirect=null) {
		$msg = (!empty($msg)) ? $msg : $this->default_msgs['denied'];

		return $this->msg($msg,'red',true,$redirect);
	}

	public function created($title=null,$redirect=null) {
		$msg = (!empty($title)) ? $title.' Created' : $this->default_msgs['created'];

		return $this->msg($msg,'blue',false,$redirect);
	}

	public function updated($title=null,$redirect=null) {
		$msg = (!empty($title)) ? $title.' Updated' : $this->default_msgs['updated'];

		return $this->msg($msg,'blue',false,$redirect);
	}

	public function deleted($title=null,$redirect=null) {
		$msg = (!empty($title)) ? $title.' Deleted' : $this->default_msgs['deleted'];

		return $this->msg($msg,'blue',false,$redirect);
	}

	public function red($msg,$redirect=null) {
		return $this->msg($msg,'red',true,$redirect);
	}

	public function blue($msg,$redirect=null) {
		return $this->msg($msg,'blue',false,$redirect);
	}

	public function green($msg,$redirect=null) {
		return $this->msg($msg,'green',false,$redirect);
	}

	public function yellow($msg,$redirect=null) {
		return $this->msg($msg,'yellow',true,$redirect);
	}

	/**
	* Stash the user posted input in a session variable for later retrieval
	* New Function
	*
	* @return	mixed		reference to this object to allow chaining
	*/
	public function stash() {
		/* store RAW $_POST */
		$this->snapdata($this->stash_key, $_POST);

		return $this;
	}

	/**
	* unStash the user posted input and clear the cache
	* This also auto loads the $_POST variable again
	* incase you need to access it via CodeIgniter methods
	* which would be preferred for security
	* New Function
	*
	* @return	mixed		stored post variables
	*/
	public function unstash() {
		$stashed = $this->get_snapdata($this->stash_key);

		/* put back RAW $_POST */
		$_POST = (is_array($stashed)) ? $stashed : [];

		/* and return stored */
		return $stashed;
	}

} /* end class */