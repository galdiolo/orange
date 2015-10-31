<?php
/**
*
* This base model should only contain functions (or stubs of functions)
* that all models that extend it might have (basic CRUD for example)
* it also contains the model validation functions
* since all models should validate data
*
* Orange Framework Extension
*
* This content is released under the MIT License (MIT)
*
* @package	CodeIgniter / Orange
* @author	Don Myers
* @license	http://opensource.org/licenses/MIT	MIT License
* @link	https://github.com/dmyers2004
*/
class MY_Model extends CI_Model {
	protected $rules = [];
	protected $rule_sets = [];
	protected $rule_override = null;

	public $errors = '';
	public $errors_array = [];
	public $errors_json = ['err' => false,'errors' => '','errors_array' => ''];

	/* for a database model this would be considered the "table" */
	protected $object = null;

	/* This model's connection (resource or other) */
	protected $connection = null;

	/* This model's default primary key or unique identifier. Used by the get(), update() and delete() functions. */
	public $primary_key = 'id';

	/* Optionally skip the validation. Used in conjunction with skip_validation() to skip data validation for any future calls. */
	protected $skip_validation = false;

	/* Protected, non-modifiable attributes - these are stripped from updates and inserts */
	protected $protected_attributes = [];

	public function __construct() {
		parent::__construct();

		$this->load->library('validate');

		log_message('debug', 'Model '.$this->object.' Initialized');
	}

	public function object() {
		return $this->object;
	}

	/* --------------------------------------------------------------
	* Standard CRUD Interface
	* ------------------------------------------------------------ */

	/* Fetch a single record based on the primary key. Returns an object. */
	public function get($primary_value) {
	}

	/* Fetch a single record based on an arbitrary WHERE call. Can be any valid value to $this->_database->where(). */
	public function get_by() {
	}

	/* Fetch all the records in the table. Can be used as a generic call to $this->_database->get() with scoped methods. */
	public function get_many() {
	}

	/* Fetch an array of records based on an arbitrary WHERE call. */
	public function get_many_by() {
	}

	/* Insert a new row into the table. $data should be an associative array of data to be inserted. Returns newly created ID. */
	public function insert($data, $skip_validation = false) {
	}

	/* Updated a record based on the primary value. */
	public function update($primary_value, $data, $skip_validation = false) {
	}

	/* Updated a record based on an arbitrary WHERE clause. */
	public function update_by($where, $data, $skip_validation = false) {
	}

	/* Delete a row from the table by the primary value */
	public function delete($id=null) {
	}

	/* Delete a row from the database table by an arbitrary WHERE clause */
	public function delete_by() {
	}

	/* Truncates the table */
	public function truncate() {
	}

	/* Fetch a count of rows based on an arbitrary WHERE call. */
	public function count_by() {
	}

	/* Fetch a total count of rows, disregarding any previous conditions */
	public function count_all() {
	}

	/* get the last error */
	public function last_error() {
	}

	/* Utility Method Interface */

	/* Create a associated array where key is "unquie" record indicator */
	public function catalog() {
	}

	public function seed($count=0) {
	}

	/* Real Model Generic Functions */

	/* run get function to determine if a record with the primary exists */
	public function primary_exists($primary_value) {
		return ($this->get($primary_value)) ? false : true;
	}

	/* Tell the class to skip the insert validation */
	public function skip_validation($value = null) {
		if ($value === null) {
			return $this->skip_validation;
		}

		$this->skip_validation = $value;

		return $this;
	}

	/* manually add some kind of validation error to the errors array */
	public function add_error($msg) {
		$this->validate->add_error($msg);

		/* regenerate */
		$this->errors       = $this->validate->error_string();
		$this->errors_array = $this->validate->error_array();
		$this->errors_json  = ['err' => true,'errors' => $this->errors,'errors_array' => $this->errors_array];

		return $this;
	}

