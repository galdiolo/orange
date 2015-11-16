<?php

class O_CliController extends MY_Controller {
	public $packages;

	/* safety check - this can only be called via command line */
	public function __construct() {
		parent::__construct();

		if (php_sapi_name() !== 'cli') {
			show_404();
			exit;
		}
	}

	public function indexCliAction($json=false) {
		$class = get_called_class();

		$methods = get_class_methods($class);

		$this->output('*** '.str_replace('CliController','',$class));

		foreach ($methods as $command) {
			if (substr($command,-9) == 'CliAction' && $command != 'indexCliAction') {
				$command = str_replace('CliAction','',$command);
				
				if ($json) {
					// "help": "Show detailed help on all command line interface commands exposed in all packages.",
					$this->output('<yellow>"'.str_replace('CliController','',$class).' '.str_replace($match.' ','',$command).'": "",');
				} else {
					$this->output('<yellow>'.str_replace('CliController','',$class).' '.str_replace($match.' ','',$command));
				}
			}
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

} /* end class */