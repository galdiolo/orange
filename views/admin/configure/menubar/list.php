<?php
theme::header_start('Menubar','manage menubars using the list view.');
Plugin_search_sort::field();
theme::header_button('Back',$controller_path,'reply');
theme::header_end();

theme::table_start(['Text','URL','Access','Parent','Active'=>'text-center','Actions'=>'text-center'],['tbody_class'=>'searchable','class'=>'sortable']);

foreach ($records as $record) {
	theme::table_start_tr();
	o::e($record->text);

	theme::table_row();
	o::e($record->url);

	theme::table_row();
	o::smart_model('o_access',$record->access_id,'key');

	theme::table_row();
	o::smart_model('o_menubar',$record->parent_id,'text');

	theme::table_row('text-center larger');
	theme::enum_icon($record->active);

	theme::table_row('actions text-center');
	if ($record->is_editable || has_access('Orange::Menubar::Override is editable')) {
		theme::table_action('edit',$this->controller_path.'/edit/'.$record->id);
	}

	if ($record->is_editable || has_access('Orange::Advanced Menubar')) {
		theme::table_action('pencil-square',$this->controller_path.'/edit/'.$record->id.'/advanced');
	}

	if ($record->is_deletable || has_access('Orange::Menubar::Override is deletable')) {
		o_dialog::confirm_a_delete($this->controller_path.'/delete/'.$record->id);
	}

	theme::table_end_tr();
}

theme::table_end();

theme::return_to_top();
