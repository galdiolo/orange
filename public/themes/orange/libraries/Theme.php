<?php
/**
* Orange Framework Extension
*
* This content is released under the MIT License (MIT)
*
* @package	CodeIgniter / Orange
* @author	Don Myers
* @license	http://opensource.org/licenses/MIT	MIT License
* @link	https://github.com/dmyers2004
*/

class Theme {
	static protected $blocks;

	static public function hero_copy($html) {
		echo '<p style="padding: 14px 48px">'.$html.'</p>';
	}

	static public function start_form_section($name,$bold=false,$columns=10) {
		/* if bold is a number then that's the columns */
		if (is_int($bold)) {
			$columns = $bold;
			$bold = false;
		}

		$bold = ($bold === true) ? ' bold' : '';
		echo '<div class="form-group">';
		echo '<label class="col-md-2 control-label '.$bold.' "for="for-'.$name.'">'.$name.'</label>';
		echo '<div class="col-md-'.$columns.'">';
	}

	static public function end_form_section($help=null) {
		echo '</div></div>';
		self::help($help);
	}

	static public function start_static_text($class='',$padding_top=10) {
		echo '<p style="padding-top:'.$padding_top.'px" class="form-control-static '.$class.'">';
	}
	
	static public function end_static_text() {
		echo '</p>';
	}

	static public function static_text($text,$class='') {
		echo '<p class="form-control-static '.$class.'">'.$text.'</p>';
	}

	static public function checkbox($name = '', $value = null, $checked = null, $extra = []) {
		$text = $extra['text'];
		unset($extra['text']);

		self::start_inline_checkbox();
		o::checkbox($name,$value,$checked,$extra);
		echo ' '.$text;
		self::end_inline_checkbox();
	}

	static public function checker($name,$value,$matches=1,$extra=[]) {
		$text = $extra['text'];
		unset($extra['text']);

		self::start_inline_checkbox();
		o::checker($name,$value,$matches,$extra);
		echo ' '.$text;
		self::end_inline_checkbox();
	}

	static public function radio($name = '', $value = null, $options = [], $extra = []) {
		foreach ($options as $key=>$text) {
			self::start_inline_radio();
			o::radio($name,$key,($key == $value),$extra);
			echo ' '.$text;
			self::end_inline_radio();
		}
	}

	static public function start_inline_checkbox() {
		echo '<nobr style="margin-right:12px;top:6px;position:relative;">';
	}

	static public function end_inline_checkbox() {
		echo '</nobr> ';
	}

	static public function start_inline_radio() {
		echo '<nobr style="margin-right:12px;top:6px;position:relative;">';
	}

	static public function end_inline_radio() {
		echo '</nobr> ';
	}

	static public function access_dropdown($name,$value) {
		$groups = ci()->o_access_model->catalog();

		$options = [];

		foreach ($groups as $record) {
			$options[$record->id] = $record->key;
		}

		self::dropdown3($name,$value,$options,$extra);
	}

	static public function dropdown3($name, $value = null, $options = [], $extra = []) {
		$defaults = ['name' => $name, 'style' => '', 'id' => $name, 'class' => '', 'empty' => FALSE, 'key_value' => 'id', 'value_value' => 'name'];
		$list = array_merge($defaults, (array) $extra);

		$list['class'] .= ' select3';
		$list['data-width'] = '100%';

		echo o::element_rtn('select', $list);
		echo o::dropdown_options($value, $options, $list);
		echo '</select>';
	}

	static public function iif($expression, $returntrue, $returnfalse = '', $echo = false) {
		echo $expression ? $returntrue : $returnfalse;
	}

	static public function block_hide($name,$bol=null) {
		if ($bol === null) {
			return self::$blocks;
		}

		self::$blocks[$name] = $bol;
	}

