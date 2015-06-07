<?php
/**
* A base Database model with a series of CRUD functions (powered by CI's query builder),
* validation-in-model support, events and more.
*
* Orange Framework Extension
*
* This content is released under the MIT License (MIT)
*
* @package	CodeIgniter / Orange
* @author	Don Myers
* @license	http://opensource.org/licenses/MIT	MIT License
* @link	https://github.com/dmyers2004
*
* Based on Original Work by Jamie Rumbelow
* @link http://github.com/jamierumbelow/codeigniter-base-model
* @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
*
*/
abstract class Database_model extends MY_Model {
	/**
	* The database connection object. Will be set to the default
	* connection. This allows individual models to use different DBs
	* without overwriting CI's global $this->db connection.
	*/
	protected $_database; /* connection to database resource */
	protected $table; /* table name - this is also used as the resource object name */
	protected $db_group = null; /* database config group to use */
	protected $debug = false; /* path to debug file to write - used naturally for local debugging */
	protected $caches = []; /* place for internal caches */
	protected $cache_prefix; /* this is auto generated in the constuctor */
	protected $soft_delete = false; /* does this table support soft delete? */
	protected $soft_delete_key = 'is_deleted'; /* what is the name of the soft delete column? */

	/* internal */
	protected $_temporary_with_deleted = false;
	protected $_temporary_only_deleted = false;

	/* --------------------------------------------------------------
	* GENERIC METHODS
	* ------------------------------------------------------------ */

	/* Initialise the model, tie into the CodeIgniter superobject */
	public function __construct() {
		/* the parent model expects a more generic object "name" */
		$this->object = $this->table;

		parent::__construct();

		$this->cache_prefix	 = 'tbl_'.$this->object;

		/* use a custom database connection? */
		if (isset($this->db_group)) {
			$this->_database = $this->load->database($this->db_group, true);
		} else {
			$this->_database = $this->db;
		}
	}

	/* --------------------------------------------------------------
	* CRUD INTERFACE
	* ------------------------------------------------------------ */

	/**
	* Fetch a single record based on the primary key. Returns an object.
	*/
	public function get($primary_value) {
		$method = __FUNCTION__;
		ci()->event->trigger('database.'.$this->object.'.before.get',$method,$primary_value);

		if ($primary_value) {
			$this->where_soft_delete();

			$result = $this->_database->where($this->primary_key, $primary_value)->get($this->table);

			$this->log_last_query();

			/* get returns a single object so return the first record or an empty record */
			$result = ($result->num_rows()) ? $result->result()[0] : (object) [];
		}

		ci()->event->trigger('database.'.$this->object.'.after.get',$method,$result);

		return $result;
	}

	/**
	* Fetch a single record based on an arbitrary WHERE call. Can be
	* any valid value to $this->_database->where().
	*/
	public function get_by() {
		$method = __FUNCTION__;
		$where = func_get_args(); /* get this so we can send it to the trigger */

		ci()->event->trigger('database.'.$this->object.'.before.get',$method,$where);

		if ($where != NULL) {
			$this->where_soft_delete();

			$this->where($where);

			$result = $this->_database->get($this->table);

			$this->log_last_query();

			/* get returns a single object so return the first record or an empty record */
			$result = ($result->num_rows()) ? $result->result()[0] : (object) [];
		}

		ci()->event->trigger('database.'.$this->object.'.after.get',$method,$result);

		return $result;
	}

	/**
	* Fetch all the records in the table. Can be used as a generic call
	* to $this->_database->get() with scoped methods.
	*/
	public function get_many() {
		$method = __FUNCTION__;
		ci()->event->trigger('database.'.$this->object.'.before.get',$method);

		$this->where_soft_delete();

		$result = $this->_database->get($this->table);

		/* get returns a array of objects */
		if (is_object($result)) {
			$result = ($result->num_rows()) ? $result->result() : [];
		} else {
			$result = [];
		}

		$this->log_last_query();

		ci()->event->trigger('database.'.$this->object.'.after.get',$method,$result);

		return $result;
	}

