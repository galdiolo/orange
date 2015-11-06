<?php

class package_requirements {
	protected $key;
	protected $packages;

	public function process(&$packages) {
		$this->packages = &$packages;

		foreach ($this->packages as $key=>$package) {
			$this->key = $key;

			$this->packages[$this->key]['errors'] = '';
			$this->packages[$this->key]['errors_raw'] = [];
			$this->packages[$this->key]['has_error'] = false;

			$this->test($package);	
		}
	}

	public function test($package) {
		$name = $package['name'];
	
		foreach ($this->packages as $p) {
			if (is_array($p['require'])) {
				if (array_key_exists($name,$p['require'])) {
					$this->packages[$this->key]['required_errors'][] = $p['name'].' <small>'.$p['require'][$name].'</small>';
					$this->packages[$this->key]['required_errors_raw'][] = [$name=>$p['name']];
					$this->packages[$this->key]['has_error'] = true;
				}
			}
		}

	}

} /* end class */