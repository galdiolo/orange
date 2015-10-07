<?php

class toolsController extends O_CliController {

	public function migrationCliAction($mode='latest') {
		$this->load->library('migration');

		if ($mode == 'latest' || $mode == 'current') {
			if ($this->migration->$mode() === FALSE) {
				show_error($this->migration->error_string());
			} else {
				$this->output('<green>Complete');
			}
		} else {
			$this->output('<red>Error');
		}
	}

	public function package_updateCliAction($package=null) {
		$this->load->library('package_manager');

		if (!$package) {
			$this->output('<blue>Please specify a package.');

			include ROOTPATH.'/application/config/autoload.php';

			foreach ($autoload['packages'] as $p) {
				$this->output('<yellow>'.basename($p,'.php'));
			}
		} else {
			$this->output(($this->package_manager->install_or_upgrade($package)) ? '<green>Complete' : '<red>Error');
		}
	}

	public function clear_cacheCliAction() {
		$success = $this->cache->clean();

		$this->output(($success == true) ? '<green>Complete' : '<red>Error');
	}

	public function cache_infoCliAction() {
		$this->output('<blue>Default: <yellow>'.ci()->config->item('cache_default'));
		$this->output('<blue>Backup: <yellow>'.ci()->config->item('cache_backup'));
		$this->output('<blue>Cache TTL: <yellow>'.ci()->config->item('cache_ttl'));

		var_dump($this->cache->cache_info());
	}

} /* end class */