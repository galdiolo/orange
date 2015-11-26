<?php

trait t_admin_delete {

	/* delete record */
	public function deleteAction($id = null) {
		if ($this->access['delete']) {
			$this->has_access($this->access['delete']);
		}

		$this->input->is_valid($this->{$this->controller_model}->rules['id']['rules'], $id);

		$this->output->json('err', !$this->{$this->controller_model}->delete($id));
	}

} /* end trait */