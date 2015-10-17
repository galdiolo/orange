<?php
trait validate_dependent {
	/* dependent on orange user record */
	public function access($field, $options = null) {
		$this->set_message('access', 'You do not have access to %s');

		$user_data = (is_object($this->ci_user)) ? $this->ci_user : [];

		return (bool) (in_array($field, $user_data['access']));
	}

	/* dependent on a database model */
	public function primary_exists($field, $options = null) {
		/* little assumption here $this->database is loaded */
		/* $options = model name */
		$this->set_message('exists', 'The %s that you requested is unavailable.');

		/* try to load the model */
		$this->ci_load->model($options);

		return ci()->$options->primary_exists($field);
	}

	/* dependent on a database model */
	public function exists($field, $options = null) {
		/* exists[model_name.column] */
		$this->set_message('exists', 'The %s that you requested is unavailable.');

		list($model, $column) = explode('.', $options, 2);

		/* try to load the model */
		$this->ci_load->model($model);

		return (method_exists(ci()->$model, 'exists')) ? ci()->$model->exists($column, $field) : false;
	}

	/* dependent on a database model is_uniquem[o_user_model.email.id] */
	public function is_uniquem($field, $options = null) {
		/* is_uniquem[model_name,column_name,$_POST[primary_key]] */
		$this->set_message('is_uniquem', 'The %s is already being used.');

		list($model, $column, $postkey) = explode('.', $options, 3);

		/* try to load the model */
		$this->ci_load->model($model);

		return ci()->$model->is_uniquem($field, $column, $postkey);
	}

	public function record_match(&$field, $options = null) {
		/* is_uniquem[model_name,column_name,$_POST[primary_key]] */
		$this->set_message('record_match', 'You don\'t has access to this record.');

		// has_access[admin_notice_model.msg_id.$msg_id.user_id.$user_id]
		list($model, $record_column1, $record_value1, $record_column2, $record_value2) = explode('.', $options, 5);

		/* try to load the model */
		$this->ci_load->model($model);

		$record = ci()->$model->get_by([$record_column1=>$record_value1]);

		return ($record->$record_column2 == $record_value2);
	}

	/* dependent on the record_group_id being set on the form */
	public function if_empty_group_id(&$field, $options = null) {
		$err_msg = 'Group id validation failure.';

		$this->set_message('if_empty_group_id', $err_msg);

		/* is group_id currently empty? */
		if (empty($field)) {
			/* Yes! Did the the current_group_id hidden get set? */
			$current_group_id = ci()->input->post('current_group_id');

			if ($current_group_id) {
				/* Yes! ok then let's decode that and use it */
				ci()->load->library('encrypt');

				$group_id = ci()->encrypt->decode($current_group_id);

				if (!is_numeric($group_id)) {
					if (ci()->input->is_ajax_request()) {
						/* die hard */
						echo '{"err":true,"errors":"<p>'.$err_msg.'<\/p>\n","errors_array":["'.$err_msg.'"]}';
						exit;
					} else {
						show_error($err_msg);
					}
				}

				$field = $group_id;
			}
		}

		/*
		always true either it found it and set
		the field (by ref) or not in which case
		the next rule will run
		usually ifempty set it to the users new record group id
		*/
		return TRUE;
	}
} /* end class */