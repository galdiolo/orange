<?php
theme::form_start($controller_path.'/'.$controller_action,$record->id);
theme::header_start(ucfirst($controller_action).' '.$controller_title);
theme::header_end();

o::hr(0,12);

theme::start_form_section('Name',true,5);
/* if it wasn't user entered (type 0) than it's not editable */
if ($record->managed == 0 || $controller_action == 'new' || ($advanced == true && has_access('Orange::Advanced Settings'))) {
	o::text('name',$record->name);
} else {
	theme::static_text($record->name);
	o::hidden('name',$record->name);
}
theme::end_form_section();

$width = ($record->show_as == 3 && !empty($record->options)) ? $record->options : 9;

theme::start_form_section('Value',false,$width);
/* 0 Textarea, 1 Boolean T/F, 2 Radios (json), 3 Text Input (option width) */
switch($record->show_as) {
	case 1: /* boolean */
		$true = ($record->value == 'true') ? ' checked' : '';
		echo '<label class="radio-inline"><input type="radio" name="value" value="true" '.$true.'> True</label>';

		$false = ($record->value == 'false') ? ' checked' : '';
		echo '<label class="radio-inline"><input type="radio" name="value" value="false" '.$false.'> False</label>';
	break;
	case 2: /* radios */
		$options = json_decode($record->options);
		theme::radio('value',$record->value,$options);
	break;
	case 3: /* input */
		o::text('value',$record->value);
	break;
	default:
		$height = ($record->options > 1) ? 'height:'.filter_var($record->options,FILTER_SANITIZE_NUMBER_INT).'px' : '';

		o::textarea('value',$record->value,['class'=>' js-validate-json','style'=>$height]);
}
theme::end_form_section($record->help);

theme::start_form_section('Group',true,5);
if ($record->managed == 0 || $controller_action == 'new' || ($advanced == true && has_access('Orange::Advanced Settings'))) {
	$sorted = o::smart_model_list('o_setting_model','group','group');
	asort($sorted);
	plugin_combobox::show('group',$record->group,$sorted);
} else {
	theme::static_text($record->group);
	o::hidden('group',$record->group);
}
theme::end_form_section();

if ($advanced == true && has_access('Orange::Advanced Settings')) {
	theme::start_form_section('Enabled',true);
	theme::checker('enabled',(int)$record->enabled);
	theme::end_form_section('Settings which are present are usually considered "enabled" but, this is internally supported for debugging etc...');
	
	theme::start_form_section('Help');
	o::text('help',$record->help);
	theme::end_form_section('Help to display under the Value input element');

	theme::start_form_section('Internal');
	o::text('internal',$record->internal);
	theme::end_form_section('Internal package "owner"');

	theme::start_form_section('Managed',true);
	theme::checker('managed',(int)$record->managed);
	theme::end_form_section('If a setting is managed a user can not change it\'s group or name.');

	theme::start_form_section('Show As',true);
	theme::radio('show_as',$record->show_as,[0=>'textarea',1=>'boolean',2=>'radios',3=>'input']);
	theme::end_form_section();

	theme::start_form_section('Options');
	o::textarea('options',$record->options);
	theme::end_form_section('If <b>Show As</b> is:<br>textarea - optional height in pixels<br>boolean - no options are available<br>radios - you <b>MUST</b> enter a json object where the key/value pairs are the radio button options<br>input - optional bootstrap grid block width.');

	theme::start_form_section('Is Deletable');
	theme::checker('is_deletable',(int)$record->is_deletable);
	theme::end_form_section();

	o::hidden('advanced',1);
} else {
	if ($controller_action == 'new') {
		o::hidden('enabled',1);
		o::hidden('help','');
		o::hidden('internal','');
		o::hidden('managed',0);
		o::hidden('show_as',0);
		o::hidden('options','');
		o::hidden('is_deletable',1);
	} else {
		o::hidden('enabled',$record->enabled);
		o::hidden('help',$record->help);
		o::hidden('internal',$record->internal);
		o::hidden('managed',$record->managed);
		o::hidden('show_as',$record->show_as);
		o::hidden('options',$record->options);
		o::hidden('is_deletable',$record->is_deletable);
	}
}

o::view_event($controller_path,'form.footer');

o::hr(0,12);

theme::footer_start();
theme::footer_cancel_button($controller_path);
theme::footer_submit_button();
theme::footer_required();
theme::footer_end();

theme::form_end();