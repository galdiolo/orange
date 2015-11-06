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

	public function write($migration_version,$folder_name,$is_active,$priority=50) {
		return $this->_database->replace($this->table,['folder_name'=>$folder_name,'migration_version'=>$migration_version,'is_active'=>(int)$is_active,'package_priority'=>$priority,'priority'=>$priority]);
	}

	public function write_new_version($folder_name,$migration_version) {
		return $this->_database->update($this->table,['migration_version'=>$migration_version],['folder_name'=>$folder_name]);
	}

	public function write_new_priority($folder_name,$priority,$overridden=1,$force=false) {
		$reply = true;
		$overridden = ($overridden === true) ? 1 : 0;
		$record = $this->_database->where(['folder_name'=>$folder_name])->get($this->table); /* raw CI active record */
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
			$reply = $this->_database->update($this->table,['priority'=>$priority,'priority_overridden'=>$priority_overridden],['folder_name'=>$folder_name]);
		}

		return $reply;
	}

	public function write_package_priority($folder_name,$priority) {
		return $this->_database->update($this->table,['package_priority'=>$priority],['folder_name'=>$folder_name]);
	}

	public function write_package_overridden($folder_name,$overridden) {
		$overridden = ($overridden) ? 1 : 0;

		return $this->_database->update($this->table,['priority_overridden'=>$overridden],['folder_name'=>$folder_name]);
	}

	/* get active in order */
	public function active() {
		return $this->_database->order_by('priority','asc')->where(['is_active'=>1])->get($this->table)->result();
	}

} /* end class */