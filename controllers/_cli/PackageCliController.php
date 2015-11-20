<?php

class packageCliController extends O_CliController {
	public $map = ['install'=>'installed','uninstall'=>'uninstalled','delete'=>'deleted','upgrade'=>'upgraded'];
	public $packages = [];
	public $force = false;

	public function __construct() {
		parent::__construct();

		$this->load->library('package_manager');

		$this->packages = ci()->package_manager->prepare();
	}

	/* dump all packages or a specific package if namespace provided */
	public function dumpCliAction() {
		$package_name = implode('/',func_get_args());

		if ($package_name) {
			if (!$record = $this->package_exist($package_name)) {
				$this->output('<red>Package doesn\'t exist.',true);
			}
		} else {
			$record = $this->packages;
		}

		var_export($record[0]);
	}

	/* list all packages namespace */
	public function packagesCliAction() {
		foreach ($this->packages as $p) {
			$this->output($p['composer']['name']);
		}
	}

	/* show human readable package details */
	public function detailsCliAction() {
		$record = $this->get_record(func_get_args());

		$this->output('<blue>Name: <off>'.$record['composer']['name']);

		$this->output('<blue>Authors:');

		foreach ($record['composer']['authors'] as $a) {
			foreach ($a as $key=>$val) {
				$this->output(' <blue>'.$key.':<off> '.$val);
			}
		}

		$this->output('<blue>Keywords: <off>'.implode(', ',$record['composer']['keywords']));
		$this->output('<blue>Homepage: <off>'.$record['composer']['homepage']);
		$this->output('<blue>License: <off>'.$record['composer']['license']);
		$this->output('<blue>Location: <off>'.$record['folder']);
		$this->output('<blue>Description: <off>'.$record['composer']['description']);

		if (isset($record['composer']['orange'])) {
			$this->output('<blue>Active: <off>'.(($record['database']['is_active']) ? '<green>true' : '<red>false'));

			$this->output('<green>Framework Details');

			$this->output('<blue>Type: <off>'.$record['composer']['orange']['type']);

			$this->output('<blue>Command Line:');

			foreach ($record['composer']['orange']['cli'] as $key=>$val) {
				$this->output(' <yellow>'.$key.':<off> '.$val);
			}

			if ($record['folder'] == 'framework') {
				$this->output('<blue>This is a: <off>Framework Package');
			} else {
				$this->output('<blue>This is a: <off>Composer Package');
			}

			$this->output('<blue>Package Priority: <off>'.$record['composer']['orange']['priority'].' - '.$record['human_priority']);
			$this->output('<blue>Help: <off>'.str_replace('<br>',chr(10),$record['composer']['orange']['homepage']));
			$this->output('<blue>Notes: <off>'.$record['orange']['notes']);

			$this->output('<green>Migrations / Version');

			$this->output('<blue>Installed Version: <off>'.$record['database']['migration_version']);
			$this->output('<blue>Current Package Version: <off>'.$record['composer']['orange']['version']);

			$map = [1=>'less than',2=>'equal to',3=>'greater than'];
			$this->output('<blue>Migration Status: <off>Installed version is '.$map[$record['version_check']].' current package version.');

			$migrations = ($record['migrations']['has_migrations'] ? 'true' : 'false').' / '.count($record['migrations']['files']);

			$this->output('<blue>Has Migrations: <off>'.$migrations);
			$this->output('<blue>Migrations that need to be run:');
			foreach ($record['migrations']['files'] as $f)  {
				$this->output(basename($f));
			}

			$this->output('<green>Adds');
			$this->output('<blue>Tables: '.str_replace(',',chr(10),$record['orange']['tables']));
			$this->output('<blue>Access: '.str_replace(',',chr(10),$record['orange']['access']));
			$this->output('<blue>Menubar: '.str_replace(',',chr(10),$record['orange']['menubar']));
			$this->output('<blue>Settings: '.str_replace(',',chr(10),$record['orange']['settings']));
		}

		$this->output('<green>Requirements');

		$this->output('<blue>Require');
		foreach ($record['composer']['require'] as $folder=>$version) {
			$this->output(' <yellow>'.$folder.' <yellow>'.$version);
		}

		$this->output('<red>Missing Packages');
		$this->output(' <yellow>'.implode(chr(10).' ',$record['package_not_available']));

		$this->output('<red>Available but not active packages');
		$this->output(' <yellow>'.implode(chr(10).' ',$record['package_not_active']));

		$this->output('<red>Required By');
		$this->output(' <yellow>'.implode(chr(10).' ',$record['is_required_by']));

		$this->output('<green>Options');

		foreach ($record['buttons'] as $k=>$b) {
			if ($b) {
				if ($k != 'error') {
					$this->output($k);
				}
			}
		}

		$this->output();
	}