	/*
	Validate model based on
	the data passed in (key value pair) and
	the rule_name (from model) or rule set passed in (validation rules)
	*/
	public function validate($data = null, $rule_name = null) {
		/* clean all errors */
		$this->errors = '';
		$this->errors_array = [];
		$this->errors_json = ['err' => false,'errors' => '','errors_array' => ''];

		$data = (array)$data;

		if ($this->skip_validation) {
			return $data;
		}

		ci()->event->trigger('model.'.$this->object.'.before.validate',$data,$rule_name);

		if ($rule_name === true) {
			log_message('debug', 'Do Not Validate');

			return $data;
		}

		$rule_name = ($rule_name) ? $rule_name : 'default';

		if ($this->rule_override) {
			$rule_name = $this->rule_override;
			$this->rule_override = null;
		}

		if (is_array($rule_name)) {
			$rules = $rule_name;
		} else {
			/* build a rule set based off of the items in my list */
			$rules = $this->build_rule_sets($rule_name);
		}

		ci()->event->trigger('model.'.$this->object.'.validate.rules',$rules);

		if (!is_array($rules) || !count($rules)) {
			log_message('debug', 'Rules Empty in My Model');

			/* rules set not an array so nothing to validate against */
			return $data;
		}

		/* filter input to only include what's in the rule set this also gives us protection */
		$filtered_data = [];

		foreach ($rules as $idx => $rule) {
			if (isset($rule['field'])) {
				$filtered_data[$rule['field']] = $data[$rule['field']];
			}
		}

		$success = ($this->validate->clear()->multiple($rules, $filtered_data) === true);

		$error_array = null; /* array for additional errors added by trigger */

		/* fail success = false */
		ci()->event->trigger('model.'.$this->object.'.after.validate',$data,$filtered_data,$rule_name,$success,$error_array);

		/* if success is false then setup error */
		if ($success === false) {
			$this->errors = $this->validate->error_string();
			$this->errors_array = $this->validate->error_array();
			$this->errors_json = ['err' => true,'errors' => $this->errors,'errors_array' => $this->errors_array];

			log_message('debug', trim($this->errors));
		} else {
			/* if success is not false then return the filtered data */
			$success = $filtered_data;
		}

		/* Error return false */
		return $success;
	}

	/*
	comma sep. list of fields to map
		id,name,age
	optional as
		id,name as fullname,age
	*/
	public function build_rule_sets($name = null) {
		$rules = false;

		if ($name) {
			$ary = explode(',', $this->rule_sets[$name]);

			foreach ($ary as $val) {
				if (!empty($val)) {
					$rule = $rulename = $val;

					if (strpos($val, ' as ') !== false) {
						list($rule, $rulename) = explode(' as ', $val, 2);
					}

					$rules[$rulename] = (empty($this->rules[$rule])) ? '' : $this->rules[$rule];
				}
			}
		}

		return $rules;
	}

	/* Protect attributes by removing them from $row array */
	public function protect_attributes(&$row) {
		foreach ($this->protected_attributes as $attr) {
			if (is_object($row)) {
				unset($row->$attr);
			} else {
				unset($row[$attr]);
			}
		}

		return $this;
	}

	/* add a rule */
	public function add_rule($name, $rule) {
		$this->rules[$name] = $rule;

		return $this;
	}

	public function add_rules($rules) {
		foreach ((array)$rules as $rule_name=>$r) {
			$this->rules[$rule_name] = $r;
		}
		
		return $this;	
	}

	/* get a single rule by field name */
	public function get_rule($name = null,$which=null) {
		$rule = ($name === null) ? $this->rules : $this->rules[$name];
		
		return ($which) ? $rule[$which] : $rule;
	}

	/*
	get the rules for certain fields on this model
	rules sent in as comma seperated list of field names
	*/
	public function get_rules($input) {
		$array = (is_array($input)) ? $input : explode(',',$input);
		$rules = [];

		foreach ($array as $name) {
			$rules[$name] = $this->rules[$name];
		}

		return $rules;
	}

	/* add a rule set (fields) to the model */
	public function add_rule_set($name, $fields='') {
		$this->rule_sets[$name] = (is_array($fields)) ? implode(',',$fields) : $fields;

		return $this;
	}

	/* get the field(s) in a rule set */
	public function get_rule_set($name=null) {
		return ($name === null) ? $this->rule_sets : $this->rule_sets[$name];
	}

} /* end MY_Model */