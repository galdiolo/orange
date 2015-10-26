<?php

class packageCliController extends O_CliController {

	public function details() {
		$this->load->library('package_manager');

		$r = $this->package_manager->records();

		var_dump($r);
	}

	public function up($package=null) {

	}

	public function down($package=null) {

	}

} /* end class */