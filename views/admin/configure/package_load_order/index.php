<?php
theme::header_start('Package Load Order','If you need to customize the load order of packages beyond the set priority.');
theme::header_button('Finished',$back_url,'reply');
theme::header_button('Save','#','floppy-o');
theme::header_end();

echo '<form id="mainform">';

theme::table_start(['Name','Type'=>'text-center','Description','Package Priority'=>'text-center','Current Priority'=>'text-center','Change'=>'text-center'],null,$records);

foreach ($db_records as $db_record) {
	$record = $records[$db_record->folder_name];
	//k($record);

	/* skip this record? */
	if (substr($name,0,1) == '_' || empty($record['folder'])) {
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
	echo '<a href="'.$controller_path.'/search/'.$record['type'].'">';
	echo '<span class="label label-'.$type_map[$record['type']].'">'.$record['type'].'</span>';
	echo '</a>';

	/* Description */
	theme::table_row();
	if ($record['name'] == 'Orange') {
		echo '<span style="font-weight: 700;color: #DF521B">Orange</span>';
	} elseif($record['json_error']) {
		echo '<span style="font-weight: 700;color: #A90018">info.json error</span>';
	} else {
		o::e($record['name']);
	}

	/* priority */
	theme::table_row('text-center');
	echo $record['priority'];

	/* current priority */
	theme::table_row('text-center');
	echo $db_record->priority;
	echo ($db_record->priority_overridden == 1) ? '*' : '';

	/* edit field */
	theme::table_row('text-center');
	o::text('order['.$url_name.']','');

}

echo '</form>';