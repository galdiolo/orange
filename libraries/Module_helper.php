<?php

class module_helper {
	public $parent;

	public function init(&$parent) {
		$this->parent = $parent;
	
		return $this;
	}

	public function normalize($text) {
		/* normalize source code */

		/* trim any beginning spaces or trailing spaces */
		$text = trim($text);

		/* remove runs of spaces or tabs */
		$text = preg_replace('/[ \t]+/',' ',$text);

		/* Removes multi-line comments and does not create a blank line, also treats white spaces/tabs */
		$text = preg_replace('!\/\*[\s\S]*?\*\/!s', '', $text);

		/* Removes single line '//' comments, treats blank characters */
		$text = preg_replace('![ \t]*//.*[ \t]*[\r\n]!', '', $text);

		/* Strip blank lines */
		$text = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $text);

		/* normalize line endings */
		$text = str_replace(chr(13).chr(10),chr(10),$text);

		/* remove spaces at the begining of the lines */
		$text = str_replace([chr(32).chr(10),chr(10).chr(32)],chr(10),$text);

		return $text;
	}

	public function version_in_range($current_version,$range) {
		$regex = str_replace(['.', '*'], ['\.', '(\d+)'], '/^'.$range.'/');

		return (bool)(preg_match($regex, $current_version));
	}
	
	/* get from database */
	public function get_model_details($folder_name,$human_name,$current_version) {
		$results = ci()->db->where(['folder_name'=>$folder_name])->get($this->parent->migration_table);

		if ($results->num_rows()) {
			$record = $results->result()[0];
		} else {
			$record = $this->parent->default_record;

			$record['human_name'] = $human_name;
			$record['folder_name'] = $folder_name;
			$record['current_version'] = $current_version;

			/* create the record */
			$this->set_model_details($record['migration_version'],$record['human_name'],$record['folder_name'],$record['current_version']);
		}

		return $record;
	}

	/* set in database */
	public function set_model_details($migration_version,$human_name,$folder_name,$current_version) {
		$data = [
			'folder_name'=>$folder_name,
			'human_name'=>$human_name,
			'migration_version'=>$migration_version,
			'current_version'=>$current_version,
		];

		return ci()->db->replace($this->parent->migration_table,$data);
	}
	
	public function config_magic($filename) {
		$content = file_get_contents($filename);
		$content = $this->normalize($content); /* flatten this thing out */
		$content = str_replace('public $','$',$content); /* convert class parameters to regular vars */

		$lines = explode(chr(10),$content);

		array_shift($lines); /* shift off <?php */
		array_shift($lines); /* shift off class... */

		$parameters = '';

		foreach ($lines as $line) {
			if (substr($line,0,9) == 'function ' || substr($line,0,7) == 'public ' || substr($line,0,1) == '}') {
				break;
			}

			$parameters .= $line.chr(10);
		}

		$map = 'name,version,info,help,type,install,uninstall,upgrade,remove,onload,autoload,requires,theme,table,notes,requires_composer';
		$map = explode(',',$map);

		foreach ($map as $n) {
			$array .= '"'.$n.'"=>$'.$n.',';
		}

		$parameters .= 'return ['.$array.'];';

		$func = create_function('',$parameters);

		$config = $func();

		$config['installer'] = $filename;
		$config['filename'] = basename($filename,'.php');
		$config['classname'] = substr(basename($filename,'.php'),8);
		$config['is_active'] = in_array($config['classname'],$this->parent->active_modules);
		
		$model_record = $this->get_model_details($config['classname'],$config['name'],$config['version']);

		$config['db_folder_name'] = $model_record->folder_name;
		$config['db_human_name'] = $model_record->human_name;
		$config['db_migration_version'] = $model_record->migration_version;
		$config['db_current_version'] = $model_record->current_version;
		
		$this->tests($config);

		return $config;
	}

	public function tests(&$config) {
		/* does this have any migrations? */
		$folder = dirname($config['installer']).'/support/migrations';
		
		$current_version = $config['version'];
		$last_upgrade = $config['db_migration_version'];
		
		$config['version_check'] = $this->version_check($current_version,$last_upgrade);;	
	}

	public function version_check($current_version,$must_match) {
		/*
		1 = less than
		2 = exact
		3 = greater than
		*/

		$must_match = str_replace('*','0',$must_match);

		if (version_compare($must_match,$current_version,'<')) {
			return 1;
		}

		if (version_compare($must_match,$current_version,'=')) {
			return 2;
		}

		if (version_compare($must_match,$current_version,'>')) {
			return 3;
		}

		return false;
	}

} /* end class */