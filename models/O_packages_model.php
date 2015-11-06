<?php
/*
CREATE TABLE `orange_packages` (
  `folder_name` varchar(128) NOT NULL,
  `full_path` varchar(255) DEFAULT NULL,
  `migration_version` varchar(16) DEFAULT NULL,
  `is_active` tinyint(1) unsigned DEFAULT '1',
  `package_priority` tinyint(1) unsigned DEFAULT '0',
  `priority` tinyint(4) NOT NULL DEFAULT '50',
  `priority_overridden` tinyint(1) unsigned DEFAULT '0',
  PRIMARY KEY (`folder_name`),
  UNIQUE KEY `idx_folder_name` (`folder_name`) USING BTREE,
  UNIQUE KEY `idx_full_path` (`full_path`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
*/
class o_packages_model extends Database_model {
	protected $table = 'orange_packages';
	public $primary_key = 'full_path';

	public function activate($key,$is_active) {
		return $this->_database->update($this->table,['is_active'=>(int)$is_active],[$this->primary_key=>$key]);
	}

	public function remove($key) {
		return $this->_database->delete($this->table,[$this->primary_key=>$key]);
	}

	public function read($key) {
		$results = $this->_database->where([$this->primary_key=>$key,'is_active'=>1])->get($this->table);

		return ($results->num_rows()) ? (array)$results->result()[0] : [];
	}

	public function write($key,$migration_version,$is_active,$priority=50) {
		/* let's validate / correct a few things */
		$folder_name = basename(dirname($key));
		$migration_version = (!empty($migration_version)) ? $migration_version : '0.0.0';
		$is_active = ($is_active) ? 1 : 0;
		$priority = (!empty($priority)) ? (int)$priority : 50;
	
		return $this->_database->replace($this->table,[$this->primary_key=>$key,'folder_name'=>$folder_name,'migration_version'=>$migration_version,'is_active'=>$is_active,'package_priority'=>$priority,'priority'=>$priority,'priority_overridden'=>0]);
	}

	public function write_new_version($key,$migration_version) {
		return $this->_database->update($this->table,['migration_version'=>$migration_version],[$this->primary_key=>$key]);
	}

	public function write_new_priority($key,$priority,$overridden=1,$force=false) {
		$reply = true;
		$overridden = ($overridden === true) ? 1 : 0;
		$record = $this->_database->where([$this->primary_key=>$key])->get($this->table); /* raw CI active record */
		$record = $record->result()[0]; /* get the single record */

		$priority_overridden = (int)$record->priority_overridden;

		if ($overridden == 1) {
			$priority_overridden = 1;
		}

		/* if the package priority and sent in priority are the same then override is off */
		if ((int)$priority == (int)$record->package_priority) {
			$priority_overridden = 0;
		}

		if ($force || $priority_overridden == 0) {
			$reply = $this->_database->update($this->table,['priority'=>$priority,'priority_overridden'=>$priority_overridden],[$this->primary_key=>$key]);
		}

		return $reply;
	}

	public function write_package_priority($key,$priority) {
		return $this->_database->update($this->table,['package_priority'=>$priority],[$this->primary_key=>$key]);
	}

	public function write_package_overridden($key,$overridden) {
		$overridden = ($overridden) ? 1 : 0;

		return $this->_database->update($this->table,['priority_overridden'=>$overridden],[$this->primary_key=>$key]);
	}

	/* get active in order */
	public function active() {
		return $this->_database->order_by('priority','asc')->where(['is_active'=>1])->get($this->table)->result();
	}

} /* end class */