	/**
	* Fetch an array of records based on an arbitrary WHERE call.
	*/
	public function get_many_by() {
		$method = __FUNCTION__;
		$where = func_get_args();

		ci()->event->trigger('database.'.$this->object.'.before.get',$method,$where);

		if ($where) {
			$this->where_soft_delete();

			$this->where($where);

			$result = $this->_database->get($this->table);

			/* get returns a array of objects */
			if (is_object($result)) {
				$result = ($result->num_rows()) ? $result->result() : [];
			} else {
				$result = [];
			}

			$this->log_last_query();
		}

		ci()->event->trigger('database.'.$this->object.'.after.get',$method,$result);

		return $result;
	}

	/**
	* Insert a new row into the table. $data should be an associative array
	* of data to be inserted. Returns newly created ID.
	*/
	public function insert($data, $skip_validation = false) {
		$method = __FUNCTION__;
		$this->flush_caches();

		ci()->event->trigger('database.'.$this->object.'.before.insert',$method,$data,$skip_validation);

		/* unset the primary key if it's set in the data array */
		if (isset($data[$this->primary_key])) {
			unset($data[$this->primary_key]);
		}

		if ($skip_validation !== true) {
			$rule = ($skip_validation === false) ? 'insert' : $skip_validation;

			/* return false on failure data validated & filtered */
			$data = $this->validate($data, $rule);
		}

		if ($data !== false) {
			/* passed by ref */
			parent::protect_attributes($data);

			$this->_database->insert($this->table, $data);

			$this->log_last_query();

			$insert_id = $this->_database->insert_id();

			ci()->event->trigger('database.'.$this->object.'.after.insert',$method,$data,$insert_id);

			return (int) $insert_id;
		}

		return false;
	}

	/**
	* Updated a record based on the primary value.
	*/
	public function update($primary_value, $data, $skip_validation = false) {
		$method = __FUNCTION__;
		$this->flush_caches();

		ci()->event->trigger('database.'.$this->object.'.before.update',$method,$primary_value,$data,$skip_validation);

		if ($skip_validation !== true) {
			$rule = ($skip_validation === false) ? 'update' : $skip_validation;

			/* return false on failure data validated & filtered */
			$data = $this->validate($data, $rule);
		}

		if ($data !== false) {
			/* passed by ref */
			parent::protect_attributes($data);

			$result = $this->_database->where($this->primary_key, $primary_value)->set($data)->update($this->table);

			$this->log_last_query();

			ci()->event->trigger('database.'.$this->object.'.after.update',$method,$data,$result);

			return $result;
		}

		return false;
	}

	/**
	* Updated a record based on an arbitrary WHERE clause.
	*/
	public function update_by($where, $data, $skip_validation = false) {
		$method = __FUNCTION__;
		$this->flush_caches();

		ci()->event->trigger('database.'.$this->object.'.before.update',$method,$data,$where,$skip_validation);

		/* rule override is handled in the parent class */
		if ($skip_validation !== true) {
			$rule = ($skip_validation === false) ? 'insert' : $skip_validation;

			/* return false on failure data validated & filtered */
			$data = $this->validate($data, $rule);
		}

		if ($data !== false) {
			/* passed by ref */
			parent::protect_attributes($data);

			$result = $this->_database->set($data)->where($where)->update($this->table);

			$this->log_last_query();

			ci()->event->trigger('database.'.$this->object.'.after.update',$method,$data,$result);

			return $result;
		}

		return false;
	}

	/**
	* Delete a row from the table by the primary value
	*/
	public function delete($id=null) {
		$method = __FUNCTION__;
		$this->flush_caches();

		ci()->event->trigger('database.'.$this->object.'.before.delete',$method,$id);

		if ($id) {
			$this->_database->where($this->primary_key, $id);

			if ($this->soft_delete) {
				$result = $this->_database->update($this->table, [$this->soft_delete_key => date('Y-m-d H:i:s')]);
			} else {
				$result = $this->_database->delete($this->table);
			}
		}

		ci()->event->trigger('database.'.$this->object.'.after.delete',$method,$id,$result);

		return $result;
	}

