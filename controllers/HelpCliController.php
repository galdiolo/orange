<?php

class helpCliController extends O_CliController {

	public function indexCliAction() {
		/* find all info.json */
		$infos = $this->rglob(ROOTPATH.'/packages','info.json');
		$cli = '';

		foreach ($infos as $i) {
			$json = json_decode(file_get_contents($i));

			if ($json !== null) {
				if (isset($json->cli)) {
					$entries = (array)$json->cli;
					foreach ($entries as $k=>$c) {
						$cli .= '<yellow>'.$k.chr(10).'<white>  '.$c.chr(10).chr(10);
					}
				}
			}
		}

		$this->output($cli);
	}

	protected function rglob($path='',$pattern='*',$flags=0) {
		$paths = glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
		$files = glob($path.$pattern, $flags);

		foreach ($paths as $path) {
			$files = array_merge($files,$this->rglob($path, $pattern, $flags));
		}

		return $files;
	}

} /* end class */