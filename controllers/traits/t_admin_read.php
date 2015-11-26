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

	/*
	standard format content into tabs if needed in the view
	this is a little slow because we need to determine the tab names
	and place all the records into those tabs (array of arrays)
	*/
	protected function _format_tabs($tabs_dbc, $tab_text = 'tab',$humanize=true) {
		$tabs = [];
		$records = [];
		
		/* this makes it slow */
		foreach ($tabs_dbc as $record) {
			$tab_name = preg_replace('/[^0-9a-z]+/', '', strtolower($record->$tab_text));
			
			/* make tab text the $tab_text parameter */
			$record->tab_text = ($humanize) ? ucwords(str_replace(['-','_'],' ',$record->$tab_text)) : $record->$tab_text;
			
			$tabs[$tab_name] = $record;
			$records[$tab_name][] = $record;
		}

		/* sort the tabs by there names */
		ksort($tabs);
		
		/* put them in the correct format */
		return ['tabs' => $tabs,'records' => $records];
	}

} /* end trait */