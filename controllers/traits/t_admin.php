<?php

trait t_admin {

	/* get the form data for the model */
	protected function _get_data($which = null,$model_name=null) {
		/* what model are we trying to get form data from? */
		if (is_object($model_name)) {
			$model = $model_name;
		} elseif (is_string($model_name)) {
			$model = $this->$model_name;
		} elseif (isset($this->controller_model)) {
			$model = $this->{$this->controller_model};
		} else {
			show_error('Could not get input from model.');
		}

		/*
		First check to see if this controllers $this->form array has a matching key to map the form values to the $this->data variable
		Second check to see if the default model on this controller has a matching rule set to map the form values to the $this->data variable
		Third just send back the enitre input->post();
		*/

		/* is it a rule set in the controller $form[$which] array? */
		if (isset($this->forms[$which])) {
			$this->input->map($this->forms[$which], $this->data);

		/* is it a rule set in the model? */
		} elseif ($model->get_rule_set($which) !== NULL) {
			$this->input->map($model->get_rule_set($which), $this->data);

		/* just get the entire form? */
		} else {
			$this->data = $this->input->post();
		}

		return $this->data;
	}

} /* end controller */