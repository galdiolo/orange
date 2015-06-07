<?php
/**
* Orange Framework Extension
* This provides a "giant" list of static functions
* for use in the view.
* These are of course accessable on the O (Orange) class.
*
* This content is released under the MIT License (MIT)
*
* @package	CodeIgniter / Orange
* @author	Don Myers
* @license	http://opensource.org/licenses/MIT	MIT License
* @link	https://github.com/dmyers2004
*/

class O {

	public function __construct() {
		ci()->load->helper('form');
	}

	/* standard escape decorator */
	static public function e($html) {
		echo htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
	}

	static public function html($html) {
		echo $html;
	}

	static public function convert2list($input, $key = 'id', $value = 'name', $orderby = '', $dir = 'a') {
		ci()->load->helper('array');

		return array2list($input, $key, $value, $orderby, $dir);
	}

	static public function open($url, $extra = []) {
		/* call form_helper */
		echo form_open($url, $extra);
	}

	static public function close($string) {
		/* call form_helper */
		echo form_close($string);
	}

	static public function hash($name, $value) {
		echo self::hidden($name, '$H$'.str_replace('=', '', base64_encode($value.chr(0).md5($value.ci()->config->item('encryption_key')))));
	}

	static public function text($name = '', $value = null, $extra = []) {
		$defaults = ['type' => 'text','name' => $name,'style' => '','placeholder' => '','id' => $name,'class' => '','maxlength' => '','value' => self::escape($value)];
		$list = array_merge($defaults, $extra);
		$list['class'] .= ' form-control';
		$list['value'] = html_escape($list['value']);

		echo self::element_rtn('input', $list);
	}

	static public function password($name = '', $extra = []) {
		$defaults = ['type' => 'password','value'=>'','name' => $name,'value' => '','style' => '','placeholder' => '','id' => $name,'class' => '','maxlength' => ''];
		$list = array_merge($defaults, $extra);
		$list['class'] .= ' form-control';
		$list['value'] = html_escape($list['value']);

		echo self::element_rtn('input', $list);
	}

	static public function dropdown($name, $value = null, $options, $extra = []) {
		$defaults = ['name' => $name,'style' => '','id' => $name,'class' => ''];
		$list = array_merge($defaults, $extra);

		echo self::element_rtn('select', $list).self::dropdown_options($value, $options, $extra).'</select>';
	}

	static public function textarea($name = '', $value = null, $extra = []) {
		$defaults = ['name' => $name,'style' => '','id' => $name,'class' => 'form-control','cols' => 25,'rows' => 4,'maxlength' => 8192,'placeholder' => ''];
		$list = array_merge($defaults, $extra);
		$list['class'] .= ' form-control';

		echo self::element_rtn('textarea', $list, $value);
	}

	static public function hidden($name = '', $value = null, $extra = []) {
		$defaults = ['type' => 'hidden','class' => '','name' => $name,'id' => $name,'value' => $value,'hmac' => FALSE,'empty' => FALSE];
		$list = array_merge($defaults, $extra);
		$list['value'] = html_escape($list['value']);

		echo self::element_rtn('input', $list);
	}

	static public function checkbox($name = '', $value = null, $checked = null, $extra = []) {
		$checked = ($checked == $value) ? 'checked' : '';

		$defaults = ['style' => '','class' => '','name' => $name,'id' => $name,'type' => 'checkbox','value' => self::escape($value),'checked' => $checked];
		$list = array_merge($defaults, $extra);
		$list['value'] = html_escape($list['value']);

		echo self::element_rtn('input', $list);
	}

	static public function checker($name,$value,$matches=1,$extra=[]) {
		$defaults = ['checked'=>1,'unchecked'=>0];
		$list = array_merge($defaults, $extra);
		$checked = ($value == $matches) ? ' checked' : '';
	
		echo '<input type="checkbox" class="checkers" data-real="'.$name.'" data-checked="'.$list['checked'].'" data-unchecked="'.$list['unchecked'].'"'.$checked.'>';
	}
	