	static public function block($name,$type='div',$prefix='block-') {
		/* if the blocks array doesn't equal false then show it (if it exsits) */
		if (self::$blocks[$name] !== true) {
			if ($block = ci()->load->get_var($name)) {
				echo '<'.$type.' class="'.$prefix.$name.'">'.$block.'</'.$type.'>';
			}
		}
	}

	static public function button($content, $extra = []) {
		/* type: button, submit, reset */
		$defaults = ['type' => 'button', 'name' => $content, 'style' => '', 'class' => '', 'id' => o::websafe($content), 'value' => $content, 'disabled' => ''];
		$list     = array_merge($defaults, (array) $extra);

		if (strpos($list['class'],'btn-') === false) {
			$list['class'] .= 'btn-default';
		}

		$list['class'] .= ' btn';

		if (!empty($list['url'])) {
			$list['href'] = $list['url'];
			unset($list['url']);
			echo o::element_rtn('a', $list, $content);
		} else {
			echo o::element_rtn('button', $list, $content);
		}
	}

	static protected function fa_icon_rtn($name, $extra) {
		return '<i class="fa fa-'.trim($name.' '.$extra).'"></i>';
	}

	static public function fa_icon($name = '', $extra = '') {
		echo self::fa_icon_rtn($name,$extra);
	}

	/* font awesome icon */
	static public function enum_icon($value,$string = 'circle-o|check-circle-o',$delimiter = '|') {
		$faicon = o::internal_enum($string, $value, $delimiter);
		echo self::fa_icon_rtn($faicon);
	}

	static public function portal_start($height = 150, $padding = 3) {
		$height  = (!is_numeric($height)) ? $height : $height.'px';
		$padding = (!is_numeric($padding)) ? $padding : $padding.'px';

		echo '<div style="border: 1px #ccc solid; padding: '.$padding.'; width: 100%;height: '.$height.'; overflow: auto">';
	}

	static public function portal_end() {
		echo '</div>';
	}

	static public function return_to_top() {
		echo '<small class="pull-right"><a style="color:#aaa" onclick="$(\'html, body\').animate({ scrollTop: 0 }, \'fast\');">Return to top</a></small>';
	}

	static public function help($help=null) {
		if (!empty($help)) {
			echo '<div class="row">';
			echo '<span class="col-md-10 col-md-offset-2 help-block">'.$help.'</span>';
			echo '</div>';
		}
	}

	/* table elements */
	static public function table_empty($records='#!!#') {
		if ($records !== '#!!#') {
			if (count($records) == 0) {
				echo '<h3>No Records Exist</h3>';

				return true;
			}
		}

		return false;
	}

	static public function table_start($column_names,$extra = [],$records='#!!#') {
		if (self::table_empty($records)) {
			return;
		}

		$defaults = ['class' => '', 'style' => '', 'id' => ''];
		extract(array_diff_key($defaults, $extra) + array_intersect_key($extra, $defaults));

		echo '<table class="table table-hover '.$class.'" id="'.$id.'" style="'.$style.'"><thead><tr class="panel-default">';

		foreach ($column_names as $key=>$name) {
			$class = '';

			if (!is_numeric($key)) {
				$class = $name;
				$name = $key;
			}

			echo '<th class="panel-heading '.$class.'">'.$name.'</th>';
		}

		echo '</tr></thead><tbody>';
	}

	/* table tabs */

	static public function table_tabs($records, $extra = []) {
		$human = $extra['human'];
		unset($extra['human']);
		
		$defaults = ['class' => '', 'style' => '', 'tab_text' => 'tab_text', 'tab_name' => 'tabs', 'nav_class' => 'nav-pills', 'hash_tabs' => TRUE];
		extract(array_diff_key($defaults, $extra)+array_intersect_key($extra, $defaults));

		echo '<ul class="nav '.$nav_class.' js-tabs" id="tabs-'.md5(ci()->page->data('controller_titles')).'" role="tablist">';

		foreach ($records as $tab_text => $record) {
			$tab_id = 'table-tab-'.md5($tab_text);
			$txt = ($human) ? ucwords(strtolower(str_replace(['_','-'],' ',$record[0]->tab_text))) : $record[0]->tab_text;
			echo '<li role="presentation" '.$class.'"><a href="#'.$tab_id.'" aria-controls="'.$tab_id.'" role="tab" data-toggle="pill">'.$txt.'</a></li>';
		}

		echo '</ul>';
	}

