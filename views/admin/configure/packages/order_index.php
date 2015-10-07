<?php
theme::header_start('Package Load Order','Customize package load order.');
theme::header_button_back();
//theme::header_button('Regenerate Onload',$controller_path.'/onload','cog');
//theme::header_button('Regenerate Autoload',$controller_path.'/config','cog');
theme::header_button('Reset All',$controller_path.'/reset','refresh',['class'=>'js-o_dialog','data-icon'=>'info','data-text'=>'Are you sure you want to reset all priorities to the package values?','data-heading'=>'Reset All Priorities','data-redirect'=>$controller_path.'/reset']);
theme::header_button('Save Changes','#','floppy-o');
theme::header_end();

echo '<form id="mainform">';

echo '<p><small>Common package priorities: themes 10, default 50, library 80, orange packages > 90</small></p>';

theme::table_start(['Name','Type'=>'text-center','Description','Package Priority'=>'text-center',''=>'text-center','Current Priority'=>'text-center','Change to'=>'text-center'],null,$records);

foreach ($records as $record) {
	/* skip this record? */
	if (substr($record['folder'],0,1) == '_' || empty($record['is_active'])) {
		continue;
	}

	/* setup a few things */
	$url_name = bin2hex($record['folder']);
	$is_active = $record['is_active'];

	/* Name */
	theme::table_start_tr();
	o::e($record['folder']);

	/* type */
	theme::table_row('text-center');
	echo '<span class="label label-'.$type_map[$record['type']].'">'.$record['type'].'</span>';

	/* Description */
	theme::table_row();
	o::e($record['name']);

	if (!$record['json_error']) {
		echo ' - ';
		o::e($record['info']);
	}

	/* priority */
	theme::table_row('text-center');
	echo $record['json_priority'];

	/* more or less? */
	theme::table_row('text-center');
	if ($record['json_priority'] > $record['priority']) {
		echo '<span class="badge"><i class="fa fa-angle-up" style="font-weight:bold"></i></span>';
	} elseif ($record['json_priority'] < $record['priority']) { 
		echo '<span class="badge"><i class="fa fa-angle-down" style="font-weight:bold"></i></span>';
	} else {
		echo '<strong>=</strong>';
	}


	/* current priority */
	theme::table_row('text-center');
	echo $record['priority'];

	/* edit field */
	theme::table_row('text-center');
	o::text('order['.$url_name.']','',['class'=>'editfield','maxlength'=>3]);
	
	//k($record);
}

echo '</form>';