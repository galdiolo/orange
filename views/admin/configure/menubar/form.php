<?php
theme::form_start($controller_path.'/'.$controller_action,$record->id);
theme::header_start($controller_title);
theme::header_end();

o::hr(0,12); /* 4px padding top and bottom */

o::hidden('sort', $record->sort);
o::hidden('parent_id', $record->parent_id);

theme::start_form_section('Text',true,4);
o::text('text',$record->text);
theme::end_form_section();

theme::start_form_section('URL',true,6);
o::text('url',$record->url);
theme::end_form_section('Entering /# will cause the menu item to not have a link');

theme::start_form_section('Active');
theme::checkbox('active',1,($record->active == 1));
theme::end_form_section();

theme::start_form_section('Access',4);
theme::access_dropdown('access_id',$record->access_id);
theme::end_form_section();

if (setting('menubar','Show Class')) {
	theme::start_form_section('Class',3);
	o::text('class',$record->class);
	theme::end_form_section();
} else {
	o::hidden('class','');
}

if (setting('menubar','Show Color')) {
	theme::start_form_section('Color',2);
	plugin_colorpicker::picker('color',$record->color);
	theme::end_form_section();
} else {
	o::hidden('color','d28445');
}

if (setting('menubar','Show Icon')) {
	theme::start_form_section('Icon');
	plugin_fontawesome::dropdown('icon',$record->icon);
	theme::end_form_section();
} else {
	o::hidden('icon','square');
}

o::view_event($controller_path,'form.footer');

o::hr(0,12); /* 4px padding top and bottom */

theme::footer_start();
theme::footer_cancel_button($controller_path);
theme::footer_submit_button();
theme::footer_required();
theme::footer_end();

theme::form_end();