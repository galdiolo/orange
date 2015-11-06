<?php

class toolsCliController extends O_CliController {

	public function siteCliAction($mode=null) {
		$mode = strtolower($mode);

		if ($mode != 'up' && $mode != 'down') {
			$this->output('<red>Please provide <green>up<off> or <green>down<off>.');
			die();
		}

		$value = ($mode == 'up') ? 'true' : 'false';
	
		$success = $this->o_setting_model->update_by(['name'=>'Site Open','group'=>'application'],['value'=>$value],'update_value');

		$msg = ($success) ? '<green>Site status updated.' : '<red>Error changing site status.';
	
		$this->output($msg);
	}
	
	public function clearCliAction($what=null) {
		$what = strtolower($what);
		$one_of = ['logs','sessions','upload_temp','cache','local_file_cache'];

		if (!in_array($what,$one_of)) {
			$this->output('<red>Please provide one of:');

			foreach ($one_of as $of) {
				$this->output($of);
			}

			die();
		}

		$local_files = glob(ROOTPATH.'/var/'.$what.'/*');

		foreach ($local_files as $lf) {
			$success = @unlink($lf);
		}

		$this->output(($success == true) ? '<green>Complete' : '<red>Error');
	}
	
	/* update onload */
	
	/* update autoload */
	public function _update_todo($package=null) {
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

} /* end class */