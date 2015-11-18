<?php

class package_migration_manager {
	public function get_migrations_between($package) {
		$folder = $package['key'];

		/* start at the database specified version */
		$start_ver = (!empty($package['database']['migration_version'])) ? $package['database']['migration_version'] : '0.0.0';

		/* end at the package specified version */
		$end_ver = (!empty($package['composer']['orange']['version'])) ? $package['composer']['orange']['version'] : '999.999.999';

		return $this->get_files($folder,$start_ver,$end_ver);
	}
	
	public function get_migrations_uninstall($package) {
		$folder = $package['key'];
		
		/* back to not installed */
		$start_ver = '0.0.0';

		/* end at the package specified version */
		$end_ver = (!empty($package['database']['migration_version'])) ? $package['database']['migration_version'] : '0.0.0';

		$migration_files = $this->get_files($folder,$start_ver,$end_ver);
		
		/* flip them for uninstall */
		$migration_files = array_reverse($migration_files,true);

		return $migration_files;
	}

	protected function get_files($folder,$start_ver,$end_ver) {
		$migration_array = [];

		/* do both version match? if so then we are up to date */
		if ($start_ver == $end_ver) {
			return $migration_array;
		}

		$migration_folder = ROOTPATH.'/'.trim($folder,'/').'/support/migrations';

		/* is it there? */
		if (is_dir($migration_folder)) {
			log_message('debug','Migration Folder '.$migration_folder);

			log_message('debug','Migration Between '.$start_ver.' '.$end_ver);

			/* migrations start with v ie. v1.0.0-name_of_migration.php */
			$migrations = glob($migration_folder.'/v*.php');

			/* loop over the migration files */
			foreach ($migrations as $migration) {
				$filename = basename($migration);

				/* split it into something useable */
				list($migration_file_version) = explode('-',str_replace('v','',$filename));

				if ($this->between_version($migration_file_version,$start_ver,$end_ver)) {
					log_message('debug','Migration File version '.$migration_file_version.' matches');

					$migration_array[] = $migration;
				} else {
					log_message('debug','Migration File version '.$migration_file_version.' does not match');
				}
			}

			log_message('debug','Migration Found '.count($migration_array));
		}

		return $migration_array;
	}

	public function between_version($version,$start_ver,$end_ver) {
		if ($version == $end_ver) {
			return true;
		}

		if (version_compare($version,$start_ver,'>')) {
			if (version_compare($version,$end_ver,'<')) {
				return true;
			}
		}

		return false;
	}

	public function version_check($current_version,$must_match) {
		/*
		1 = less than
		2 = exact
		3 = greater than
		4 = error
		*/
		$must_match = str_replace('*','0',$must_match);

		if (empty($current_version) || empty($must_match)) {
			return 4;
		}

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

	public function run_migrations_up($package) {
		return $this->run_migrations($package['migrations']['files'],'up');
	}

	public function run_migrations_down($package) {
		return $this->run_migrations($package['migrations']['uninstall'],'down');
	}

	protected function run_migrations($migration_files,$dir) {
		log_message('debug', 'run migrations '.$dir);

		$success = true;

		if (is_array($migration_files)) {
			foreach ($migration_files as $migration_file) {
				$migration_filename = basename($migration_file,'.php');

				log_message('debug','Running Migration File '.$migration_filename);

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