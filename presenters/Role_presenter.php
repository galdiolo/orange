<?php
class Role_presenter extends Presenter {
	public function cookies() {
		return 'cookies value is: '.$this->object->username.' Moster!';
	}

	public function name() {
		return 'Name overridden! '.$this->object->username;
	}
} /* end class */
