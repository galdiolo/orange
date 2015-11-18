<?php
/*
CREATE TABLE `orange_packages` (
	`full_path` varchar(255) NOT NULL,
	`migration_version` varchar(16) DEFAULT NULL,
	`is_active` tinyint(1) unsigned DEFAULT '1',
	`priority` tinyint(4) NOT NULL DEFAULT '50',
	PRIMARY KEY (`full_path`),
	UNIQUE KEY `idx_folder_name` (`full_path`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
*/
class o_packages_model extends Database_model {
	protected $table = 'orange_packages';
	public $primary_key = 'full_path';

	public function activate($key,$is_active) {
		return $this->_database->update($this->table,['is_active'=>(int)$is_active],[$this->primary_key=>$key]);
	}

	public function version($key,$version) {
		return $this->_database->update($this->table,['migration_version'=>$version],[$this->primary_key=>$key]);
	}

	public function priority($key,$priority) {
		return $this->_database->update($this->table,['priority'=>$priority],[$this->primary_key=>$key]);
	}

	public function add($key,$migration_version,$is_active,$priority=50) {
		$migration_version = (!empty($migration_version)) ? $migration_version : '0.0.0';
		$is_active = ($is_active) ? 1 : 0;
		$is_loaded = ($is_loaded) ? 1 : 0;
		$priority = (!empty($priority)) ? (int)$priority : 50;

		return $this->_database->replace($this->table,[$this->primary_key=>$key,'migration_version'=>$migration_version,'is_active'=>$is_active,'priority'=>$priority]);
	}

	/* get active in order */
	public function active() {
		return $this->_database->order_by('priority','asc')->where(['is_active'=>1])->get($this->table)->result();
	}

} /* end class */