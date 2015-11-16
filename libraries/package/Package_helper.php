<?php

class package_helper {
	protected $packages;
	protected $package_migration_manager;
	protected $o_packages_model;
	protected $active_packages;
	protected $available_packages;
	protected $sudo_packages = ['php'];

	public function __construct() {
		$this->package_migration_manager = &ci()->package_migration_manager;
		$this->o_packages_model = &ci()->o_packages_model;
	}

	public function migrations(&$packages) {
		/* save this for later to add errors */
		$this->packages = &$packages;

		foreach ($this->packages as $key=>$package) {
			$this->packages[$key]['version_check'] = $this->package_migration_manager->version_check($package['database']['migration_version'],$package['composer']['orange']['version']);

			/* starting version for migrations */
			$starting_version = ($package['database']['migration_version']) ? $package['database']['migration_version'] : '0.0.0';

			$migration_files = $this->package_migration_manager->get_migrations_between($key,$starting_version,$package['composer']['orange']['version']);

			$this->packages[$key]['migrations']['files'] = $migration_files;
			$this->packages[$key]['migrations']['has_migrations'] = (count($migration_files) > 0);

			/* update packages that don't have migrations */
			if ($this->packages[$key]['migrations']['has_migrations'] == false && $this->packages[$key]['version_check'] == 3) {
				/* no migrations - just update the veresion since the code is already up to date */
				$this->packages[$key]['migrations']['migration_version'] = $this->packages[$key]['composer']['orange']['version'];
				$this->packages[$key]['migrations']['version_check'] = 2;

				$this->o_packages_model->version($key,$this->packages[$key]['composer']['orange']['version']);
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

		foreach ($this->packages as $key=>$package) {
			$errors = false;

			$this->packages[$key]['buttons']['deactivate'] = false;
			$this->packages[$key]['buttons']['activate'] = false;
			$this->packages[$key]['buttons']['upgrade'] = false;
			$this->packages[$key]['buttons']['uninstall'] = false;

			if ($package['database']['is_active']) {
				$this->packages[$key]['www_name'] = '<strong>'.htmlentities($package['composer']['name']).'</strong>';
			} else {
				$this->packages[$key]['www_name'] = htmlentities($package['composer']['name']);
			}

			if (isset($package['composer']['orange'])) {

				/*
				show activate / deactivate

				activate will add to onload and autoload and run all migrations up to the latest as needed
				deactivate removes from onload and autoload but does NOT run migrations down (uninstall does that)
				*/
				if ($package['is_active']) {
					$this->packages[$key]['buttons']['deactivate'] = true;
				} else {
					$this->packages[$key]['buttons']['activate'] = true;
				}

				/*
				show upgrade

				this will run all migrations up to the listed orange version
				*/
				if ($package['migrations']['has_migrations'] && $package['is_active']) {
					$this->packages[$key]['buttons']['upgrade'] = true;
				}

				/*
				show uninstall

				This will run all migrations down to 0
				*/
				if ($package['is_active'] != '1') {
					$this->packages[$key]['buttons']['uninstall'] = true;
				}

				/* is this loaded? if it isn't then they can't unload it */
				if ($package['database']['is_installed'] != '1') {
					$this->packages[$key]['buttons']['uninstall'] = false;
				}

				/* is this package required by anyone? */
				if (count($package['is_required_by']) > 0) {
					$this->packages[$key]['buttons']['deactivate'] = false;
					$this->packages[$key]['buttons']['uninstall'] = false;
					$this->packages[$key]['buttons']['error'] = true;
				}

				if (count($package['package_not_active']) > 0) {
					$this->packages[$key]['buttons']['activate'] = false;
					$this->packages[$key]['buttons']['error'] = true;
				}

				if (count($package['package_not_available']) > 0) {
					$this->packages[$key]['buttons']['activate'] = false;
					$this->packages[$key]['buttons']['error'] = true;
				}

			}

		}
	}

	/* find if a package is require so they don't deactivate it */
	public function requirements(&$packages) {
		/* save this for later to add errors */
		$this->packages = &$packages;

		/* first convert array from full path keys to composer namespace keys */
		foreach ($this->packages as $key=>$package) {
			$this->available_packages[$package['composer']['name']] = $package;

			if ($package['is_active'] || !isset($package['composer']['orange'])) {
				$this->active_packages[$package['composer']['name']] = $package;
			}
		}

		/* ok now loop over the packages to determine the requirements */
		foreach ($this->packages as $key=>$package) {
			if (isset($package['composer']['orange'])) {
				$this->packages[$key]['missing_package'] = false;
				$this->packages[$key]['not_active_package'] = false;

				$this->_test_requirement($key,$package);
			}
		}
	}

	protected function _test_requirement($key,$package) {
		/* does this package have any requirements? */
		if (is_array($package['composer']['require'])) {
			/* yes - ok let's see if they are active */
			foreach ($package['composer']['require'] as $required_package=>$version) {
				
				if ($package['is_active']) {
					$this->_tell_package_it_is_needed($required_package,$package['composer']['name']);
				}

				if (!$this->_test_is_available($required_package)) {
					$this->packages[$key]['package_not_available'][$required_package] = $required_package;
					$this->packages[$key]['missing_package'] = true;
				} elseif (!$this->_test_is_active($required_package)) {
					$this->packages[$key]['package_not_active'][$required_package] = $required_package;
					$this->packages[$key]['not_active_package'] = true;
				}

			}
		}
	}

	protected function _tell_package_it_is_needed($namespace,$required_by) {
		foreach ($this->packages as $key=>$package) {
			if ($package['composer']['name'] == $namespace) {
				$this->packages[$key]['is_required_by'][$required_by] = $required_by;

				return true;
			}
		}
	}

	protected function _test_is_active($key) {
		if (in_array($key,$this->sudo_packages)) {
			return true;
		}

		return array_key_exists($key,$this->active_packages);
	}

	protected function _test_is_available($key) {
		if (in_array($key,$this->sudo_packages)) {
			return true;
		}

		return array_key_exists($key,$this->available_packages);
	}


} /* end class */