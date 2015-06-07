<?php
theme::header_start('File Based Sets','Only config files which use the $config format are displayed.');
theme::header_button('Back',$controller_path,'reply');
theme::header_end();

theme::table_start(['Name']);

foreach ($records as $record) {
	theme::table_start_tr();
	echo '<a href="'.$controller_path.'/group/'.$record->name.'"><i class="fa fa-tachometer"></i> '.$record->name.'</a>';
	theme::table_end_tr();
}

theme::table_end();

theme::return_to_top();