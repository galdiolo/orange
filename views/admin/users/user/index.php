<?php
theme::header_start('Users','manage users');
theme::header_button_new();
theme::header_end();

theme::table_start(['Name','Email','Role','Active'=>'txt-ac','Actions'=>'txt-ac']);

foreach ($records as $record) {
	theme::table_start_tr();
	o::e($record->username);

	theme::table_row();
	o::e($record->email);

	theme::table_row();
	echo '<a href="/admin/users/role/details/'.$record->role_id.'">';
	o::smart_model('o_role',$record->role_id,'name');
	echo '</a>';

	theme::table_row('txt-ac larger');
	theme::enum_icon($record->is_active);

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

theme::return_to_top();