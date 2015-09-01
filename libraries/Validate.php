<?php
/**
* Orange Framework Extension
*
* This content is released under the MIT License (MIT)
*
* @package	CodeIgniter / Orange
* @author	Don Myers
* @license	http://opensource.org/licenses/MIT	MIT License
* @link	https://github.com/dmyers2004
*/
/**
* New Validation Library
* a little more generic than form_validation
* but, it uses the same functions for validation
*
*/

include 'validate_rules/ci.php';
include 'validate_rules/dependent.php';
include 'validate_rules/file.php';
include 'validate_rules/filter.php';
include 'validate_rules/misc.php';
include 'validate_rules/numbers.php';
include 'validate_rules/string.php';
include 'validate_rules/time.php';
include 'validate_rules/image.php';

class Validate {
	use validate_ci,
		validate_dependent,
		validate_file,
		validate_filter,
		validate_misc,
		validate_numbers,
		validate_string,
		validate_time,
		validate_image;

	protected $_field_data         = [];
	protected $_error_array        = [];
	protected $_error_prefix       = '<p>';
	protected $_error_suffix       = '</p>';
	protected $error_string        = '';

	protected $json_options; /* use these on the errors array to create the json data */
	protected $attached = []; /* storage for validations attached as closures */
	protected $config; /* local copy of config */

	protected $die_on_failure; /* die on failure switch */
	protected $success; /* storage for validation success or failure */
	protected $error; /* storage for the errors */
	
	public $errors_detailed = []; /* error storage for debugging only */

	protected $ci_auth;
	protected $ci_db;
	protected $ci_input;
	protected $ci_security;
	protected $ci_load;
	protected $ci_config;
	protected $ci_user;
	protected $ci_session;

	/**
	* Constuct
	*
	* @param		array		configuration sent in via the library load call
	* @depends loader	because we use the settings method to load in the filesystem/database settings
	*/
	public function __construct() {
		$this->ci_auth = &ci()->auth;
		$this->ci_db = &ci()->db;
		$this->ci_input = &ci()->input;
		$this->ci_security = &ci()->security;
		$this->ci_load = &ci()->load;
		$this->ci_config = &ci()->config;
		$this->ci_user = &ci()->user;
		$this->ci_session = &ci()->session;

		$this->config = $this->ci_load->setting('validate');

		$this->json_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT;

		/* setup the defaults */
		$this->clear();

		log_message('debug', 'Validate Class Initialized');
	}

	/**
	* add_error
	*
	* Add an error to the error array.
	* This "master" array is used to generate all the error types
	*
	* @param	string	human readable error
	* @return	this		to allow chaining
	*/
	public function add_error($text,$field=null) {
		if ($field) {
			$this->_error_array[$field] = $text;	
		} else {			
			$this->_error_array[] = $text;
		}

		return $this;
	}

	/**
	* Get Array of Error Messages
	*
	* Returns the error messages as an array
	*
	* @return	array
	*/
	public function error_array() {
		return $this->_error_array;
	}

	/**
	* Error String
	*
	* Returns the error messages as a string, wrapped in the error delimiters
	*
	* @param	string
	* @param	string
	* @return	string
	*/
	public function error_string($prefix = null, $suffix = null) {
		// No errors, validation passes!
		if (count($this->_error_array) === 0) {
			return '';
		}

		if ($prefix === null) {
			$prefix = $this->_error_prefix;
		}

		if ($suffix === null) {
			$suffix = $this->_error_suffix;
		}

		// Generate the error string
		$str = '';
		foreach ($this->_error_array as $val) {
			if ($val !== '') {
				$str .= $prefix.$val.$suffix.chr(10);
			}
		}

		return $str;
	}

