<?php

class package_requirements {
	protected $packages;
	protected $composer;

	public function process(&$packages) {
		$this->packages = &$packages;
		
		/* load composer json */
		$composer_json = json_decode(file_get_contents(ROOTPATH.'/composer.json'));

		$this->composer = (array)$composer_json->require;
		
		/* test the packages */
		foreach ($packages as $package) {
			$this->check_composer($package);
			$this->check_packages($package);
			$this->allow_uninstall($package);
		}
	}

	public function check_composer($package) {
		$composer_requirements = $package['requires-composer'];
		$folder = $package['folder'];

		foreach ($composer_requirements as $name=>$looking_for_version) {

			if (!array_key_exists($name,$this->composer)) {
				$this->add_issue($folder,'composer_error','Required composer package "'.$name.' v'.$looking_for_version.'" is not loaded.');
				$this->add_issue($folder,'composer_error_raw',$name.' v'.$looking_for_version);
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
					$this->add_issue($folder,'composer_error','Required composer package "'.$name.' v'.$looking_for_version.'" is not loaded.');
					$this->add_issue($folder,'composer_error_raw',$name.' v'.$looking_for_version.' - found v'.$composer_version);
				}
			}
		}

		return true;
	}

	public function check_packages($package) {
		$package_requirements = $package['requires'];
		$folder = $package['folder'];

		foreach ($package_requirements as $name=>$looking_for_version) {
			if ($this->packages[$name]['is_active'] != true) {
				$this->add_issue($folder,'package_error','Required package "'.$name.' v'.$looking_for_version.'" is not loaded.');
				$this->add_issue($folder,'package_error_raw',$name.' v'.$looking_for_version);
			} else {
				$package_version = $this->packages[$folder]['version'];

				if (!ci()->package_migration_manager->version_in_range($package_version,$looking_for_version)) {
					$this->add_issue($folder,'package_error','Required package "'.$name.' v'.$looking_for_version.'" is not loaded.');
					$this->add_issue($folder,'package_error_raw',$name.' v'.$looking_for_version.' - found v'.$package_version);
				}
			}
		}

		return true;
	}

	/* Does any other package require this packages? */
	public function allow_uninstall($package) {
		$folder = $package['folder'];

		foreach ($this->packages as $package) {
			if ($package['is_active']) {
				foreach ((array)$package['requires'] as $name=>$version) {
					if ($name == $folder) {
						$this->add_issue($folder,'required_error','This package is required by "'.$package['folder'].'" package');
						$this->add_issue($folder,'required_error_raw',$package['folder']);
		
						break;
					}
				}
			}
		}
	}

	public function add_issue($package_name,$key,$msg) {
		$this->packages[$package_name][$key][] = $msg;
	}

} /* end class */