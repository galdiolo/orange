<?php
theme::header_start('Packages','Interface to manage packages.');
Plugin_search_sort::field();
theme::header_button('Customize Load Order',$controller_path.'/load-order','sort-amount-asc');
o::view_event($controller_path,'header.buttons');
theme::header_end();

/* display errors */
if ($errors) {
	echo '<div class="alert alert-danger" role="alert">';
	echo '<b>We have a problem!</b><br>';
	echo $errors.'<br>';
	echo 'This needs to be fixed in order for packages to be dynamically loaded.';
	echo '</div>';
}

theme::table_start(['Name','Type'=>'text-center','Description','Version'=>'text-center','Actions'=>'text-center'],['tbody_class'=>'searchable','class'=>'sortable'],$records);

//kd($records);

foreach ($records as $name=>$record) {
	//k($name);
	//k($record);

	/* setup a few things */
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
	if($record['json_error']) {
		echo '<span style="font-weight: 700;color: #A90018">'.$record['json_error_txt'].'</span>';
	} else {
		o::e($record['name']);
	}

	if (!$record['json_error']) {
		/* (i) for more information */
		echo ' - ';
		o::e($record['info']);
		echo ' <a href="'.$controller_path.'/details/'.$record['url_name'].'" class="" data-name="'.$name.'"><i class="fa fa-info-circle"></i></a> ';
	}

	/* Version */
	theme::table_row('text-center');
	/* show upgrade version and up arrow? */
	switch ($record['version_display']) {
		case 1: /* less than */
			echo '<span class="label label-info"><i class="fa fa-exclamation-triangle"></i> '.$record['version'].'</span>&nbsp;';
			echo '<span class="label label-primary">'.$record['migration_version'].'</span> ';
		break;
		case 2:
			/* version in db matches migration version */
			echo '<span class="label label-primary">'.$record['migration_version'].'</span> ';
		break;
		case 3: /* greater than */
			echo '<span class="label label-info"> <i class="fa fa-arrow-up"></i>'.$record['version'].'</span>&nbsp;';
			echo '<span class="label label-primary">'.$record['migration_version'].'</span> ';
		break;
		default:
			echo '<span class="label label-default">'.$record['version'].'</span> ';
	}

	/* Actions */
	theme::table_row('text-center');
	echo '<nobr>';
	
	/* show info buton? error or otherwise */
	if ($record['button']['info']) {
		echo '<a href="'.$controller_path.'/details/'.$record['url_name'].'" class="btn btn-xs btn-primary"><i class="fa fa-question-circle"></i></a> ';
	}

	/* show install */
	if ($record['button']['install']) {
		echo '<a href="'.$this->controller_path.'/install/'.$record['url_name'].'" class="btn btn-xs btn-default">install</a> ';
	}

	/* show upgrade */
	if ($record['button']['upgrade']) {
		echo '<a href="'.$this->controller_path.'/upgrade/'.$record['url_name'].'" class="btn btn-xs btn-info">upgrade</a> ';
	}

	/* show uninstall */
	if ($record['button']['uninstall']) {
		echo '<a href="'.$this->controller_path.'/uninstall/'.$record['url_name'].'" data-name="'.$record['name'].'" class="btn btn-xs btn-warning js-uninstallable">Uninstall</a> ';
	}
	
	/* show delete */
	if ($record['button']['delete']) {
		echo '<a href="'.$this->controller_path.'/delete/'.$record['url_name'].'" data-name="'.$record['name'].'" data-redirect="true" data-icon="trash" data-text="Are you sure you want to delete this package?" data-heading="Delete Record" class="btn btn-xs btn-danger js-o_dialog"><i class="fa fa-trash"></i></a> ';
	}

	echo '</nobr>';

	theme::table_end_tr();
}

theme::table_end();

theme::return_to_top();