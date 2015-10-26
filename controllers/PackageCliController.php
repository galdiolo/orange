<?php

class packageCliController extends O_CliController {
	public $map = ['install'=>'installed','uninstall'=>'uninstalled','delete'=>'deleted','upgrade'=>'upgraded'];
	public $packages = [];

	public function __construct() {
		parent::__construct();

		$this->load->library('package_manager');

		$this->packages = ci()->package_manager->records();
	}

	public function detailsCliAction($complete=null) {
		/*
		1 = less than
		2 = exact
		3 = greater than

		http://www.orange.dev/admin/configure/packages/upgrade/6578616d706c655f706572736f6e
		*/

		if ($complete) {
			var_dump($this->packages);
		}

		foreach ($this->packages as $package) {
			$has_migration = ($package['version_check'] == 3) ? true : false;

			$m = ($has_migration) ? '<green>Has Migration.' : '';

			$this->output($package['folder'].' - '.$package['name'].' '.$package['version'].' '.$m);

			if ($has_migration && count($package['required_error_raw']) > 0) {
				$this->output('             requires:');
				foreach ($package['required_error_raw'] as $rer) {
					$this->output('          '.$rer);
				}
			}

		}
	}

	/* install / upgrade */
	public function upCliAction($package=null) {
		if (!$package) {
			$this->output('<red>Please include package name.',true);
		}

		if (!$this->package_exist($package)) {
			$this->output('<red>Package doesn\'t exist.',true);
		}

		if ($this->packages[$package]['version_check'] !== 3) {
			$this->output('<red>Package doesn\'t need upgrading.',true);
		}

		/* dump all caches */
		ci()->cache->clean();

		/* also refresh the user data */
		ci()->auth->refresh_userdata();

		$method = ($this->packages[$package]['is_active'] == false) ? 'install' : 'upgrade';

		if (ci()->package_manager->$method($package) !== true) {
			$this->output('<red>Package "UP" error.',true);
		}

		$this->output('<green>Package "UP" success.');
	}

	/* uninstall */
	public function downCliAction($package=null) {
		if (!$package) {
			$this->output('<red>Please include package name.',true);
		}

		if (!$this->package_exist($package)) {
			$this->output('<red>Package doesn\'t exist.',true);
		}

		/* dump all caches */
		ci()->cache->clean();

		/* also refresh the user data */
		ci()->auth->refresh_userdata();

		if (ci()->package_manager->uninstall($package) !== true) {
			$this->output('<red>Package "DOWN" error.',true);
		}

		$this->output('<green>Package "DOWN" success.');
	}

	protected function package_exist($package_name) {
		foreach ($this->packages as $p) {
			if ($p['folder'] == $package_name) {
				return true;
			}
		}

		return false;
	}

} /* end class */