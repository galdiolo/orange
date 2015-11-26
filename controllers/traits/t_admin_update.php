<?php

trait t_admin_update {

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
			$this->wallet->updated($this->controller_title, $this->controller_path);
		}

		log_message('debug', $this->{$this->controller_model}->errors);

		$this->wallet->failed('Record Update Error', $this->controller_path);
	}

} /* end trait */