	/**
	* Delete a row from the database table by an arbitrary WHERE clause
	*/
	public function delete_by() {
		$method = __FUNCTION__;
		$this->flush_caches();

		$where = func_get_args(); /* so we can pass it to the trigger */

		ci()->event->trigger('database.'.$this->object.'.before.delete',$method,$where);

		if ($where != NULL) {
			$this->where($where);

			if ($this->soft_delete) {
				$result = $this->_database->update($this->table, [$this->soft_delete_key => date('Y-m-d H:i:s')]);
			} else {
				$result = $this->_database->delete($this->table);
			}
		}

		ci()->event->trigger('database.'.$this->object.'.after.delete',$method,$id,$result);

		return $result;
	}
	
	/* --------------------------------------------------------------
	* UTILITY METHODS
	* ------------------------------------------------------------ */

	/* Getter for the table name */
	public function table() {
		return $this->table;
	}

	public function exists($column, $field) {
		$method = __FUNCTION__;
		ci()->event->trigger('database.'.$this->object.'.before.exists',$method,$column,$field);

		$row = $this->_database->query("SELECT COUNT(`".$column."`) AS dupe FROM `".$this->table."` WHERE `".$column."` = ".$this->_database->escape($field)."")->row()->dupe;
		$this->log_last_query();

		return ($row > 0) ? true : false;
	}

	/* --------------------------------------------------------------
	* QUERY BUILDER DIRECT ACCESS METHODS
	* ------------------------------------------------------------ */

	/* Set WHERE parameters, cleverly */
	protected function where($params) {
		if (count($params) == 1 && is_array($params[0])) {
			foreach ($params[0] as $field => $filter) {
				if (is_array($filter)) {
					$this->_database->where_in($field, $filter);
				} else {
					if (is_int($field)) {
						$this->_database->where($filter);
					} else {
						$this->_database->where($field, $filter);
					}
				}
			}
		} elseif (count($params) == 1) {
			$this->_database->where($params[0]);
		} elseif (count($params) == 2) {
			if (is_array($params[1])) {
				$this->_database->where_in($params[0], $params[1]);
			} else {
				$this->_database->where($params[0], $params[1]);
			}
		} elseif (count($params) == 3) {
			$this->_database->where($params[0], $params[1], $params[2]);
		} else {
			if (is_array($params[1])) {
				$this->_database->where_in($params[0], $params[1]);
			} else {
				$this->_database->where($params[0], $params[1]);
			}
		}

		return $this;
	}

	/**
	* Validation Methods
	*/

	/* used by form validation to find unique */
	public function is_uniquem($field, $column, $postkey) {
		$query = $this->_database
			->select($column.','.$this->primary_key)
			->from($this->table)
			->where($column, $field)
			->limit(1)
			->get();

		if ($query->num_rows() > 0) {
			if ($query->row()->{$this->primary_key} != $this->input->post($postkey)) {
				return false;
			}
		}

		return true;
	}

	public function build_sql_where_in($array) {
		/* becuase we need to escape we need to run it through a loop */
		$sql = '(';

		/* add proper escaping */
		foreach ($array as $a) {
			if (is_numeric($a)) {
				$sql .= $this->_database->escape($a + 0).",";
			} else {
				$sql .= "'".$this->_database->escape($a)."',";
			}
		}

		return rtrim($sql, ',').')';
	}

	public function debug_log($file_path = false) {
		$this->debug = $file_path;
	}

	public function last_query() {
		return $this->_database->last_query();
	}

	public function log_last_query() {
		if ($this->debug) {
			$query	= $this->last_query();
			$output = (is_array($query)) ? print_r($query, true) : $query;
			file_put_contents($this->debug, $output.chr(10), FILE_APPEND);
		}
	}

	public function limit($limit, $offset = 0) {
		$this->_database->limit($limit, $offset);

		return $this;
	}

