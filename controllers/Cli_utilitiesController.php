<?php

class cli_utilitiesController extends MY_Controller {

	public function indexCliAction() {
		$methods = get_class_methods(get_class());

		$skip = ['index','__','get'];

		$this->output('<blue>Methods:');

		foreach ($methods as $m) {
			$name = substr($m,0,-9);
			if (!in_array($name,$skip)) {
				$this->output('<yellow>'.$name);
			}
		}

		$this->output();
	}

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
	
	protected function output($text='') {
		$ansi_codes = [
			"off"        => 0,
			"bold"       => 1,
			"italic"     => 3,
			"underline"  => 4,
			"blink"      => 5,
			"inverse"    => 7,
			"hidden"     => 8,
			"black"      => 30,
			"red"        => 31,
			"green"      => 32,
			"yellow"     => 33,
			"blue"       => 34,
			"magenta"    => 35,
			"cyan"       => 36,
			"white"      => 37,
			"black_bg"   => 40,
			"red_bg"     => 41,
			"green_bg"   => 42,
			"yellow_bg"  => 43,
			"blue_bg"    => 44,
			"magenta_bg" => 45,
			"cyan_bg"    => 46,
			"white_bg"   => 47
		];
    
		foreach ($ansi_codes as $color=>$val) {
			$text = str_replace('<'.$color.'>',"\033[".$val."m",$text);
		}
		
		echo $text."\033[0m".chr(10);
	}

} /* end class */