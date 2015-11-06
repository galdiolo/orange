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
*
*/
class Presenter_iterator implements Iterator {
	protected $inject;

	/* current array or row */
	protected $array;

	/* index in array */
	protected $index = 0;

	/* name of the presenter class */
	protected $class = null;

	public function __construct($array = null,$class = null,$inject = null) {
		$this->array = $array;
		$this->index = 0;
		$this->class = $class;
		$this->inject = $inject;
	}

	/* Iterator required methods */
	public function current() {
		return new $this->class($this->array[$this->index],$this->inject);
	}

	public function key() {
		return $this->index;
	}

	public function next() {
		++$this->index;
	}

	public function rewind() {
		$this->index = 0;
	}

	public function valid() {
		return isset($this->array[$this->index]);
	}

} /* end class */