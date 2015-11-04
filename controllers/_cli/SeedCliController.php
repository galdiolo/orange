<?php

class seedCliController extends O_CliController {

	public function _remap($method,$param=[]) {
		$model = substr($method,0,-9);

		$this->output('<green>Loading model <white>'.$model);

		try {
			$this->load->model($model);
		} catch (Exception $e) {
			$this->output('<red>Could not load model <off>'.$model,true);
		}

		$this->output('<green>Model loaded');

		if (!method_exists($this->$model,'seed')) {
			$this->output('<red>Seed method not available in <off>'.$model,true);
		}

		$count = ((int)$param[0] > 0) ? (int)$param[0] : 1;

		if ($this->$model->seed($count)) {
			$this->output('<green>'.$count.' records created',true);
		} else {
			$this->output('<red>Error',true);
		}
	}

} /* end class */