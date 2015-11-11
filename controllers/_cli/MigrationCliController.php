<?php

class migrationCliController extends O_CliController {

	public function __construct() {
		parent::__construct();

		$this->load->library('migration');
	}

	public function currentCliAction() {
		$version = $this->migration->current();

		if ($version === false) {
			show_error($this->migration->error_string());
		} elseif ($version === true) {
			$this->output('<yellow>No migrations found.');
		} else {
			$this->output('Version: <yellow>'.$version);
		}

		$this->output('<green>Complete');
	}

	public function findCliAction() {
		$files = $this->migration->find_migrations();

		foreach ($files as $f) {
			$this->output($f);
		}

		if (!empty($this->migration->error_string())) {
			show_error($this->migration->error_string());
		}

		$this->output('<green>Complete');
	}

	public function configCliAction() {
		include ROOTPATH.'/application/config/migration.php';

		foreach ($config as $k=>$c) {
			$this->output($k.' '.$c);
		}

		$this->output('<green>Complete');
	}

	public function latestCliAction() {

		$version = $this->migration->latest();

		if ($version === false) {
			show_error($this->migration->error_string());
		} else {
			$this->output('Version: <yellow>'.$version);
		}

		$this->output('<green>Complete');
	}

	public function versionCliAction($target_version) {
		$target_version = (int)$target_version;

		$version = $this->migration->version($target_version);

		if ($version === false) {
			show_error($this->migration->error_string());
		} elseif ($version === true) {
			$this->output('<yellow>No migrations found.');
		} else {
			$this->output('Version: <yellow>'.$version);
		}

		$this->output('<green>Complete');
	}

} /* end class */