<?php
theme::header_start('Settings','Manage export / import.');
theme::header_button('Import',$controller_path.'/manage-input','download');
theme::header_button('Export Config Files',$controller_path.'/manage-export','upload');
theme::header_button('Export Migration',$controller_path.'/manage-export','upload');
theme::header_button('Export Setting File',$controller_path.'/manage-export','upload');
theme::header_end();

theme::table_start(['Group','Name','Value','Export'=>'text-center']);

foreach ($records as $record) {
	theme::table_start_tr();
	o::e($record->group);

	theme::table_row();
	o::e($record->name);

	theme::table_row();
	o::shorten($record->value,256);

	theme::table_row('actions text-center');
	echo '<input type="checkbox" name="export[]" value="'.$record->id.'">';

	theme::table_end_tr();
}

theme::table_end();

theme::return_to_top();