	static public function radio($name = '', $value = null, $checked = null, $extra = []) {
		/* the name matches all radios */
		$checked = ($checked) ? 'checked' : '';

		$defaults = ['style' => '','class' => '','name' => $name,'id' => $name,'type' => 'radio','value' => self::escape($value),'checked' => $checked];
		$list = array_merge($defaults, $extra);
		$list['value'] = html_escape($list['value']);

		echo self::element_rtn('input', $list);
	}

	static public function button($content, $extra = []) {
		/* type: button, submit, reset */
		$defaults = ['style' => '','class' => '','name' => $content,'id' => self::websafe($content),'value' => $content,'disabled' => '','type' => 'button'];
		$list = array_merge($defaults, $extra);

		echo self::element_rtn('button', $list, $content);
	}

	static public function a($href, $text, $extra = []) {
		$defaults = ['style' => '','id' => $name,'class' => '','href' => $href];
		$list = array_merge($defaults, $extra);

		echo self::element_rtn('a', $list, self::escape($text));
	}

	static public function label($title = '', $extra = []) {
		$defaults = ['title' => $title,'style' => '','class' => $class,'for' => $for];
		$list = array_merge($defaults, $extra);
		$title = $list['title'];

		unset($list['title']);

		echo self::element_rtn('label', $list, $title);
	}

	static public function label_start($extra = []) {
		$defaults = ['style' => '','id' => $name,'class' => ''];
		$list = array_merge($defaults, $extra);

		echo self::element_rtn('label', $list);
	}

	static public function label_end() {
		echo '</label>';
	}

	static public function spacer($w = 12, $h = null) {
		echo self::spacer_rtn($w,$h);
	}

	static public function spacer_rtn($w = 12, $h = null) {
		$h = ($h) ? $h : $w;
		return '<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" style="width:'.$w.'px;height:'.$h.'px" width="'.$w.'" height="'.$h.'">';
	}

	static public function hr($top=4,$bottom=null,$color='ccc') {
		$bottom = ($bottom) ? $bottom : $top;
		echo '<hr style="margin: '.$top.'px 0 '.$bottom.'px 0; display: block; height: 1px; border: 0; border-top: 1px solid #'.$color.'">';
	}

	static public function span_start($extra = []) {
		$defaults = ['style' => '','id' => $name,'class' => ''];
		$list = array_merge($defaults, $extra);

		echo self::element_rtn('span', $list);
	}

	static public function span_end() {
		echo '</span>';
	}

	static public function div_start($extra = []) {
		$defaults = ['style' => '','id' => $name,'class' => ''];
		$list = array_merge($defaults, $extra);

		echo self::element_rtn('div', $list);
	}

	static public function div_end() {
		echo '</div>';
	}

	static public function smart_dropdown($name = '', $selected = '', $model_name, $extra = []) {
		$defaults = ['name' => $name,'style' => '','id' => $name,'class' => '','obj_key' => 'id','obj_val' => 'name'];
		$list = array_merge($defaults, $extra);
		extract($list);

		unset($list['obj_key']);
		unset($list['obj_val']);

		ci()->load->model($model_name);

		$catalog = ci()->$model_name->catalog();

		$options = [];

		foreach ($catalog as $obj) {
			$options[$obj->$obj_key] = $obj->$obj_val;
		}

		echo self::element_rtn('select', $list).self::dropdown_options($selected, $options).'</select>';
	}

	static public function smart_hidden($name, $value, $model_name = '', $extra = []) {
		$defaults = ['column' => 'name'];
		$list = array_merge($defaults, $extra);
		extract($list);

		ci()->load->model($model_name);

		$catalog = ci()->$model_name->catalog();

		echo self::hidden($name, $catalog[$value]->$column, $extra);
	}

	static public function smart_model($name,$id,$field,$return=false) {
		$name = str_replace('_model','',$name).'_model';
		ci()->load->model($name);

		$records = ci()->$name->catalog();

		$html = (is_object($records[$id])) ? $records[$id]->$field : $records[$id][$field];

		if ($return) {
			return $html;
		} else {
			echo self::e($html);
		}
	}