	public function todoCliAction() {
		foreach ($this->packages as $record) {
			$this->output($record['composer']['name']);

			if ($record['buttons']['error']) {
				$this->output('<red> See Details');
			}

			foreach ($record['buttons'] as $k=>$b) {
				if ($b) {
					if ($k != 'error') {
						$this->output(' <blue>'.$k);
					}
				}
			}

		}
	}

	public function errorsCliAction() {
		foreach ($this->packages as $record) {
			if (isset($record['composer']['orange'])) {
				$this->output(chr(10).'*** '.$record['composer']['name']);

				if (count($record['package_not_available']) > 0) {
					$this->output('<red>Missing Packages');
					$this->output(' <yellow>'.implode(chr(10).' ',$record['package_not_available']));
				}

				if (count($record['package_not_active']) > 0) {
					$this->output('<red>Available but not active packages');
					$this->output(' <yellow>'.implode(chr(10).' ',$record['package_not_active']));
				}

			}
		}
	}

	public function deactivateCliAction() {
		$record = $this->get_record(func_get_args());

		if (!$record['buttons']['deactivate'] && $this->force == false) {
			return $this->output('<red>Deactivate not available');
		}

		if (!$this->package_manager->deactivate($record['key'])) {
			$this->output('<red>Error',true);
		}

		$this->output('<green>Complete');
	}

	public function activateCliAction($package=null) {
		$record = $this->get_record(func_get_args());

		if (!$record['buttons']['activate'] && $this->force == false) {
			return $this->output('<red>Activate not available');
		}

		if (!$this->package_manager->activate($record['key'])) {
			$this->output('<red>Error',true);
		}

		$this->output('<green>Complete');
	}

	public function migrateCliAction($package=null) {
		$record = $this->get_record(func_get_args());

		if (!$record['buttons']['upgrade'] && $this->force == false) {
			return $this->output('<red>Migrate not available');
		}

		if (!$this->package_manager->upgrade($record['key'])) {
			$this->output('<red>Error',true);
		}

		$this->output('<green>Complete');
	}

	public function uninstallCliAction($package=null) {
		$record = $this->get_record(func_get_args());

		if (!$record['buttons']['uninstall'] && $this->force == false) {
			return $this->output('<red>Uninstall not available');
		}

		if (!$this->package_manager->uninstall($record['key'])) {
			$this->output('<red>Error',true);
		}

		$this->output('<green>Complete');
	}

	public function requiresCliAction() {
		$package_name = implode('/',func_get_args());

		if ($package_name) {
			if (!$record = $this->package_exist($package_name)) {
				$this->output('<red>Package doesn\'t exist.',true);
			}

			$record = [$record];
		} else {
			$record = $this->packages;
		}

		foreach ((array)$record as $r) {
			$this->output(chr(10).'*** '.$r['composer']['name']);
			foreach ($r['composer']['require'] as $k=>$v) {
				$this->output(' <red>'.$k.' '.$v);
			}
		}

		$this->output('<green>Complete');
	}

	/* used above */
	protected function get_record($input) {
		if (end($input) == 'force') {
			/* pop it off */
			array_pop($input);
			$this->force = true;
		}
	
		$package_name = implode('/',$input);

		if (!$package_name) {
			$this->output('<red>Please include package name.',true);
		}

		if (!$record = $this->package_exist($package_name)) {
			$this->output('<red>Package doesn\'t exist.',true);
		}

		return $record;
	}

	protected function package_exist($package_name) {
		foreach ($this->packages as $p) {
			if ($p['composer']['name'] == trim($package_name)) {
				return $p;
			}
		}

		return false;
	}

} /* end class */