<?php
/*
CREATE TABLE `orange_packages` (
  `full_path` varchar(255) DEFAULT NULL,
  `migration_version` varchar(16) DEFAULT NULL,
  `is_active` tinyint(1) unsigned DEFAULT '1',
  `package_priority` tinyint(1) unsigned DEFAULT '0',
  `priority` tinyint(4) NOT NULL DEFAULT '50',
  `priority_overridden` tinyint(1) unsigned DEFAULT '0',
  UNIQUE KEY `idx_full_path` (`full_path`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
*/
class o_packages_model extends Database_model {
	protected $table = 'orange_packages';
	public $primary_key = 'full_path';

	public function activate($key,$is_active) {
		return $this->_database->update($this->table,['is_active'=>(int)$is_active],[$this->primary_key=>$key]);
	}

	public function write($key,$migration_version,$is_active,$priority=50) {
		$migration_version = (!empty($migration_version)) ? $migration_version : '0.0.0';
		$is_active = ($is_active) ? 1 : 0;
		$priority = (!empty($priority)) ? (int)$priority : 50;
	
		return $this->_database->replace($this->table,[$this->primary_key=>$key,'migration_version'=>$migration_version,'is_active'=>$is_active,'priority'=>$priority]);
	}
	
	/* update a 
	public function write_new_version($key,$migration_version) {
		return $this->_database->update($this->table,['migration_version'=>$migration_version],[$this->primary_key=>$key]);
	}

	/* get active in order for autoload / onload */
	public function active() {
		return $this->_database->order_by('priority','asc')->where(['is_active'=>1])->get($this->table)->result();
	}

} /* end class */