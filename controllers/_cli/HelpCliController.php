<?php

class helpCliController extends O_CliController {

	public function indexCliAction() {
		$this->load->library('package_manager');
		
		$packages = $this->package_manager->prepare();

		$cli = '';

		foreach ($packages as $package) {
			if (isset($package['composer']['orange']['cli'])) {

				$entries = (array)$package['composer']['orange']['cli'];

				foreach ($entries as $k=>$c) {
					$cli .= '<yellow>'.$k.' - <white>'.$c.chr(10).chr(10);
				}
			}
		}

		$this->output($cli);
	}

} /* end class */