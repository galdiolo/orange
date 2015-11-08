<?php

class package_helper {
	protected $packages;
	protected $namespace_packages = [];
	protected $active_packages = [];
	protected $package_migration_manager;
	protected $o_packages_model;

	public function requirements(&$packages) {
		/* save this for later to add errors */
		$this->packages = &$packages;
		$this->package_migration_manager = &ci()->package_migration_manager;
		$this->o_packages_model = &ci()->o_packages_model;

		/* first convert array from full path keys to composer namespace keys */
		foreach ($packages as $key=>$package) {
			$this->namespace_packages[$package['composer_name']] = $package;

			if ($package['is_active']) {
				$this->active_packages[$key] = $package;
			}
		}

		/* ok now loop over the packages to determine the requirements */
		foreach ($this->namespace_packages as $key=>$package) {
			$package_key = $package['full_path'];
		
			$this->packages[$package_key]['errors'] = '';
			$this->packages[$package_key]['errors_raw'] = [];
			$this->packages[$package_key]['has_error'] = false;

			$this->_test_requirement($package_key,$package);
		}
	}

	protected function _test_requirement($key,$package) {
	/* does this package have any requirements? */
		if (is_array($package['require'])) {
			/* yes - is the required package in the active packages? */
			if (!array_key_exists($package['composer_name'],$this->active_packages)) {
				$missing_package = $this->namespace_packages[$package['composer_name']];
				/* no - add an error */

				$this->packages[$key]['required_errors'][] = $missing_package['composer_name'];
				$this->packages[$key]['required_errors_raw'][] = $missing_package['composer_name'];
				$this->packages[$key]['has_error'] = true;
			}
		}
	}

	public function migrations(&$packages) {
		/* save this for later to add errors */
		$this->packages = &$packages;

		foreach ($this->packages as $key=>$config) {
			/* starting version for migrations */
			$starting_version = ($config['migration_version']) ? $config['migration_version'] : '0.0.0';

			$migration_files = $this->package_migration_manager->get_migrations_between($key,$starting_version,$composer_config['composer_version']);

			$this->packages[$key]['migrations'] = $migration_files;
			$this->packages[$key]['has_migrations'] = (count($migration_files) > 0);

			/* update packages that don't have migrations */
			if ($this->packages[$key]['has_migrations'] == false && $this->packages[$key]['version_check'] == 3) {
				/* no migrations - just update the veresion since the code is already up to date */
				$this->packages[$key]['migration_version'] = $this->packages[$key]['composer_version'];
				$this->packages[$key]['version_check'] = 2;

				$this->o_packages_model->write_new_version($this->packages[$key]['full_path'],$this->packages[$key]['composer_version']);
			}
		}
	}

	public function buttons(&$packages) {
		/* save this for later to add errors */
		$this->packages = &$packages;

		/*
		finally calculate the buttons & version display for the view
		Should this be done in the first loop?
		not sure since package_requirements requires the complete array to do it's logic checks?
		*/

		foreach ($this->packages as $key=>$config) {
			/* calc which buttons to show so it's not done in the view (where it doesn't belong) */
			if (!$config['has_error']) {
				$config['button']['install'] = (!$config['is_active']);
				$config['button']['upgrade'] = ($config['version_check'] == 3 && $config['is_active']);
				$config['button']['uninstall'] = ($config['is_active'] && !$config['is_required']);

				if ($this->allow_delete) {
					$config['button']['delete'] = (!$config['is_active'] && !$config['is_required']);
				} else {
					$config['button']['delete'] = false;
				}
			} else {
				$config['button']['info'] = true;
			}

			/* calc version display */
			$config['version_display'] = 0;

			if (!$config['json_error']) {
				if ($config['is_active']) {
					switch ($config['version_check']) {
						case 1: /* less than */
							$config['version_display'] = 4;
							$config['show_version'] = true;
						break;
						case 2:
							$config['version_display'] = 2;
							/* version in db matches migration version */
						break;
						case 3: /* greater than */
							$config['version_display'] = 3;
							$config['uninstall'] = false;
							$config['upgrade'] = true;
						break;
						default:
							$config['version_display'] = 4;
					}
				}
			}

			/* ok now put this in the array as well! */
			$this->packages[$key] = $config;
		}
	}
} /* end class */