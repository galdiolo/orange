<?php

trait t_admin_create {

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
			$this->wallet->created($this->controller_title, $this->controller_path);
		}

		log_message('debug', $this->{$this->controller_model}->errors);

		$this->wallet->failed('Record Creation Error', $this->controller_path);
	}

} /* end trait */