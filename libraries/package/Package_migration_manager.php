<?php

class package_migration_manager {

	public function get_migrations_between($package,$start_ver='0.0.0',$end_ver='999.999.999') {
		$migration_array = [];
		$migration_folder = ROOTPATH.'/'.trim($package,'/').'/support/migrations';

		$start_ver = ($start_ver) ? $start_ver : '0.0.0';
		$end_ver = ($end_ver) ? $end_ver : '999.999.999';

		/* is it there? */
		if (is_dir($migration_folder)) {
			/* migrations start with v ie. v1.0.0-name_of_migration.php */
			$migrations = glob($migration_folder.'/v*.php');

			/* loop over the migration files */
			foreach ($migrations as $migration) {
				$filename = basename($migration);

				/* split it into something useable */
				list($migration_file_version) = explode('-',str_replace('v','',$filename));

				if ($this->between_version($migration_file_version,$start_ver,$end_ver)) {
					$migration_array[] = $migration;
				}
			}
		}

		return $migration_array;
	}

	public function between_version($version,$start_version,$end_version) {
		if (version_compare($version,$end_version,'<') || version_compare($version,$start_version,'=>')) {
			return true;
		}

		return false;
	}

	public function version_check($current_version,$must_match) {
		/*
		1 = less than
		2 = exact
		3 = greater than
		*/
		$must_match = str_replace('*','0',$must_match);

		if (version_compare($must_match,$current_version,'=')) {
			return 2;
		}

		if (version_compare($must_match,$current_version,'<')) {
			return 1;
		}

		if (version_compare($must_match,$current_version,'>')) {
			return 3;
		}

		return false;
	}

	public function version_in_range($current_version,$range) {
		$range = ($range == '*') ? '*.*.*' : $range;

		$regex = str_replace(['.', '*'], ['\.', '(\d+)'], '/^'.$range.'/');

		$bol = (bool)(preg_match($regex, $current_version));

		return $bol;
	}

	public function run_migrations($config,$dir) {
		log_message('debug', 'run migrations '.$dir.' '.$config['key'].' '.$config['composer_version']);

		if (empty($config['key'])) {
			show_error('Key Not Found');
		}

		if (empty($config['composer']['orange']['version'])) {
			show_error('Orange Version Empty');
		}

		if ($dir != 'up' && $dir != 'down') {
			show_error('debug', 'migrations direction '.$dir.' not valid.');
		}

		switch ($dir) {
			case 'up':
				/* if it's down then we need a complete set of migrations */
				$migration_files = $this->get_migrations_between($config['key'],$config['composer_version'],'999.999.999');
			break;
			case 'down':
				/* if it's down then we need a complete set of migrations */
				$migration_files = $this->get_migrations_between($config['key'],'0.0.0',$config['composer_version']);

				/* ok now run it backwards */
				$migration_files = array_reverse($migration_files,true);
			break;
		}

		log_message('debug','Found '.count($migration_files).' migration files');

		$success = true;

		if (is_array($migration_files)) {
			foreach ($migration_files as $migration_file) {
				$migration_filename = basename($migration_file,'.php');

				$class_name = str_replace(['.','-'],['','_'],$migration_filename);

				include $migration_file;

				if (!class_exists($class_name,false)) {
					show_error('Error: migration class named "'.$class_name.'" not found in "'.$migration_file.'"');
				}

				$migration = new $class_name($config);

				if (method_exists($migration,$dir)) {
					log_message('debug', 'migrations running '.$class_name.'::'.$dir);

					$success = $migration->$dir();
				} else {
					show_error('migrations could not find '.$class_name.'::'.$dir);
				}

				if ($success !== true) {
					break;
				}
			}
		}

		return $success;
	}

} /* end class */