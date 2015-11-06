<?php

class package_requirements {
	protected $packages;
	protected $composer;
	protected $key;

	public function process(&$packages) {
		/* make local ref */
		$this->packages = &$packages;
		
		/* load composer json */
		$composer_json = json_decode(file_get_contents(ROOTPATH.'/composer.json'));
		
		/* get the required objects from composer */
		$this->composer = (array)$composer_json->require;
		
		/* test the packages */
		foreach ($packages as $key=>$package) {
			/* active record index */
			$this->key = $key;
		
			/* each package starts with no errors */
			$this->packages[$this->key]['has_errors'] = false;
			
			/* each package starts with not being required */
			$this->packages[$this->key]['is_required'] = false;

			//$this->check_composer($package);
			$this->check_packages($package);
			$this->allow_uninstall($package);
		}
	}

	public function check_composer($package) {
		$composer_requirements = $package['requires-composer'];
		
		/* we need the internal folder name? !todo make namespaced path */
		$folder = $package['folder'];

		foreach ($composer_requirements as $name=>$looking_for_version) {

			if (!array_key_exists($name,$this->composer)) {
				$this->add_issue('composer_error','Required composer package "'.$name.' v'.$looking_for_version.'" is not loaded.');
				$this->add_issue('composer_error_raw',$name.' v'.$looking_for_version);
			} else {

				/*
				if composer is "any" version then I guess they don't care?
				not the best thing but we have no idea of the version so there
				isn't much we can do.
				*/
				if ($composer_version == '*' || $looking_for_version == '*') {
					return true;
				}

				if (!ci()->package_migration_manager->version_in_range($composer_version,$looking_for_version)) {
					$this->add_issue('composer_error','Required composer package "'.$name.' v'.$looking_for_version.'" is not loaded.');
					$this->add_issue('composer_error_raw',$name.' v'.$looking_for_version.' - found v'.$composer_version);
				}
			}
		}

		return true;
	}

	public function check_packages($package) {
		$package_requirements = $package['requires'];

		$folder = $package['folder'];

		foreach ($package_requirements as $name=>$looking_for_version) {
			if ($this->packages[$this->key]['is_active'] != true) {
				$this->add_issue('package_error','Required package "'.$name.' v'.$looking_for_version.'" is not loaded.');
				$this->add_issue('package_error_raw',$name.' v'.$looking_for_version);
			} else {
				$package_version = $this->packages[$this->key]['version'];

				if (!ci()->package_migration_manager->version_in_range($package_version,$looking_for_version)) {
					$this->add_issue('package_error','Required package "'.$name.' v'.$looking_for_version.'" is not loaded.');
					$this->add_issue('package_error_raw',$name.' v'.$looking_for_version.' - found v'.$package_version);
				}
			}
		}

		return true;
	}

	/* Does any other package require this packages? */
	public function allow_uninstall($package) {
		/* get the current package internal name (folder name) */
		$folder_name = $package['folder'];

		/* now loop over all the packages to determine if it is required */
		foreach ($this->packages as $package) {
			/* is the package active? */
			if ($package['is_active'] && is_array($package['requires'])) {
				if (array_key_exists($folder_name,$package['requires'])) {
					$this->add_issue('required_error','This package is required by "'.$folder_name.'" package');
					$this->add_issue('required_error_raw',$folder_name);

					$this->packages[$this->key]['is_required'] = true;
				}
			}
		}
	}

	public function add_issue($key,$msg) {
		$this->packages[$this->key][$key][$msg] = $msg;
		
		/* now this package has a issue */
		$this->packages[$this->key]['has_errors'] = true;
	}

} /* end class */