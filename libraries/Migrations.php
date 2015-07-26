<?php

class Migrations extends CI_Migration {

	public function __construct($config = array()) {
		$config = array_merge(setting('migration'),$config);
		
		/* it only runs the setup if CI_Migration is called directly not extended */
		foreach ($config as $key => $val) {
			$this->{'_'.$key} = $val;
		}

		log_message('info', 'Migrations Class Initialized');

		// Are they trying to use migrations while it is disabled?
		if ($this->_migration_enabled !== TRUE) {
			show_error('Migrations has been loaded but is disabled or set up incorrectly.');
		}

		// If not set, set it
		$this->_migration_path !== '' OR $this->_migration_path = APPPATH.'migrations/';

		// Add trailing slash if not set
		$this->_migration_path = rtrim($this->_migration_path, '/').'/';

		// Load migration language
		$this->lang->load('migration');

		// They'll probably be using dbforge
		$this->load->dbforge();

		// Make sure the migration table name was set.
		if (empty($this->_migration_table)) {
			show_error('Migrations configuration file (migration.php) must have "migration_table" set.');
		}

		// Migration basename regex
		$this->_migration_regex = ($this->_migration_type === 'timestamp') ? '/^\d{14}_(\w+)$/' : '/^\d{3}_(\w+)$/';

		// Make sure a valid migration numbering type was set.
		if ( ! in_array($this->_migration_type, array('sequential', 'timestamp'))) {
			show_error('An invalid migration numbering type was specified: '.$this->_migration_type);
		}

		// If the migrations table is missing, make it
		if ( ! $this->db->table_exists($this->_migration_table)) {
			$this->dbforge->add_field(array(
				'version' => array('type' => 'BIGINT', 'constraint' => 20),
			));

			$this->dbforge->create_table($this->_migration_table, TRUE);

			$this->db->insert($this->_migration_table, array('version' => 0));
		}

		// Do we auto migrate to the latest migration?
		if ($this->_migration_auto_latest === TRUE && ! $this->latest()) {
			show_error($this->error_string());
		}
	}

	/**
	* Retrieves list of available migration scripts
	*
	* @return	array	list of migration file paths sorted by version
	*
	* -- bring in from the packages as well
	*/
	public function find_migrations() {
		$migrations = array();

		$package_folders = $this->load->get_package_paths();

		/* add the default to the beginning of the array */
		array_unshift($package_folders,$this->_migration_path);

		foreach ($package_folders as $mfolder) {
			$level1 = glob($mfolder.'*_*.php');
			$level2 = glob($mfolder.'/support/migrations/*_*.php');
			
			$merged = array_merge($level1,$level2);
		
			// Load all *_*.php files in the migrations path
			foreach ($merged as $file) {
				$name = basename($file, '.php');

				// Filter out non-migration files
				if (preg_match($this->_migration_regex, $name)) {
					$number = $this->_get_migration_number($name);

					// There cannot be duplicate migration numbers
					if (isset($migrations[$number])) {
						$this->_error_string = sprintf($this->lang->line('migration_multiple_version'), $number);
						show_error($this->_error_string);
					}

					$migrations[$number] = $file;
				}
			}
		}

		ksort($migrations);

		return $migrations;
	}

} /* end MY_Migration */