	static public function smart_model_list($name,$key,$field) {
		$name = str_replace('_model','',$name).'_model';
		ci()->load->model($name);

		$records = ci()->$name->catalog();

		$list = [];

		foreach ($records as $idx => $record) {
			if ((int)$record->is_deleted == 0) {
				$list[$record->$key] = $record->$field;
			}
		}

		return $list;
	}

	static public function dropdown_options($value, $options, $extra) {
		$html = '';

		if ($extra['empty']) {
			$html .= '<option value="" selected>&nbsp;</option>';
		}

		$key_value = $extra['key_value'];
		$value_value = $extra['value_value'];

		foreach ((array) $options as $key => $val) {
			/* only a single value so it's a key value pair */
			if (!is_object($val)) {
				$k = $key;
				$v = $val;
			} else {
				/* it's either a object or array - convert it to a array */
				$val = (array) $val;

				/* get our values */
				$k = $val[$key_value];
				$v = $val[$value_value];
			}

			$selected = (($k == $value || $v == $value) && $extra['empty'] == FALSE) ? ' selected' : '';
			$html .= '<option'.$selected.' value="'.self::escape($k).'">'.self::escape($v).'</option>';
		}

		return $html;
	}

	static public function websafe($copy) {
		return trim(strtolower(preg_replace("/[^0-9a-zA-Z]/",'-',$copy)),'-');
	}

	static public function filesafe($copy) {
		return trim(strtolower(preg_replace("/[^0-9a-zA-Z]/",'_',$copy)),'_');
	}

	static public function element_rtn($a, $list, $b = false) {
		$html = '<'.$a.' '.self::attributes($list).'>';

		return ($b !== FALSE) ? $html.$b.'</'.$a.'>' : $html;
	}

	static public function element($a, $list, $b = false) {
		echo self::element_rtn($a, $list, $b);
	}

	static public function attributes($list = []) {
		$attr = '';

		foreach ($list as $name => $value) {
			if (!empty($value) || $name == 'value') {
				if ($name == 'id') {
					$value = self::websafe($value);
				}

				$attr .= $name.'="'.trim($value).'" ';
			}
		}

		return trim($attr);
	}

	static public function escape($str = '', $is_textarea = false) {
		if (is_array($str)) {
			foreach (array_keys($str) as $key) {
				$str[$key] = self::escape($str[$key], $is_textarea);
			}

			return $str;
		}

		if ($is_textarea === TRUE) {
			return str_replace(['<', '>'], ['&lt;', '&gt;'], stripslashes($str));
		}

		return str_replace(["'", '"'], ['&#39;', '&quot;'], stripslashes($str));
	}

	/* format a date */
	static public function date($date,$format='F j, Y, g:ia') {
		if (is_numeric($date)) { /* is it a timestamp? */
			$date_string = date($format, $date);
		} elseif (is_a($value, 'MongoDate')) { /* is it a mongo date built in PHP object */
			$date_string = date($format, $mongo_obj->sec);
		} else { /* guess it's a string? */
			$date_string = date($format, strtotime($date));
		}

		echo (date('U', strtotime($date)) < 10) ? '' : $date_string;
	}

	static public function state_options($format = 'a/n', $first_empty = true) {
		$options['l'] = ['al','ak','az','ar','ca','co','ct','de','dc','fl','ga','hi','id','il','in','ia','ks','ky','la','me','md','ma','mi','mn','ms','mo','mt','ne','nv','nh','nj','nm','ny','nc','nd','oh','ok','or','pa','ri','sc','sd','tn','tx','ut','vt','va','wa','wv','wi','wy'];
		$options['a'] = ['AL','AK','AZ','AR','CA','CO','CT','DE','DC','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY'];
		$options['n'] = ['Alabama','Alaska','Arizona','Arkansas','California','Colorado','Connecticut','Delaware','District Of Columbia','Florida','Georgia','Hawaii','Idaho','Illinois','Indiana','Iowa','Kansas','Kentucky','Louisiana','Maine','Maryland','Massachusetts','Michigan','Minnesota','Mississippi','Missouri','Montana','Nebraska','Nevada','New Hampshire','New Jersey','New Mexico','New York','North Carolina','North Dakota','Ohio','Oklahoma','Oregon','Pennsylvania','Rhode Island','South Carolina','South Dakota','Tennessee','Texas','Utah','Vermont','Virginia','Washington','West Virginia','Wisconsin','Wyoming'];

		list($key, $value) = explode('/', $format, 2);

		$dropdown = array_combine($options[$key], $options[$value]);

		if ($first_empty) {
			$dropdown = ['' => ''] + $dropdown;
		}

		return $dropdown;
	}

