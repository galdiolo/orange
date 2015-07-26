<?php

class cli_utilitiesController extends APP_PublicController {

	public function indexCliAction() {
		$methods = get_class_methods(get_class());

		$skip = ['index','__','get'];

		echo 'Methods:'.chr(10);

		foreach ($methods as $m) {
			$name = substr($m,0,-9);
			if (!in_array($name,$skip)) {
				echo $name.chr(10);
			}
		}

		echo chr(10);
	}

	public function migrationCliAction() {
		require BASEPATH.'libraries/Migration.php';

		$this->load->library('migrations');

		$array = $this->migrations->find_migrations();

		echo '<pre>';
		var_dump($array);

	}

	public function activate_packageCliAction($package=null) {
		$this->load->library('package_manager');

		echo $this->package_manager->install_or_upgrade($package);
	}

} /* end class */