	/**
	* set_message
	*
	* wrapper to match the orginal method in form_validation library
	* Sets the current error if fail is returned from the validation
	* the first parameter is not used since these are set and used during the actual test
	*
	* @param	string	field name (not used) this is only here to match the form_validation library input
	* @param	string	current error message passed thru sprintf with human label (1), parameters (2)
	* @return	this		to allow chaining
	*/
	public function set_message($field = null, $text = null) {
		/*
		flip flop them so $text is empty
		we only need to pass 1 the orginal form_validation
		library passes the fieldname and the message
		*/
		if ($field == null && $text == null) {
			$this->error_string = '%s is a invalid value.';
		} elseif ($text == null) {
			$this->error_string = $field;
		} else {
			$this->error_string = $text;
		}

		return $this;
	}

	/**
	* Set The Error Delimiter
	*
	* Permits a prefix/suffix to be added to each error message
	*
	* @param	string
	* @param	string
	* @return	CI_Form_validation
	*/
	public function set_error_delimiters($prefix = '<p>', $suffix = '</p>') {
		$this->_error_prefix = $prefix;
		$this->_error_suffix = $suffix;

		return $this;
	}

	/**
	* error
	*
	* Return the last error in the error array human readable format
	*
	* @return	string
	*/
	public function error($field = null, $prefix = '', $suffix = '') {
		if ($field) {
			$html = (isset($this->_error_array[$field])) ? $this->_error_array[$field] : '' ;
		} else {
			$html = end($this->_error_array);
		}

		return $prefix.$html.$suffix;
	}

	/**
	* errors_json
	*
	* return the errors in json format
	* because it's json more than likely it will be used for further
	* processing therefore by default additional details are included
	*
	* @param	boolean	weither to return details default true
	* @param	integer	json Bitmask
	* @return	string	json
	*/
	public function errors_json($options = null) {
		$options = ($options)  ? $options : $this->json_options;

		return json_encode(['err' => true, 'errors' => $this->error_string('', '<br>'), 'errors_array' => $this->error_array()], $options);
	}

	/**
	* clear
	*
	* Clear / Init the library for processing.
	* if you need to process more than set of input you
	* will need to call this method to clear the library between calls
	*
	* @return	this	to allow chaining
	*/
	public function clear() {
		$this->_error_array = [];
		$this->errors_detailed = [];
		$this->die_on_failure = false;
		$this->success = false;

		return $this;
	}

	/**
	* attach
	* attach a validation function (closure)
	*
	* Heavy lifter to attach a closures to the library
	* these are the actual validation methods
	* each method is prefixed with validate_ + name so they don't run into actual
	* class methods. This is handled automatically when attaching and when they
	* are called so nothing needs to be done special.
	*
	* When the closures is called it will be passed:
	* Argument 1 a reference to this class
	* Argument 2 a refenence to the variable that needs processing
	* Argument 3 any extra parameters in brackets of the rule [1,2,3] in string format '1,2,3'.
	* these will need to be seperated with list() + explode() for example or through
	* some other means in the actual function.
	*
	* @param	string	validation method name
	* @param	closures	function to be called
	* @return	this	to allow chaining
	*/
	public function attach($name, closure $func) {
		log_message('debug', '"validate_'.$name.'" attached to Validate library.');

		$this->attached['validate_'.$name] = $func;

		return $this;
	}

	/**
	* die_on_fail
	*
	* weither to die automatically on the first validation failure.
	* This could be useful when testing input from a user which should be valid already
	* but may have been changed by the user for example.
	*
	* @param	boolean	Turn on or off this feature. Default true.
	* @return	this	to allow chaining
	*/
	public function die_on_fail($boolean = true) {
		$this->die_on_failure = (bool)$boolean;

		return $this;
	}

	public function filter($rules, &$field) {
		/* modifies the reference of field directly and don't fail */
		$this->single($rules, $field, false);

		return $this;
	}

