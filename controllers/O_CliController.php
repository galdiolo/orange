<?php

class O_CliController extends MY_Controller {

	/* safety check - this can only be called via command line */
	public function __construct() {
		parent::__construct();

		if (php_sapi_name() !== 'cli') {
			show_error('This controller can only be called from the command line.',404);
			exit;
		}
	}

	public function indexCliAction() {
		$methods = get_class_methods(get_called_class());

		$this->output('<blue>Available Methods:');

		foreach ($methods as $name) {
			if (substr($name,-9) == 'CliAction' && $name{0} != '_' && $name != 'indexCliAction') {
				$name = str_replace('CliAction','',$name);
				
				$this->output('<yellow>'.$name);
			}
		}
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

	protected function input($prompt='') {
		echo $prompt;

		return rtrim( fgets( STDIN ),chr(10));
	}

} /* end class */