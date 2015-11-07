<?php
theme::form_start($controller_path.'/'.$controller_action,$record->id);
theme::header_start(ucfirst($controller_action).' '.$controller_title);
theme::header_end();

o::hr(0,12);

theme::start_form_section('Role',true,3);
o::text('name', $record->name);
theme::end_form_section();

theme::start_form_section('Description',6);
o::text('description', $record->description);
theme::end_form_section();

theme::start_form_section('Access');

/* sort the tabs */
sort($access_tabs);

/* sort all the entries by name - then when they are shown in tabs they are in order */
uasort($all_access,function($a,$b) {
	return ($a->name > $b->name) ? 1 : -1;
});

theme::table_tabs($records);

theme::table_tabs_start();

echo '<ul id="tabs" class="nav nav-pills js-tabs" data-tabs="tabs">';
foreach ($access_tabs as $idx=>$tab) {
	echo '<li><a href="#tab-'.md5($tab).'" data-toggle="tab">'.$tab.'</a></li>	';
}
echo '</ul>';

echo '<div class="well" style="overflow: hidden">';

echo '<div id="my-tab-content" class="tab-content">';
foreach ($access_tabs as $idx => $tab) {
	echo '<div class="tab-pane" id="tab-'.md5($tab).'" style="margin-top: -14px">';
	foreach ($all_access as $access_record) {
		if ($access_record->group == $tab) {
			echo '<div style="padding: 2px 0">';
			theme::checkbox('access['.$access_record->id.']', $access_record->id, array_key_exists($access_record->id, $access),['text'=>$access_record->name.' <small class="text-info">'.$access_record->description.'</small>']);
			echo '</div>';
		}
	}
	echo '</div>';
}
echo '</div>';

echo '</div>'; /* close well */

theme::end_form_section('&nbsp;');

o::view_event($controller_path,'form.footer');

o::hr(0,12);

theme::footer_start();
theme::footer_cancel_button($controller_path);
theme::footer_submit_button();
theme::footer_required();
theme::footer_end();

theme::form_end();

foreach ($access_options as $id=>$access) {
	theme::checkbox('groups[]', $id, array_key_exists($id, $groups),['text'=>$access->description.' <small style="opacity: .6">'.$access->key.'</small>']);
	echo '<div style="height: 6px;"></div>';
}
