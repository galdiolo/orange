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
			$db_array[$record->name] = convert_to_real($record->value);
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

	/* dynamic add from the "built in" view */
	public function addAction($hex=null) {
		$add = hex2bin($hex);

		list($name,$value,$group,$show_as) = explode(chr(0),$add,4);

		$data = [
			'name'=>$name,
			'value'=>$value,
			'group'=>$group,
			'enabled'=>1,
			'managed'=>1, /* code added = managed */
			'show_as'=>$show_as,
			'is_deletable'=>1, /* but they can delete it again */
		];

		$this->output->json('err',$this->o_setting_model->insert($data,'insert'));
	}

	/* used on the /admin/configure/setting/group/menubar view */
	static public function looper($all,$which) {
		$overridden_icon = '<i class="fa fa-exchange"></i>';
		$controller_path = ci()->page->data('controller_path');
		$inp = $all[$which];

		if (count($inp) > 0) {
			echo '<table class="table table-condensed" style="margin:0">';
			foreach ($inp as $name => $value) {

				$show_as = 0; /* text area default */
				$overridden = '&nbsp;';
				$link = '&nbsp;';

				switch ($which) {
					case 'db':
						if ($all['db'][$name] != $all['env'][$name] && isset($all['env'][$name])) {
							$overridden = $overridden_icon;
						}
						if ($all['db'][$name] != $all['file'][$name] && isset($all['db'][$name])) {
							$overridden = $overridden_icon;
						}
					break;
					case 'env':
						if ($all['env'][$name] != $all['file'][$name] && isset($all['file'][$name])) {
							$overridden = $overridden_icon;
						}
					break;
					case 'file':
						if ($all['file'][$name] != $all['env'][$name] && isset($all['env'][$name])) {
							$overridden = $overridden_icon;
						}
					break;
				}

				$group = ci()->uri->segment(5);

				switch(gettype($value)) {
					case 'string';
					case 'integer';
					case 'null';
					case 'float';
					break;
					case 'boolean';
						$show_as = 1; /* true / false radio's */
					break;
				}

				if (!ci()->o_setting_model->compound_key_exists($name,$group) && $which == 'merged') {
					$hash = bin2hex($name.chr(0).convert_to_string($value).chr(0).$group.chr(0).$show_as);
					$link = '<a class="js-add-link" href="'.$controller_path.'/add/'.$hash.'"><i class="fa fa-plus-square"></i></a>';
				}

				echo '<tr>';
				echo '<td width="47%">'.$name.'&nbsp;</td>';
				echo '<td style="width:47%;">'.theme::format_value($value).'</td>';
				echo '<td style="width:3%; text-align:center">'.$link.'</td>';
				echo '<td style="width:3%; text-align:center">'.$overridden.'</td>';
				echo '</tr>';
			}
			echo '</table>';
		}
	}

} /* end settings */