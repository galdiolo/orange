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

class moduleController extends APP_AdminController {
	public $controller = 'module';
	public $controller_path = '/admin/configure/module';
	public $controller_title = 'Module';
	public $controller_titles = 'Modules';
	public $libraries = ['module_install','module_core'];
	public $has_access = 'Orange::Manage Modules';
	public $modules = [];

	public function __construct() {
		parent::__construct();

		/* used on list view and detail view */
		$this->page->data('typer',function($type) {
			$module_type = strtolower($type);
			switch ($module_type) {
				case 'library':
				case 'libraries':
					echo ' <span class="label label-success">'.$module_type.'</span>';
				break;
				case 'theme':
					echo ' <span class="label label-warning">'.$module_type.'</span>';
				break;
				case 'module':
					echo ' <span class="label label-primary">'.$module_type.'</span>';
				break;
				case 'plugin':
					echo ' <span class="label label-info">'.$module_type.'</span>';
				break;
				case 'assets':
					echo ' <span class="label label-warning">'.$module_type.'</span>';
				break;
				default:
					echo ' <span class="label label-default">'.$module_type.'</span>';
			}
		});

		if ($reply = $this->module_core->init(ROOTPATH,APPPATH) !== true) {
			show_error($reply);
		}
	}

	public function indexAction($filter=null) {
		if ($filter) {
			$this->input->is_valid('alpha',$filter);
		}

		$this->page
			->js('/themes/orange/assets/js/module-index.js')
			->data(['records'=>$this->module_core->details(),'filter'=>$filter])
			->build();
	}

	public function installAction($name=null) {
		$this->_process($name,'install');

		redirect($this->controller_path);
	}

	public function upgradeAction($name=null) {
		$this->_process($name,'upgrade');

		redirect($this->controller_path);
	}

	public function uninstallAction($name=null) {
		$this->_process($name,'uninstall');

		redirect($this->controller_path);
	}

	public function deleteAction($name=null) {
		$this->_process($name,'delete');

		redirect($this->controller_path);
	}

	public function uploadAction() {
		$this->load->helper('file_helper');
		$bytes = 4 * 1024 * 1024;

		$this->page
			->data('bytes',$bytes)
			->js('/themes/orange/assets/js/module-upload.js')
			->build();
	}

	/* refactor */
	public function uploadPostAction() {
		/* they need to attach the filename to a header variable */
		$filename = (isset($_SERVER['HTTP_X_FILENAME']) ? $_SERVER['HTTP_X_FILENAME'] : false);

		$json = ['error'=>true,'msg'=>'Upload Error'];

		if ($filename) {
			if (substr($filename,-4) != '.zip') {
				$json = ['error'=>true,'msg'=>'Unknown File Type'];
			} else {
				$new_filename = ROOTPATH.'/var/upload_temp/'.md5($filename.mt_rand(0,999999).microtime());
				file_put_contents($new_filename,file_get_contents('php://input'));
				$json = ['error'=>false,'msg'=>'File <strong>'.$filename.'</strong> Uploaded'];

				/* post process */
				$success = $this->module_core->unzip_n_move($new_filename);

				if ($success !== true) {
					$json = ['error' => true,'msg' => $success];
				}
			}
		}

		$this->output->json($json);
	}

	/* old */
	public function _uploadPostAction() {
		$config['upload_path'] = ROOTPATH.'/var/upload_temp/';
		$config['allowed_types'] = 'zip';
		$config['max_size'] = 8192;

		$this->load->library('upload',$config);

		if (!$this->upload->do_upload()) {
			$json = [
				'error' => $this->upload->display_errors(),
				'msg'=>'Error Uploading',
			];
		} else {
			$upload = $this->upload->data();

			$json = [
				//'success' => $this->upload->data()
				'redirect'=>true,
				'msg'=>'Uploaded',
			];

			$success = $this->module_core->unzip_n_move($upload);

			if ($success !== true) {
				$json = [
					'error' => $this->upload->display_errors(),
					'msg' => $success,
				];
			}
		}

		$this->output->json($json);
	}

	/* handle dynamic module help loading */
	public function detailsAction($module='') {
		$this->page
			->data(['module'=>$this->module_core->get_by('classname',hex2bin($module))])
			->build('admin/configure/module/details');
	}

/*
deprecated this shouldn't even work it's missing Action and it's not called here?
	public function unzip_n_move($upload) {
		return parent::unzip_n_move($upload);
	}
*/
	
