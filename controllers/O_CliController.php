<?php

class O_CliController extends MY_Controller {

	/* safety check - this can only be called via command line */
	public function __construct() {
		parent::__construct();

		if (php_sapi_name() !== 'cli') {
			show_404();
			exit;
		}
	}

	public function indexCliAction() {
		$class = get_called_class();

		//$methods = get_class_methods($class);

		$this->output($class);

		$match = str_replace('CliController','',$class);

		$matching = $this->_get_matching($match);

		$this->output('<blue>Available Methods:');

		foreach ($matching as $command=>$desc) {
			$this->output('<yellow>'.str_replace($match.' ','',$command));
			$this->output('  <white>'.$desc);
		}
	}

	protected function output($text='',$die=false) {
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

		if ($die) {
			die();
		}
	}

	protected function input($prompt='') {
		echo $prompt;

		return rtrim( fgets( STDIN ),chr(10));
	}

	protected function _rglob($path='',$pattern='*',$flags=0) {
		$paths = glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
		$files = glob($path.$pattern, $flags);

		foreach ($paths as $path) {
			$files = array_merge($files,$this->_rglob($path, $pattern, $flags));
		}

		return $files;
	}

	public function _get_matching($match) {
		/* find all info.json */
		$infos = $this->_rglob(ROOTPATH.'/packages','info.json');
		$matches = [];

		foreach ($infos as $i) {
			$json = json_decode(file_get_contents($i));

			if ($json !== null) {
				if (isset($json->cli)) {
					$entries = (array)$json->cli;
					foreach ($entries as $k=>$c) {
						$parts = explode(' ',$k);
						if ($parts[0] == $match) {
							$matches[$k] = $c;
						}
					}
				}
			}
		}

		return $matches;
	}


} /* end class */