	static public function table_tabs_start($class=null) {
		$class = ($class) ? ' '.$class : '';
		echo '<div class="tab-content'.$class.'">';
	}

	static public function table_tabs_end() {
		echo '</div>';
	}

	static public function table_tab_pane_start($tab,$class=null) {
		$class = ($class) ? ' '.$class : '';
		echo '<div role="tabpanel" class="tab-pane'.$class.'" id="table-tab-'.md5($tab).'">';
	}

	static public function table_tab_pane_end() {
		echo '</div>';
	}

	/* table row */

	static public function table_start_tr($trclass = null,$tdclass = null) {
		$trclass = ($trclass) ? ' class="'.$trclass.'"' : '';
		$tdclass = ($tdclass) ? ' class="'.$tdclass.'"' : '';

		echo '<tr'.$trclass.'><td'.$tdclass.'>';
	}

	static public function table_row($class = null) {
		$class = ($class) ? ' class="'.$class.'"' : '';

		echo '</td><td'.$class.'>';
	}

	static public function table_action($icon, $url, $extra = []) {
		$defaults = ['handler' => 'js-'.$icon, 'icon_extra' => '', 'class' => '', 'data' => []];
		extract(array_diff_key($defaults, $extra)+array_intersect_key($extra, $defaults));

		echo self::table_action_link($url, $class.' '.$handler, $icon, $icon_extra, o::convert2data($data));
	}

	static public function table_action_link($url = '#', $class = '', $icon = '', $icon_extra = '',$a_extra='') {
		return (!empty($url)) ? '&nbsp;<a '.$a_extra.' href="'.$url.'" class="larger '.trim($class).'">'.self::fa_icon_rtn($icon, $icon_extra).'</a>' : '';
	}

	static public function table_end_tr() {
		echo '</td></tr>';
	}

	static public function table_end() {
		echo '</tbody></table>';
	}

	static public function table_footer() {
		echo '</div>';
	}

	/* Form Elements */
	static public function form_start($url = null, $record_id = null, $extra = []) {
		$defaults  = ['action' => $url, 'name' => '', 'style' => '', 'id' => 'main-form', 'class' => '', 'data-validate' => 'true', 'method' => 'post', 'accept-charset' => 'utf-8'];
		$list = array_merge($defaults, (array) $extra);
		$list['class'] .= ' form-horizontal';

		/* call form_helper */
		echo o::element_rtn('form', $list);

		/* add the record id */
		if (is_scalar($record_id)) {
			echo o::element_rtn('input', ['type' => 'hidden', 'id' => 'id', 'name' => 'id', 'value' => $record_id]);
		}
	}

	static public function form_end($extra) {
		o::close($extra);
		echo '<div>&nbsp;</div>';
	}

	/* universal header */

	static public function header_start($title = null, $help = null) {
		echo '<div class="row">';
		echo '<div class="col-md-7 header">';
		echo '<h3>'.$title.' <small class="hidden-xs">'.$help.'</small></h3>';
		echo '</div>';
		echo '<div class="col-md-5 header-buttons">';
	}

	static public function header_end() {
		echo '</div></div><div class="form-body">';
	}

	static public function header_button_new() {
		self::header_button('Add '.ci()->page->data('controller_title'), ci()->page->data('controller_path').'/new', 'magic');
	}

	static public function header_button_back($controller_path=null) {
		$controller_path = ($controller_path) ? $controller_path : 'javascript: window.history.go(-1)';
		self::header_button('Back',$controller_path,'reply');
	}

