<?php

class cacheCliController extends O_CliController {

	public function clearCliAction() {
		$success = $this->cache->clean();
		
		$local_files = glob(ROOTPATH.'/var/local_file_cache/*');

		foreach ($local_files as $lf) {
			@unlink($lf);
		}

		$this->output(($success == true) ? '<green>Complete' : '<red>Error');
	}

	public function infoCliAction() {
		$this->output('<blue>Default: <yellow>'.ci()->config->item('cache_default'));
		$this->output('<blue>Backup: <yellow>'.ci()->config->item('cache_backup'));
		$this->output('<blue>Cache TTL: <yellow>'.ci()->config->item('cache_ttl'));
		
		$this->output('<yellow>CI Cache');
		var_dump($this->cache->cache_info());

		$this->output('<yellow>Local Array File Cache');
		$local_files = glob(ROOTPATH.'/var/local_file_cache/*');
		
		foreach ($local_files as $lf) {
			echo $lf.chr(10);		
		}
	}

} /* end class */