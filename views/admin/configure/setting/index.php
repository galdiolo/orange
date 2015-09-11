<?php
$config = (CONFIG) ? 'Config "'.CONFIG.'"' : '';
theme::header_start('Settings','Manage application wide settings. '.$config);
theme::header_button_new();
theme::header_button('Built in',$controller_path.'/list-all','file');
theme::header_end();

theme::table_empty($records['records']);

theme::table_tabs($records['records']);

theme::table_tabs_start();

foreach ($records['records'] as $tab=>$tab_records) {
	theme::table_tab_pane_start($tab);

	theme::table_start(['Name','Value','Managed'=>'text-center','Actions'=>'text-center']);

/*
	uasort($tab_records,function($a,$b) {
		return ($a->name > $b->name) ? 1 : -1;
	});
*/

	foreach ($tab_records as $record) {
		theme::table_start_tr();
		echo (!$record->enabled) ? '<i class="text-muted">' : '';
		o::e($record->name);
		echo (!$record->enabled) ? '</i>' : '';

		theme::table_row();
		echo theme::format_value($record->value,128);

		theme::table_row('larger text-center');
		echo theme::enum_icon((int)$record->managed);

		theme::table_row('actions text-center');
		if ($record->is_editable) {
			theme::table_action('edit',$this->controller_path.'/edit/'.$record->id);
		}

		if ($record->is_editable && has_access('Orange::Advanced Settings')) {
			theme::table_action('pencil-square',$this->controller_path.'/edit/'.$record->id.'/advanced');
		}

		if ($record->is_deletable) {
			o_dialog::confirm_a_delete($this->controller_path.'/delete/'.$record->id);
		}
		theme::table_end_tr();
	}

	theme::table_end();
	theme::table_tab_pane_end();
}
theme::table_tabs_end();

theme::return_to_top();