	static public function header_button_help($name=null) {
		if (file_exists(ROOTPATH.'/packages/'.$name.'/install/help.html')) {
			echo '&nbsp;<a class="btn btn-sm btn-default" href="/admin/configure/package/help/'.$name.'" target="_blank"><i class="fa fa-life-ring"></i></a>';
		}
	}

	static public function header_button($copy = null, $href = null, $icon = null, $extra = []) {
		$defaults = ['href' => $href, 'icon' => $icon];
		$list = array_merge($defaults, (array) $extra);
		$handler = ($list['handler']) ? $list['handler'] :'js-'.o::websafe($icon);

		$list['class'] = 'btn btn-default btn-sm '.$list['class'].' '.$handler;
		$list['role'] = 'button';

		unset($list['icon']);
		unset($list['handler']);

		echo '&nbsp;'.o::element_rtn('a', $list).'&nbsp;';
		echo ($icon != '') ? self::fa_icon_rtn($icon) : '';
		echo ' '.trim($copy).'</a>';
	}

	static public function header_button_dropdown($copy = 'button', $options = [], $extra = []) {
		$defaults = ['class' => '', 'style' => '', 'ul_class' => '', 'id' => o::websafe($copy),'icon'=>''];
		$list     = array_merge($defaults, (array)$extra);
		extract($list);

		echo '&nbsp;<div class="btn-group">';
		echo '<button type="button" data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle '.$a_class.'">';
		echo ($icon != '') ? self::fa_icon_rtn($icon).' ' : '';
		echo $copy.' <span class="caret"></span>';
		echo '</button>';
		echo '<ul class="dropdown-menu dropdown-menu-right '.$ul_class.'">';

		foreach ($options as $a => $t) {
			if ($t == '<hr>') {
				echo '<li class="divider"></li>';
			} else {
				if (substr($t, 0, 10) == '<selected>') {
					$t = substr($t, 10);
					echo ' style="font-weight: bold; color: #ec4c28"';
				} else {
					$d = '';
				}

				echo '<li><a href="'.$a.'" '.$d.'>'.$t.'</a></li>';
			}
		}

		echo '</ul></div>';
	}

	/* Form Footer */
	static public function footer_start($class='') {
		echo '</div>'; /* end form body wrapper */
		echo '<div class="form-footer-buttons '.$class.'">';
		echo '<div class="col-md-2"></div><div class="col-md-10">';
	}

	static public function footer_end() {
		echo '</div></div></div>';
	}

	static public function footer_cancel_button($href = null,$text='Cancel',$extra=[]) {
		$defaults = ['class' => 'btn btn-default js-cancel', 'href' => $href];
		$extra = array_merge($defaults, (array) $extra);

		self::footer_button($href,$text,$extra,'a');
	}

	static public function footer_submit_button($href = null,$text='Save',$extra=[]) {
		$defaults = ['class' => 'btn btn-primary js-submit', 'href' => $href];
		$extra = array_merge($defaults, (array) $extra);

		self::footer_button($href,$text,$extra,'button');
	}

	static public function footer_a_button($href, $text, $extra=[]) {
		$defaults = ['class' => 'btn btn-default js-'.o::websafe($text),'href' => $href];
		$extra = array_merge($defaults, (array) $extra);

		self::footer_button($href,$text,$extra,'a');
	}

	static public function footer_button($href,$text,$extra=[],$type='button') {
		$defaults = ['class' => 'btn btn-default js-'.o::websafe($text), 'href' => $href];
		$list = array_merge($defaults, (array) $extra);

		echo '&nbsp;&nbsp;&nbsp;';
		echo o::element_rtn($type,$list,$text);
	}

	static public function footer_required() {
		echo '<span class="required-txt hidden-xs">Required Fields are in Bold</span>';
	}

	static public function panel_start($header) {
		echo '<div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title">'.$header.'</h3></div><div class="panel-body">';
	}

	static public function panel_end() {
		echo '</div></div>';
	}

}/* end class */