	/* text based enum */
	static public function enum($value,$string = 'False|True',$delimiter = '|') {
		echo self::internal_enum($string,$value,$delimiter);
	}

	static public function internal_enum($string, $value, $delimiter = '|') {
		$enum = explode($delimiter, $string);

		return $enum[(int)$value];
	}

	/* model value based on primary id */
	static public function model($primary_id,$model,$property='name') {
		$model = str_replace('_model', '', $model).'_model';

		ci()->load->model($model);

		$catalog = ci()->$model->catalog();

		echo $catalog[$primary_id]->$property;
	}

	/* shorten with option to create a link */
	static public function shorten($text,$length=64,$link=false) {
		$text = str_replace([chr(10), chr(13)], '', $text);
		$link = str_replace('{controller_path}', ci()->page->data('controller_path'), $link);

		echo(($link) ? '<a href="'.$link.'">' : '').((strlen($text) > $length) ? self::e(substr($text, 0, $length)).'&hellip;' : self::e($text)).(($link) ? '</a>' : '');
	}
	
	static public function extract_data_uri($html,$just_path=false) {
    $map = [
      'data:image/png;base64'=>'png',
      'data:image/jpg;base64'=>'jpg',
      'data:image/jpeg;base64'=>'jpg',
      'data:image/gif;base64'=>'gif',
    ];

		/* extract all the images into a array */
		$parts = explode('<img src="data',$html);

		foreach ($parts as $part) {
			if (substr($part,0,7) == ':image/') {
				$raw_image = 'data'.substr($part,0,strpos($part,'">'));
        /* data:image/png;base64,iVBORw0KGg.... */
        
        $parts = explode(',',$raw_image);

        $abs_image_path = ROOTPATH.'/public'.setting('paths','WWW Image','/images').'/'.md5($raw_image).'.'.$map[$parts[0]];

        $ifp = fopen($abs_image_path,'wb');
        fwrite($ifp,base64_decode($parts[1]));
        fclose($ifp);

        $www_image_path = str_replace(ROOTPATH.'/public','',$abs_image_path);

  			$html = str_replace($raw_image,$www_image_path,$html);
  		}
		}

		/*
		Just path only works if your $html is a single data:image
		<img src="/images/d908b4c369197c494e18f11ff37f8041.png">
		*/

		if ($just_path) {
			list($start,$html,$end) = explode('"',$html,3);
		}

		return $html;
	}
	
	static public function convert2element($element,$attr=null,$data=null) {
		return '<'.$element.self::convert2attributes($attr).$element.self::convert2data($data).'>';
	}

	static public function convert2data($data) {
		$html = '';
		
		foreach ($data as $k=>$v) {
			if (!empty($k)) {
				$html .= ' data-'.$k.'="'.str_replace('"','&quot;',$v).'"';
			}
		}
	
		return $html.' ';
	}
	
	static public function convert2attributes($attr) {
		$html = '';
		
		foreach ($data as $k=>$v) {
			if (!empty($k)) {
				$html .= ' '.$k.'="'.str_replace('"','&quot;',$v).'"';
			}
		}
	
		return $html.' ';
	}
	
	static public function view_event($controller_path,$tag) {
		ci()->event->trigger('view.'.trim(str_replace('/','.',$controller_path),'.').'.'.$tag);
	}
	
} /* end class */