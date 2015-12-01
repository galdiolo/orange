<?php

trait t_security {

	/* has access test for a controller */
	public function has_access($access,$die=true) {
		$success = $this->auth->has_access($access);

		if ($success === false && $die === true) {
			$this->access_denied();
			exit;
		}

		return $success;
	}

	/* make throwing a "access denied" from a controller easier */
	public function access_denied($url = '') {
		$this->auth->denied($url);
	}

} /* end controller */