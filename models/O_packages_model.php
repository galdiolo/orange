<?php

class o_packages_model extends Database_model {
	protected $table = 'orange_packages';

	public function activate($folder_name,$is_active) {
		return $this->_database->update($this->table,['is_active'=>(int)$is_active],['folder_name'=>$folder_name]);
	}

	public function remove($folder_name) {
		return $this->_database->delete($this->table,['folder_name'=>$folder_name]);
	}

	public function read($folder_name) {
		$results = $this->_database->where(['folder_name'=>$folder_name,'is_active'=>1])->get($this->table);

		return ($results->num_rows()) ? (array)$results->result()[0] : [];
	}

	public function write($migration_version,$folder_name,$is_active) {
		return $this->_database->replace($this->table,['folder_name'=>$folder_name,'migration_version'=>$migration_version,'is_active'=>(int)$is_active]);
	}

	public function write_new_version($folder_name,$migration_version) {
		return $this->_database->update($this->table,['migration_version'=>$migration_version],['folder_name'=>$folder_name]);
	}

} /* end class */