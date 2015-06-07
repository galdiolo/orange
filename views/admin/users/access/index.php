<?php
theme::header_start('Access','manage access attached to roles.');
theme::header_button_new();
theme::header_end();

theme::table_empty($records['records']);

theme::table_tabs($records['records']);

theme::table_tabs_start();

asort($records['records']);

foreach ($records['records'] as $tab=>$tab_records) {
	theme::table_tab_pane_start($tab);
	theme::table_start(['Name','Description','Key','Actions'=>'txt-ac']);

	uasort($tab_records,function($a,$b) {
		return ($a->name > $b->name) ? 1 : -1;
	});

	foreach ($tab_records as $record) {
		theme::table_start_tr();
		o::e($record->name);

		theme::table_row();
		o::e($record->description);

		theme::table_row();
		echo ' <small class="text-muted">';
		o::e($record->group.'::'.$record->name);
		echo '</small>';
			
		theme::table_row('actions txt-ac');
	
		if ($record->is_editable) {
			theme::table_action('edit',$this->controller_path.'/edit/'.$record->id);
		}
	
		if ($record->is_deletable) {
			o_dialog::confirm_a_delete($this->controller_path.'/delete/'.$record->id);
		}
	
		theme::table_action('list-ul',$this->controller_path.'/details/'.$record->id);
	
		theme::table_end_tr();
	}

	theme::table_end();
	theme::table_tab_pane_end();
}
theme::table_tabs_end();
theme::return_to_top();