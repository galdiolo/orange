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
class Event {
	protected $listeners = [];

	/*
		priorities are set using the unix nice levels
		http://www.computerhope.com/unix/unice.htm
		
		In short - the lower the number the higher the priority
			 100 High
				0  Normal
			-100 Low
	*/
	public function register($name, $closure, $priority=0) {
		log_message('debug', 'event::register::'.$name);

		$this->listeners[$name][$priority][] = $closure;
		
		/* chainable */
		return $this;
	}

	public function trigger($name,&$a1=null,&$a2=null,&$a3=null,&$a4=null,&$a5=null,&$a6=null,&$a7=null,&$a8=null) {
		log_message('debug', 'event::trigger::'.$name);

		if ($this->has_event($name)) {
			$events = $this->listeners[$name];

			ksort($events);

			foreach ($events as $priority) {
				foreach ($priority as $event) {
					/* call closure - stop on false */
					if ($event($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8) === false) {
						break 2;
					}
				}
			}
		}
		
		/* chainable */
		return $this;
	}

	public function has_event($name) {
		return (isset($this->listeners[$name]) && count($this->listeners[$name]) > 0);
	}

	/* This is more for debugging since it's pretty raw data */
	public function events() {
		return array_keys($this->listeners);
	}

} /* end Event */