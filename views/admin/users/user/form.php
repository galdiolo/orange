<?php
theme::form_start($controller_path.'/'.$controller_action,$record->id);
theme::header_start(ucfirst($controller_action).' '.$controller_title);
theme::header_end();

o::hr(0,12);

theme::start_form_section('User Name',true,3);
o::text('username', $record->username);
theme::end_form_section();

theme::start_form_section('Email',true,3);
o::text('email', $record->email);
theme::end_form_section();

theme::start_form_section('Password',($record->id == -1),3);
o::password('password',['value'=>$record->password]);
theme::end_form_section($password_format_copy);

theme::start_form_section('Confirm Password',($record->id == -1),3);
o::password('confirm_password',['value'=>$record->confirm_password]);
theme::end_form_section();

if ($record->id != 1) {
	theme::start_form_section('Role',4);
	theme::dropdown3('role_id', $record->role_id, o::smart_model_list('o_role_model','id','name'));
	theme::end_form_section();

	theme::start_form_section('Active');
	theme::checkbox('is_active', 1, $record->is_active);
	theme::end_form_section();
} else {
	o::hidden('role_id',$record->role_id);
	o::hidden('is_active',$record->is_active);
}

if (strtotime($record->last_login) > 1) {
	theme::start_form_section('Last login');
	theme::static_text(date('F j, Y, g:i a', strtotime($record->last_login)));
	o::hidden('last_login',$record->last_login);
	theme::end_form_section();
}

o::view_event($controller_path,'form.footer');

o::hr(0,12);

theme::footer_start();
theme::footer_cancel_button($controller_path);
theme::footer_submit_button();
theme::footer_required();
theme::footer_end();

theme::form_end();