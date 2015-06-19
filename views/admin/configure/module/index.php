<?php
theme::header_start('Modules','Interface to manage modules.');
if ($filter) {
	theme::header_button('Show All',$controller_path,'filter');
}
o::view_event($controller_path,'header.buttons');
theme::header_end();

/* display errors */
if (count($records['_messages']) > 0) {
	echo '<div class="alert alert-danger" role="alert">';
	echo '<b>We have a problem!</b><br>';
	foreach ($records['_messages'] as $m) {
		echo $m.'<br>';
	}
	echo 'These need to be fixed in order for modules to be dynamically loaded.';
	echo '</div>';
}

theme::table_start(['Name','Type'=>'txt-ac','Description','Version'=>'txt-ac','Actions'=>'txt-ac'],null,$records);

foreach ($records as $name=>$record) {
	if (substr($name,0,1) == '_' || (!empty($filter) && $record['type'] != $filter)) {
		continue;
	}

	$url_name = bin2hex($record['classname']);
	$is_active = $record['is_active'];

	/* setup defaults */	
	$allow_upgrade = false;
	$allow_install = false;
	$allow_uninstall = false;
	$allow_delete = false;

	/* Name */
	theme::table_start_tr();
	echo '<span style="';
	echo ($is_active) ? 'font-weight: 700">' : '">';
	o::e($record['classname']);
	echo '</span>';

	/* type */
	theme::table_row('txt-ac');
	echo '<a href="'.$controller_path.'/index/'.$record['type'].'">';
	$typer($record['type']);
	echo '</a>';

	/* Description */
	theme::table_row();
	if ($record['name'] == 'Orange') {
		echo '<span style="font-weight: 700;color: #DF521B">Orange</span>';
	} else {
		o::e($record['name']);
	}
	echo ' - ';
	o::e($record['info']);
	echo ' <a href="'.$controller_path.'/details/'.$url_name.'" class="" data-name="'.$name.'"><i class="fa fa-info-circle"></i></a> ';

	/* Version */
	theme::table_row('txt-ac');
	/* show upgrade version and up arrow? */
	if ($is_active && $record['has_upgrade'] != '') {
		if (version_compare($record['has_upgrade'],$record['version'],'>')) {
			echo '<span class="label label-info"><i class="fa fa-arrow-up"></i> '.$record['has_upgrade'].'</span> ';
			$allow_upgrade = true;
		} elseif (version_compare($record['has_upgrade'],$record['version'],'<')) {
			echo '<span class="label label-info"><i class="fa fa-exclamation-triangle"></i> '.$record['has_upgrade'].'</span> ';
		} elseif (version_compare($record['has_upgrade'],$record['version'],'=')) {
			echo '<span class="label label-info"><i class="fa fa-exclamation-triangle"></i> '.$record['has_upgrade'].'</span> ';
		}
	}

	if ($is_active) {
		echo '<span class="label label-primary">'.$record['version'].'</span> ';
	} else {
		echo '<span class="label label-default">'.$record['version'].'</span> ';
	}

	/* Actions */
	theme::table_row('txt-ac');

	if ($is_active) {
		$allow_uninstall = true;
	}

	if (!$is_active && !empty($record['version'])) {
		$allow_install = true;
		$allow_delete = true;
	}

	/* did we get any required errors? */
	if (count($record['install_errors']) > 0) {
		$allow_upgrade = false;
		$allow_install = false;
	}

	if (count($record['upgrade_errors']) > 0) {
		$allow_upgrade = false;
	}

	if (count($record['uninstall_errors']) > 0) {
		$allow_uninstall = false;
	}

	echo '<nobr>';
	
	/* so error /!\ */
	if ($is_active) {
		$errors = array_merge_recursive($record['install_errors'],$record['upgrade_errors'],$record['uninstall_errors']);
	} else {
		$errors = array_merge_recursive($record['install_errors'],$record['uninstall_errors']);
	}
	
	$info_class = (count($record['upgrade_errors']) > 0) ? 'info' : 'primary';
	
	if (count($errors) > 0) {
		echo '<a class="js-issues btn btn-xs btn-'.$info_class.'" data-myname="'.$record['name'].'" data-errors="'.str_replace('"','&quot;',implode('<br>',$errors)).'"><i class="fa fa-question-circle"></i></a> ';
	}
	
	/* show install */
	if ($allow_install && $record['install']) {
		echo '<a href="'.$this->controller_path.'/install/'.$url_name.'" class="btn btn-xs btn-default">install</a> ';
	}

	/* show upgrade */
	if ($allow_upgrade && $record['has_upgrade']) {
		echo '<a href="'.$this->controller_path.'/upgrade/'.$url_name.'" class="btn btn-xs btn-info">upgrade</a> ';
	}

	/* show uninstall */
	if ($allow_uninstall && $record['uninstall']) {
		echo '<a href="'.$this->controller_path.'/uninstall/'.$url_name.'" data-name="'.$record['name'].'" class="btn btn-xs btn-warning js-uninstallable">Uninstall</a> ';
	}

	/* show delete */
	if ($allow_delete && $record['remove']) {
		echo '<a href="'.$this->controller_path.'/delete/'.$url_name.'" data-name="'.$record['name'].'" class="btn btn-xs btn-danger js-remove"><i class="fa fa-trash"></i></a> ';
	}

	echo '</nobr>';

	theme::table_end_tr();
}

theme::table_end();

theme::return_to_top();