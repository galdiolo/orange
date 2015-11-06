<?php

class packageCliController extends O_CliController {
	public $map = ['install'=>'installed','uninstall'=>'uninstalled','delete'=>'deleted','upgrade'=>'upgraded'];
	public $packages = [];

	public function __construct() {
		parent::__construct();

		$this->load->library('package_manager');

		$this->packages = ci()->package_manager->records();
	}

	public function dumpCliAction($package_name=null) {
		if ($package_name) {
			if (!$record = $this->package_exist($package_name)) {
				$this->output('<red>Package doesn\'t exist.',true);
			}
		} else {
			$record = $this->packages;
		}

		var_export($record);
	}

	public function detailsCliAction($package_name=null) {
		if (!$package_name) {
			$this->output('<red>Please include package name.',true);
		}

		if (!$record = $this->package_exist($package_name)) {
			$this->output('<red>Package doesn\'t exist.',true);
		}
		
		$this->output('<blue>Name: <off>'.$record['name']);
		$this->output('<blue>Internal Name: <off>'.$record['folder']);
		$this->output('<blue>Is Active: <off>'.(($record['is_active'] == 1) ? 'true' : 'false'));
		$this->output('<blue>Description: <off>'.$record['info']);
		$this->output('<blue>Version: <off>'.$record['version']);
		$this->output('<blue>Type: <off>'.$record['type']);
		$this->output('<blue>Priority: <off>'.$record['priority']);
		$map = [1=>'Less Than',2=>'Equal To',3=>'Greater Than'];
		$this->output('<blue>Migration Status: <off>'.$map[$record['version_check']].' Migration Version');
		$this->output('<blue>Total Migrations: <off>'.count($record['migrations']));
		$this->output('<green>Requirements:');
		$this->output('<blue>Missing Required Packages: <off>'.implode(chr(10),$record['package_error_raw']));
		$this->output('<blue>Missing Required Composer Packages: <off>'.implode(chr(10),$record['composer_error_raw']));
		$this->output('<blue>Required By: <off>'.chr(10).implode(chr(10),$record['required_error']));
		if (count($record['cli']) > 0) {
			$this->output('<green>Command Line Methods:');
			foreach ($record['cli'] as $a=>$b) {
				$this->output('<blue>'.$a.'<off> '.$b);
			}
		}
		
	}

	public function listCliAction() {
		/*
		1 = less than
		2 = exact
		3 = greater than

		http://www.orange.dev/admin/configure/packages/upgrade/6578616d706c655f706572736f6e
		*/
		$spacers = chr(9);
		$spacer2 = $spacers.'-'.$spacers;

		foreach ($this->packages as $package) {
			$options = [];

			if ($package['button']['install']) {
				$options[] = '<green>install';
			}

			if ($package['button']['upgrade']) {
				$options[] = '<blue>upgrade';
			}

			if ($package['button']['uninstall']) {
				$options[] = '<magenta>uninstall';
			}
				
			if (!$package['json_error']) {
				$this->output(str_pad($package['folder'],32).' '.str_pad($package['name'],32).' '.str_pad($package['version'],8).' '.implode(' ',$options));
	
				$errors = array_merge_recursive((array)$package['required_error'],(array)$package['composer_error']);
	
				if ($package['has_errors']) {
					foreach ($errors as $err) {
						$this->output($spacers.'<red>'.$err);
					}
				}
			}

		}
	}

	/* install / upgrade */
	public function installCliAction($package=null) {
		$this->upCliAction($package);
	}

	public function upgradeCliAction($package=null) {
		$this->upCliAction($package);
	}
	
	public function upCliAction($package=null) {
		if (!$package) {
			$this->output('<red>Please include package name.',true);
		}

		if (!$this->package_exist($package)) {
			$this->output('<red>Package doesn\'t exist.',true);
		}

		if (!$this->packages[$package]['button']['install'] && !$this->packages[$package]['button']['upgrade']) {
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
	
	public function uninstallCliAction($package=null) {
		$this->downCliAction($package);
	}
	
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
				return $p;
			}
		}

		return false;
	}

} /* end class */