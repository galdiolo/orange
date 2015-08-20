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

class settingController extends APP_AdminController {
	public $controller = 'setting';
	public $controller_title = 'Setting';
	public $controller_titles = 'Settings';
	public $controller_path = '/admin/configure/setting';
	public $controller_model = 'o_setting_model';
	public $libraries = 'plugin_combobox'; /* orange required */
	public $has_access = 'Orange::Manage Settings';

	public function indexAction() {
		$records = $this->o_setting_model->index('group,name');
		$records = $this->_format_tabs($records,'group');

		$this->page
			->js('/themes/orange/assets/js/settings.min.js')
			->data(['advanced'=>$this->session->userdata('setting-advanced'),'records'=>$records])
			->build();
	}

	public function newAction($advanced=null) {
		if ($advanced == 'advanced' && has_access('Orange::Advanced Settings')) {
			$this->page->data('advanced',true);
		}

		$this->page->js('/themes/orange/assets/js/settings.min.js');

		parent::newAction();
	}

	public function newPostAction() {
		$this->load->settings_flush();

		if ($this->access['create']) {
			$this->has_access($this->access['create']);
		}

		$this->_get_data('insert');
		
		if ($id = $this->o_setting_model->insert($this->data, 'insert')) {
			$this->wallet->created($this->content_title, $this->controller_path);
		}

		$this->wallet->failed($this->content_title, $this->controller_path);
	}

	public function editAction($id = null,$advanced = null) {
		if ($advanced == 'advanced' && has_access('Orange::Advanced Settings')) {
			$this->page->data('advanced',true);
		}

		$this->page->js('/themes/orange/assets/js/settings.min.js');

		parent::editAction($id);
	}

	public function editPostAction() {
		$this->load->settings_flush();

		if ($this->access['update']) {
			$this->has_access($this->access['update']);
		}

		$this->input->is_valid($this->o_setting_model->rules['id']['rules'], '@id');

		$this->_get_data('update');

		if ($this->o_setting_model->update($this->data['id'], $this->data, 'update')) {
			$this->wallet->updated($this->content_title, $this->controller_path);
		}

		$this->wallet->failed($this->content_title, $this->controller_path);
	}

	public function deleteAction($id=null) {
		/* flush all cached settings */
		$this->load->settings_flush();

		parent::deleteAction($id);
	}

	public function list_allAction() {
		$files = glob(APPPATH.'config/*');

		foreach ($files as $file) {
			if (is_file($file)) {

				/* unset config variables */
				$config = null;

				/* load a "set" config variable if it exsits */
				include $file;

				if (isset($config) && is_array($config)) {
					$name = basename($file, '.php');
					$records[$name] = (object)['name'=>$name];
				}
			}
		}

		$this->page->data('records',$records)->build();
	}

	public function groupAction($which = null) {
		/* load file based */
		$this->load->config($which, true, true);

		$app_file = APPPATH.'config/'.$which.'.php';

		if (file_exists($app_file)) {
			include APPPATH.'config/'.$which.'.php';
			$file_array = (array) $config;
			unset($config);
		}

    $db_array = $this->o_setting_model->get_many_by(['enabled'=>1,'group'=>$which]);

    foreach ($db_array as $idx=>$record) {
      unset($db_array[$idx]);
      $db_array[$record->name] = $this->_format_setting($record->value);
    }

		$merged = array_merge($file_array, $db_array);

		$env_array = [];

		if (CONFIG) {
			$env_file = APPPATH.'config/'.CONFIG.'/'.$which.'.php';
	
			if (file_exists($env_file)) {
				include APPPATH.'config/'.CONFIG.'/'.$which.'.php';
				$env_array = (array) $config;
				unset($config);
			}
		}

		$this->page
			->js('/themes/orange/assets/js/settings.js')
			->data([
				'controller_titles' => 'Complete Settings for "'.$which.'"',
				'which' => $which,
				'all' => ['merged'=>$merged,'env'=>$env_array,'file'=>$file_array,'db'=>$db_array],
			])
			->build();
	}