	/* composer actions */
	public function composerAction() {
		$composer = file_get_contents(ROOTPATH.'/composer.json');

		$this->page->data(['composer'=>$composer])->build();
	}

	public function composer_savePostAction() {
		$composer = $this->input->post('composer');

		$success = (bool)file_put_contents(ROOTPATH.'/composer.json',trim($composer));

		$json = [
			'error' => $success,
			'msg' => '',
		];

		/* do we need a flash msg? */
		if ($this->input->post('is_redirecting') == 'true') {
			$this->wallet->blue('Saved composer.json');
		}

		$this->output->json($json);
	}

	public function composer_ajaxAction() {
		error_reporting(-1);
		ini_set('display_errors', 1);

		putenv('COMPOSER_HOME='.ROOTPATH.'/vendor/bin/composer');
		chdir(ROOTPATH);

		$cli = '"'.ROOTPATH.'/vendor/composer/composer/bin/composer" update -d "'.ROOTPATH.'"';

		$proc = proc_open($cli,[1 => ['pipe','w'],2 => ['pipe','w']],$pipes);

		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		$output = proc_close($proc);

		echo $stdout.$stderr;
	}
	
	/* git clone actions */
	public function cloneAction() {
		$this->page->build();
	}

	public function clone_savePostAction() {
		$tmpfname = tempnam(ROOTPATH.'/var/upload_temp','');

		if (file_exists($tmpfname)) {
			$temp = basename($tmpfname);
			unlink($tmpfname);
		}

		$cli = 'cd '.ROOTPATH.'/var/upload_temp;'.$this->input->post('command').' "'.$temp.'"';

		$proc = proc_open($cli,[1 => ['pipe','w'],2 => ['pipe','w']],$pipes);

		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		$output = proc_close($proc);

		/* does it have the install file? */
		$file = glob(ROOTPATH.'/var/upload_temp/'.$temp.'/install_*.php');

		if (count($file) == 1) {
			$err = false;

			$module_folder_name = substr(basename($file[0],'.php'),8);

			$current = dirname($file[0]);
			$new = dirname($current).'/'.$module_folder_name;

			rename($current,$new);

			$this->module_core->smart_move($new);
		} else {
			$err = true;
		}

		$this->output->json(['err'=>$err,'msg'=>$cli.chr(10).$stdout.$stderr.$output]);
	}

	public function onloadAction() {
		$autoload = $this->module_core->get_modules_config();

		$records = array_unique($autoload['public_onload']+$autoload['admin_onload']);

		$this->page->data(['records'=>$records,'public'=>$autoload['public_onload'],'admin'=>$autoload['admin_onload']])->build();
	}

	public function onload_savePostAction() {
		$public = [];
		$admin = [];

		$checkboxes = $this->input->post('checkboxes');

		foreach ($checkboxes as $c=>$dummy) {
			if (substr($c,0,7) == 'public_') {
				$public[] = hex2bin(substr($c,7));
			} elseif(substr($c,0,6) == 'admin_') {
				$admin[] = hex2bin(substr($c,6));
			}
		}

		/* !todo move this to module_core */

		/* load the current */
		include APPPATH.'/config/modules.php';

		/* clean out what's there */
		$autoload['public_onload'] = [];
		$autoload['admin_onload'] = [];

		/* add the currently checked */
		foreach ($public as $p) {
			$autoload['public_onload'][] = $p;
		}

		foreach ($admin as $a) {
			$autoload['admin_onload'][] = $a;
		}

		$this->module_core->write(APPPATH.'/config/modules.php',$autoload);

		$this->wallet->success('Saved');

		$this->output->json('err',false);
	}

	protected function _process($name,$method) {
		$map = ['install'=>'installed','uninstall'=>'uninstalled','delete'=>'deleted','upgrade'=>'upgraded'];

		$name = hex2bin($name);

		$this->cache->clean();

		if ($reply = $this->module_core->$method($name) !== true) {
			$this->wallet->failed($reply);
			return false;
		}

		$this->wallet->success('Module "'.$name.'" '.$map[$method].'.');

		$this->auth->refresh_userdata();

		return true;
	}

} /* end class */