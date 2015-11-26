<?php

trait t_admin_read {

	/* index */
	public function indexAction() {
		if ($this->access['read']) {
			$this->has_access($this->access['read']);
		}

		if ($this->controller_model != NULL) {
			/* get all records apply order by or search if any */
			$records = $this->{$this->controller_model}->index($this->controller_orderby);
		}

		$this->page->data('records',$records)->build($this->controller_path.'/index');
	}

	/* standard format content into tabs if needed in the view */
	protected function _format_tabs($tabs_dbc, $tab_text = 'tab') {
		$tabs = [];
		$records = [];

		foreach ($tabs_dbc as $record) {
			$tab_name = preg_replace('/[^0-9a-z]+/', '', strtolower($record->$tab_text));

			$record->tab_text = $record->$tab_text;
			$tabs[$tab_name] = $record;
			$records[$tab_name][] = $record;
		}

		ksort($tabs);

		return ['tabs' => $tabs,'records' => $records];
	}

} /* end trait */