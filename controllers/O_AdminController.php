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
	public $theme_folder = 'orange';
	public $body_class = 'admin';

	/* your basic scaffolding */
	public $controller_path = null; /* url to this controller */
	public $controller = ''; /* controller name */
	public $controller_title = ''; /* used in various places */
	public $controller_titles = '';  /* used in various places */
	public $controller_model = null; /* allows autoloading */
	public $has_access = null; /* array, single, * everyone, @ everyone logged in, null will always fail therefore you must set has_access */

	public function __construct() {
		$this->onload_visibility = 'admin';
		
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
	
	/* has access test for the Admin controllers */
	public function has_access($access,$die=true) {
		$success = $this->auth->has_access($access);

		if ($success === false && $die === true) {
			$this->access_denied();
			exit(1);
		}

		return $success;
	}
	
	/* make throwing a "access denied" from a admin controller easier */
	public function access_denied($url = '') {
		$this->auth->denied($url);
	}

	/* crud functions */
	
	/* read */
	public function indexAction() {
		if ($this->access['read']) {
			$this->has_access($this->access['read']);
		}
	
		if ($this->controller_model != NULL) {
			/* get all records apply order by or search if any */
			$records = $this->{$this->controller_model}->index($this->controller_orderby);
		}

		$this->page->data('records',$records)->build($this->controller_path.'/index');
	}

	/* create */
	public function newAction() {
		if ($this->access['create']) {
			$this->has_access($this->access['create']);
		}

		$data = [
			'record' => (object)['id' => -1],
			'controller_action' => 'new',
			'controller_action_title' => 'New',
		];

		$this->page->data($data)->build($this->controller_path.'/form');
	}
	
	/* create validate form input */
	public function newValidatePostAction() {
		if ($this->access['create']) {
			$this->has_access($this->access['create']);
		}

		$this->_get_data('insert');
		$this->{$this->controller_model}->validate($this->data, 'insert');
		$this->output->json($this->{$this->controller_model}->errors_json);
	}

	/* create record */
	public function newPostAction() {
		if ($this->access['create']) {
			$this->has_access($this->access['create']);
		}

		$this->_get_data('insert');

		if ($id = $this->{$this->controller_model}->insert($this->data, false)) {
			$this->wallet->created($this->content_title, $this->controller_path);
		}

		log_message('debug', $this->{$this->controller_model}->errors);

		$this->wallet->failed($this->content_title, $this->controller_path);
	}
	
	/* update */
	public function editAction($id = null) {
		if ($this->access['update']) {
			$this->has_access($this->access['update']);
		}

		$this->input->is_valid($this->{$this->controller_model}->rules['id']['rules'], $id);

		$data = [
			'record' => $this->{$this->controller_model}->get($id),
			'controller_action' => 'edit',
			'controller_action_title' => 'Edit',
		];

		$this->page->data($data)->build($this->controller_path.'/form');
	}
	
	/* update validate form input */
	public function editValidatePostAction() {
		if ($this->access['update']) {
			$this->has_access($this->access['update']);
		}

		$this->_get_data('update');
		$this->{$this->controller_model}->validate($this->data, 'update');
		$this->output->json($this->{$this->controller_model}->errors_json);
	}
	
	/* update record */
	public function editPostAction() {
		if ($this->access['update']) {
			$this->has_access($this->access['update']);
		}

		$this->input->is_valid($this->{$this->controller_model}->rules['id']['rules'], '@id');

		$this->_get_data('update');

		if ($this->{$this->controller_model}->update($this->data['id'], $this->data, false)) {
			$this->wallet->updated($this->content_title, $this->controller_path);
		}

		log_message('debug', $this->{$this->controller_model}->errors);

		$this->wallet->failed($this->content_title, $this->controller_path);
	}
	
	/* delete record */
	public function deleteAction($id = null) {
		if ($this->access['delete']) {
			$this->has_access($this->access['delete']);
		}

		$this->input->is_valid($this->{$this->controller_model}->rules['id']['rules'], $id);

		$this->output->json('err', !$this->{$this->controller_model}->delete($id));
	}
	
	/* standard format content into tabs if needed in the view */
	protected function _format_tabs($tabs_dbc, $tab_text = 'tab') {
		$tabs = [];
		$records = [];

		foreach ($tabs_dbc as $record) {
			$tab_name = preg_replace('/[^0-9a-z]+/', '', strtolower($record->$tab_text));

			$record->tab_text = $record->$tab_text;
			$tabs[$tab_name] = $record;
			$records[$tab_name][] = $record;
		}

		ksort($tabs);

		return ['tabs' => $tabs,'records' => $records];
	}
	
	/* get the form data for the model */
	protected function _get_data($which = null) {
		/*
		First check to see if this controllers $this->form array has a matching key to map the form values to the $this->data variable
		Second check to see if the default model on this controller has a matching rule set to map the form values to the $this->data variable
		Third just send back the enitre input->post();
		*/

		/* is it a rule set in the controller $form[$which] array? */
		if (isset($this->forms[$which])) {
			$this->input->map($this->forms[$which], $this->data);

		/* is it a rule set in the model? */
		} elseif ($this->{$this->controller_model}->get_rule_set($which) !== NULL) {
			$this->input->map($this->{$this->controller_model}->get_rule_set($which), $this->data);

		/* just get the entire form? */
		} else {
			$this->data = $this->input->post();
		}

		return $this->data;
	}

} /* end controller */