	/**
	* one
	*
	* validate a single field with a set of rules.
	*
	* @param	string	rules in CodeIgniter form validation format
	* @param	mixed		variable to be tested passed by reference so it can be modified by the method if needed
	* @param	string	human label used in the error message as a sprintf parameter
	* @return	boolean true on success false on failure
	*/
	public function single($rules, &$field, $human_label = null) {
		/* is the rule set ($rules) stored in the validate config? */
		$rules = (!isset($this->config[$rules])) ? $rules : $this->config[$rules];

		/* if human_label is true then die on fali */
		if ($human_label === true) {
			$this->die_on_fail(true);
			$human_label = null;
		}

		log_message('debug', $human_label.'.'.$rules.'.'.$field);

		/* do we even have a rules to validate against? */
		if (!empty($rules)) {
			$rules = explode('|', $rules);

			foreach ($rules as $rule) {
				/* do we even have a rules to validate against? */
				if (empty($rule)) {
					$this->success = true;
					break;
				}
				
				/*
				Strip the parameter (if exists) from the rule
				Rules can contain a parameter: max_length[5]
				*/
				$param = null;

				if (preg_match("/(.*?)\[(.*?)\]/", $rule, $match)) {
					$rule  = $match[1];
					$param = $match[2];
				}

				$this->success = false;
				$this->error_string = '%s is not valid.';

				/* now we need to find this bugger */

				/* is it a attached (closure) validation function? */
				if (isset($this->attached['validate_'.$rule])) {
					$this->success = $this->attached['validate_'.$rule]($this,$field,$param);

				/* is this method attach to me? */
				} elseif (method_exists($this, $rule)) {
					if ($param !== null) {
						$this->success = $this->$rule($field, $param);
					} else {
						$this->success = $this->$rule($field);
					}

				/* is it a PHP method? */
				} elseif (function_exists($rule)) {
					/* Try PHP Functions */
					if ($param !== null) {
						$success = call_user_func($rule, $field, $param);
					} else {
						$success = call_user_func($rule, $field);
					}
					
					/* did the PHP method return a boolean? */
					if (is_bool($success)) {
						$this->success = $success;
					} else {
						$field = $success;
						$this->success = true;
					}

				} else {
					/* rule not found */
					$this->error_string = 'Could not validate %s against '.$rule;
				}

				/* fail! */
				if ($this->success === false) {
					/* ok let's clean out the field since it "failed" */
					$field = null;

					/* if the label is not provided try to gussy up the the rule name for human consumption */
					$human_label = (empty($human_label)) ? ucwords(str_replace('_', '', $rule)) : $human_label;

					if (strpos($param, ',') !== false) {
						/* Convert into a string so it can be used in the error message */
						$param = str_replace(',', ', ', $param);

						/* Replace last , with or */
						if (($pos = strrpos($param, ', ')) !== false) {
							$param = substr_replace($param, ' or ', $pos, 2);
						}
					}

					$this->add_error(sprintf($this->error_string, $human_label, $param));
					$this->errors_detailed[] = ['rule' => $rule,'param' => $param,'human_label' => $human_label,'value' => $field];

					/*
					Leave on first error?
					Since our form input is prevalidated
					then they must be forging the form data
					*/
					if ($this->die_on_failure) {
						$this->ci_auth->forged();
					}

					return $this->success;
				}
			}
		}

		return $this->success;
	}

	/**
	* multiple
	*
	* validate multiple fields with a set of fields
	*
	* @param	array	array of rules in CodeIgniter Format
	* @param	array	mixed variables to be tested passed by reference so it can be modified by the method if needed
	* @return	boolean true on success false on failure
	*/
	public function multiple($rules, &$fields, $strip = false) {
		$this->_field_data = &$fields;

		foreach ($rules as $fieldname => $rule) {
			/* success fail doesn't matter until we run all the tests on all of the fields */
			$this->single($rule['rules'], $this->_field_data[$fieldname], $rule['label']);
		}

		$fields = &$this->_field_data;
		
		/* strip any field which doesn't have a rule */
		if ($strip) {
			foreach ($fields as $k=>$f) {
				if (!array_key_exists($k,$rules)) {
					unset($fields[$k]);
				}
			}
		}

		return (bool) (count($this->_error_array) == 0);
	}
} /* end class */