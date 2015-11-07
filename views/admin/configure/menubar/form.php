<?php
theme::form_start($controller_path.'/'.$controller_action,$record->id);
theme::header_start($controller_title);
theme::header_end();

o::hr(0,12);

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

if (setting('menubar','Show Color')) {
	theme::start_form_section('Color',3);
	if (ci()->load->library_exists('plugin_colorpicker')) {
		plugin_colorpicker::picker('color',$record->color);
	} else {
		o::text('color',$record->color);
	}
	theme::end_form_section();
} else {
	o::hidden('color','d28445');
}

if (setting('menubar','Show Icon')) {
	theme::start_form_section('Icon',3);
	if (ci()->load->library_exists('plugin_fontawesome')) {
		plugin_fontawesome::dropdown('icon',$record->icon);
	} else {
		o::text('icon',$record->icon);
	}
	theme::end_form_section();
} else {
	o::hidden('icon','square');
}

if ($advanced == true && has_access('Orange::Advanced Menubar')) {	
	theme::start_form_section('Target',3);
	o::text('target',$record->target);
	theme::end_form_section();

	theme::start_form_section('Class',3);
	o::text('class',$record->class);
	theme::end_form_section();
	
	theme::start_form_section('Internal');
	o::text('internal',$record->internal);
	theme::end_form_section('Internal package "owner"');

	theme::start_form_section('Is Editable');
	theme::checker('is_editable',(int)$record->is_editable);
	theme::end_form_section();

	theme::start_form_section('Is Deletable');
	theme::checker('is_deletable',(int)$record->is_deletable);
	theme::end_form_section();

	o::hidden('advanced',1);
} else {
	if ($controller_action == 'new') {
		o::hidden('target','');
		o::hidden('class','');
		o::hidden('internal','');
		o::hidden('is_editable',1);
		o::hidden('is_deletable',1);
	} else {
		o::hidden('target',$record->target);
		o::hidden('class',$record->class);
		o::hidden('internal',$record->internal);
		o::hidden('is_editable',$record->is_editable);
		o::hidden('is_deletable',$record->is_deletable);
	}
}

o::hidden('return_to',$return_to);

o::view_event($controller_path,'form.footer');

o::hr(0,12);

theme::footer_start();
theme::footer_cancel_button($controller_path);
theme::footer_submit_button();
theme::footer_required();
theme::footer_end();

theme::form_end();