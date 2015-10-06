<?php
theme::header_start('Package Load Order','Customize package load order.');
theme::header_button('Return to Packages',$back_url,'reply');
theme::header_button('Regenerate Onload',$controller_path.'/onload','cog');
theme::header_button('Regenerate Autoload',$controller_path.'/config','cog');
theme::header_button('Reset All',$controller_path.'/reset','refresh');
theme::header_button('Save Changes','#','floppy-o');
theme::header_end();

echo '<form id="mainform">';

echo '<p><small>Common package priorities: themes 10, default 50, library 80, orange packages > 90</small></p>';

theme::table_start(['Name','Type'=>'text-center','Description','Package Priority'=>'text-center','Current Priority'=>'text-center','Change to'=>'text-center'],null,$records);

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
	echo '<span style="';
	echo ($is_active) ? 'font-weight: 700">' : '">';
	o::e($record['folder']);
	echo '</span>';

	/* type */
	theme::table_row('text-center');
	echo '<span class="label label-'.$type_map[$record['type']].'">'.$record['type'].'</span>';

	/* Description */
	theme::table_row();
	o::e($record['name']);

	/* priority */
	theme::table_row('text-center');
	echo $record['json_priority'];

	/* current priority */
	theme::table_row('text-center');

	echo ($record['priority_overridden'] == 1) ? '<span class="badge"><strong>' : '';
	echo $record['priority'];
	$icon = ((int)$record['json_priority'] > (int)$record['priority']) ? 'angle-up' : 'angle-down';
	echo ($record['priority_overridden'] == 1) ? ' <i class="fa fa-'.$icon.'"></i></strong></span>' : '';

	/* edit field */
	theme::table_row('text-center');
	o::text('order['.$url_name.']','',['class'=>'editfield','maxlength'=>3]);
	
	//k($record);
}

echo '</form>';