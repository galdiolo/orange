<?php
/* on delete assigned to this role */
$role_name = o::smart_model('o_role_model',setting('auth','Default Role Id'),'name',true);

theme::header_start('Roles','manage user roles.');
Plugin_search_sort::field();
theme::header_button_new();
theme::header_end();

theme::table_start(['Name','Description','Actions'=>'text-center'],['tbody_class'=>'searchable','class'=>'sortable']);

foreach ($records as $record) {
	theme::table_start_tr();
	o::e($record->name);

	theme::table_row();
	o::e($record->description);

	theme::table_row('actions text-center');

	if ($record->is_editable) {
		theme::table_action('edit',$this->controller_path.'/edit/'.$record->id);
	}

	if ($record->is_deletable) {
		o_dialog::confirm_a_delete($this->controller_path.'/delete/'.$record->id,['data'=>['append'=>'<br>This will reassign all users with this role to the "'.$role_name.'" role.']]);
	}

	theme::table_action('list-ul',$this->controller_path.'/details/'.$record->id);

	theme::table_end_tr();
}

theme::table_end();

theme::return_to_top();