	protected function _format_setting($value) {
		/* is it JSON? if not this will return null */
		$value = @json_decode($value, true);

		if ($value === null) {
			switch(trim(strtolower($value))) {
				case 'true':
					$value = true;
				break;
				case 'false':
					$value = false;
				break;
				case 'null':
					$value = null;
				break;
				default:
					if (is_numeric($value)) {
						$value = (is_float($value)) ? (float)$value : (int)$value;
					}
			}
		}
		
		return $value;
	}

	/* dynamic add from the "built in" view */
	public function addAction($hex=null) {
		$add = hex2bin($hex);

		list($name,$value,$group,$show_as) = explode(chr(0),$add,4);

		$data = [
			'name'=>$name,
			'value'=>$value,
			'group'=>$group,
			'enabled'=>1,
			'managed'=>0,
			'show_as'=>$show_as,
			'is_deletable'=>1,
		];

		$this->output->json('err',$this->o_setting_model->insert($data,'insert'));
	}

	/* used on the /admin/configure/setting/group/menubar view */
	static public function looper($all,$which) {
		$inp = $all[$which];

		if (count($inp) > 0) {
			echo '<table class="table table-condensed" style="margin:0">';
			foreach ($inp as $name => $value) {

				$show_as = 0; /* text area default */
				$overridden = null;

				switch ($which) {
					case 'db':
						if ($all['env'][$name] != $all['db'][$name] && isset($all['env'][$name])) {
							$overridden = '<i class="fa fa-exchange"></i>';
						}
					break;
					case 'env':
						if ($all['file'][$name] != $all['env'][$name] && isset($all['file'][$name])) {
							$overridden = '<i class="fa fa-exchange"></i>';
						}
					break;
					case 'file':
					break;
				}

				$html = self::style_type($value,$show_as,$overridden);
				$group = ci()->uri->segment(5);
				$link = '&nbsp;';

				if (!ci()->o_setting_model->compound_key_exists($name,$group)) {
					$link = ($add_link) ? '<a class="js-add-link" href="'.ci()->page->data('controller_path').'/add/'.bin2hex($name.chr(0).$value.chr(0).$group.chr(0).$show_as).'"><i class="fa fa-plus-square"></i></a>' : '&nbsp;';
				}

				echo '<tr><td>'.$name.'&nbsp;</td><td style="width:20%">'.$html.'</td><td>'.$link.'</td></tr>';
			}
			echo '</table>';
		}
	}
	
	/* make the setting values "pretty" */
	static public function style_type(&$value='',&$show_as=0,$overridden=null) {
		$overridden = ($overridden) ? ' '.$overridden : '';

		if (is_array($value)) {
			$html = htmlentities(var_export($value,true));
			$value = json_encode($value);
		} elseif (is_numeric($value)) {
			$html = '<span class="label label-warning">'.$value.$overridden.'</span>';
			$show_as = 3; /* single line text input */
		} elseif (is_integer($value)) {
			$html = '<span class="label label-info">'.$value.$overridden.'</span>';
			$show_as = 3; /* single line text input */
		} elseif (is_bool($value)) {
			$str = ($value) ? 'True' : 'False';
			$color = ['True'=>'success','False'=>'danger'];
			$html = '<span class="label label-'.$color[$str].'">'.$str.$overridden.'</span>';
			$value = strtolower($str);
			$show_as = 1; /* true / false radio's */
		} elseif (strtolower($value) == 'true') {
			$html = '<span class="label label-success">True'.$overridden.'</span>';
			$value = strtolower($value);
			$show_as = 1; /* true / false radio's */
		} elseif (strtolower($value) == 'false') {
			$html = '<span class="label label-danger">False'.$overridden.'</span>';
			$value = strtolower($value);
			$show_as = 1; /* true / false radio's */
		} else {
			/* shorten it first */
			$hellip = (strlen($value) > 128) ? '&hellip;' : '';

			$html = htmlentities(substr($value,0,128)).$hellip;
		}
		
		return $html;
	}

} /* end settings */