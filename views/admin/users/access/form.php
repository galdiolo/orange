<?php
theme::form_start($controller_path.'/'.$controller_action,$record->id);
theme::header_start(ucfirst($controller_action).' '.$controller_title);
theme::header_end();

o::hr(0,12);

theme::start_form_section('Group',true,3);
$sorted = o::smart_model_list('o_access_model','group','group');
asort($sorted);
plugin_combobox::show('group',$record->group,$sorted);
theme::end_form_section();

theme::start_form_section('Access',true,3);
o::text('name', $record->name);
theme::end_form_section();

theme::start_form_section('Description',6);
o::text('description', $record->description);
theme::end_form_section();

o::view_event($controller_path,'form.footer');

o::hr(0,12);

theme::footer_start();
theme::footer_cancel_button($controller_path);
theme::footer_submit_button();
theme::footer_required();
theme::footer_end();

theme::form_end();