	public function order_by($criteria, $order = 'ASC') {
		if (is_array($criteria)) {
			foreach ($criteria as $key => $value) {
				$this->_database->order_by($key, $value);
			}
		} else {
			$this->_database->order_by($criteria, $order);
		}

		return $this;
	}

	/* called on insert, update, delete */
	public function flush_caches() {
		log_message('debug', 'Model Cache Flush: '.$this->cache_prefix);

		$cached = ci()->cache->cache_info();
		$strlen = strlen($this->cache_prefix);

		foreach ($cached as $key=>$record) {
			if (substr($key,0,$strlen) == $this->cache_prefix) {
				ci()->cache->delete($key);
			}
		}

		return $this;
	}

	/**
	* Don't care about soft deleted rows on the next call
	*/
	public function with_deleted() {
		$this->_temporary_with_deleted = true;

		return $this;
	}

	/**
	* Only get deleted rows on the next call
	*/
	public function only_deleted() {
		$this->_temporary_only_deleted = true;

		return $this;
	}

	/* set soft delete */
	protected function where_soft_delete() {
		if ($this->soft_delete && $this->_temporary_with_deleted !== true) {
			$gt = ($this->_temporary_only_deleted) ? ' >' : '';

			$this->_database->where($this->soft_delete_key.$gt, '0000-00-00 00:00:00');
		}

		return $this;
	}

	/**
	* Restore
	* Set 'deleted' field to 0
	*/
	public function restore($id) {
		if ($this->soft_delete === true) {
			return $this->update($id, [$this->soft_delete_key => '0000-00-00 00:00:00']);
		}

		return false;
	}

	/**
	* Create a associated array
	* this can be used for dropdowns or easy access to model values based on the primary ID for example
	* by default these are filtered
	* is the primary id and the value is the entire record
	* these are also automatically cached
	*
	* catalog() - primary id=>(object)record
	* catalog(id,color) - id=>color
	*
	*/
	public function catalog($key=null,$name=null) {
		$method = __FUNCTION__;
		$order_by = $name;

		if ($key == null || $name == null) {
			$key = $this->primary_key;
			$name = '*';
			$order_by = 'id';
		}

		$cache_key = $this->cache_prefix.'.'.$key.'.'.$name.'.catalog';

		ci()->event->trigger('database.'.$this->object.'.before.get',$method,$data,$cache_key);
		
		/* is this already in the objects page "request" cache */
		if ($this->caches[$cache_key]) {
			/* send it out ASAP */
			return $this->caches[$cache_key];
		}

		/* is it in the normal cache? */
		if (!$data = ci()->cache->get($cache_key)) {
			$data = [];

			/* get the results as you would for a normal index */
			$results = $this->index($order_by);

			foreach ($results as $row) {
				$data[$row->$key] = ($name == '*') ? $row : $row->$name;
			}

			ci()->event->trigger('database.'.$this->object.'.after.get',$method,$data,$cache_key);

			ci()->cache->save($cache_key, $data);
		}

		/* put it in the objects page "request" cache */
		$this->caches[$cache_key] = $data;

		return $data;
	}

	/* default method called to produce the index view records */
	public function index($orderby = null) {
		/* did we get any? */
		if ($orderby) {
			$direction = 'ASC';

			if (strpos($orderby, ',') !== false) {
				list($orderby, $direction) = explode(',', $orderby);
			}

			$this->order_by($orderby, $direction);
		}

		return $this->get_many();
	}

	/**
	* Fetch a count of rows based on an arbitrary WHERE call.
	*/
	public function count_by() {
		$method = __FUNCTION__;
		$where = func_get_args();

		ci()->event->trigger('database.'.$this->object.'.before.count.by',$method,$where);

		$this->where($where);
		$this->where_soft_delete();

		return $this->_database->count($this->collection);
	}

	/**
	* Fetch a total count of rows, disregarding any previous conditions
	*/
	public function count_all() {
		$method = __FUNCTION__;

		ci()->event->trigger('database.'.$this->object.'.before.count.all',$method);

		$this->where_soft_delete();

		return $this->_database->count($this->collection);
	}

} /* end DB Model */