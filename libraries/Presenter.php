<?php
/**
* Orange Framework Extension
*
* This content is released under the MIT License (MIT)
*	Original idea for a creating a CI presenter by Jamie Rumbelow
*
* @package CodeIgniter / Orange
* @author	Don Myers
* @license http://opensource.org/licenses/MIT	MIT License
* @link	https://github.com/dmyers2004
*
* Single record
* $record = $this->c_snippet_model->get(2);
* $record = $this->load->presenter($record,'role),
*
* Multiple records in a array
* $records = $this->c_snippet_model->index();
* $records = $this->load->presenter($records,'role');
*
*	inside the methods $this->object represents the current rows data
*
* While this is a neat idea unfortunately,
* using this will slow down your views
* especially lists (arrays)
* thou it is faster than looping over the array to prepare it
* 1 or more times before giving it to the view
* since these transformations are only applied at the time
* they are called in the view
*
*/
class Presenter {
	/* current object or row */
	protected $object;

	public function __construct($object = null) {
		$this->object = $object;
	}

	/* my magic function! */
	public function __get($property) {
		$return = '';

		/*
		built in function?
		
		$record->field__date;
		*/
		if (strpos($property,'__') !== false) {
			list($property,$built_in) = explode('__', $property,2);

			/* add our i_ (internal) prefix on */
			$built_in = 'i_'.$built_in;

			$return = (property_exists($this->object, $property) && method_exists($this,$built_in)) ? $this->$built_in($property) : '';
		} else {
			/*
			is it a raw value request?
			
			$record->field_raw
			*/
			$is_raw = (strtolower(substr($property,-4)) === '_raw');
	
			/* if so remove "raw" from the property */
			$property = $is_raw ? substr($property, 0, -4) : $property;
	
			/* Does this property exist? */
			if (property_exists($this->object, $property)) {
				/* yep! */
				$return = $this->object->$property;
			}
	
			/*
			is there a matching method and they aren't asking for a raw value?
			
			$record->method;
			*/

			if (method_exists($this,$property) && !$is_raw) {
				/* yep! */
				$return = $this->$property();
			}
	
		}

		/* then just return an empty string */
		return $return;
	}
	
	/* i_ internal */
	public function i_human_date($value) {
		$format = setting('presenter','date','l jS \of F Y h:i:s A');
	
		return date($format,strtotime($this->object->$value));
	}

	public function i_uppercase($value) {
		return strtoupper($this->object->$value);
	}

	public function i_lowercase($value) {
		return strtolower($this->object->$value);
	}
	
	public function i_enum_bol_string($value) {
		$enum = setting('presenter','enum',[0=>'False',1=>'True']);
	
		return $enum[$this->object->value];
	}

	public function i_enum_circle($value) {
		$enum = [0=>'circle-o',1=>'check-circle-o'];
	
		return $enum[$this->object->value];
	}

} /* end class */