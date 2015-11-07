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

class menubarController extends APP_AdminController {
	public $controller = 'menubar';
	public $controller_path = '/admin/configure/menubar';
	public $controller_model = 'o_menubar_model';
	public $controller_title = 'Root Menu';
	public $controller_titles = 'Menus';
	public $libraries = 'plugin_nestable';
	public $has_access = 'Orange::Manage Menubar';

	public function __construct() {
		parent::__construct();
		
		if (setting('menubar','Show Color',false) && $this->load->library_exists('plugin_colorpicker')) {
			$this->load->library('plugin_colorpicker');
		}

		if (setting('menubar','Show Icon',false) && $this->load->library_exists('plugin_fontawesome')) {
			$this->load->library('plugin_fontawesome');
		}
	}

	public function indexAction() {
		$this->page
			->data([
				'nestable_handler' => $this->controller_path,
				'tree' => $this->make($this->o_menubar_model->order_by('sort')->get_many()),
				'parent_options' => [0 => '<i class="fa fa-upload"></i>'] + $this->o_menubar_model->catalog('id', 'text'),
			])
			->build();
	}

	public function listAction() {
		$this->load->library('plugin_search_sort');

		$this->page
			->data('records',$this->o_menubar_model->index('url,sort'))
			->build('/admin/configure/menubar/list');
	}

	public function sortPostAction() {
		/* returned error doesn't actually work at this time */
		$this->o_menubar_model->orderNodes($this->input->post('order'), 0);
		$this->output->json('err', false);
	}

	/* used to dynamically load a view */
	public function recordAction($id = null) {
		$this->input->is_valid('is_a_id', $id);

		$this->page
			->template('_templates/orange_blank')
			->data('record',$this->o_menubar_model->get($id))
			->build('/admin/configure/menubar/view');
	}

	/* used to show the new record form */
	public function newAction($parent_id = 0, $parent_text = 'Root') {
		$this->input->is_valid('is_a_id', $parent_id)->is_valid('is_a_str', $parent_text);

		$title = 'New Menu';
		$parent_text = urldecode($parent_text);
		
		if (!empty($parent_text)) {
			$title .= ' Under &ldquo;'.$parent_text.'&rdquo;';		
		}

		$this->page
			->data([
				'controller_title' => $title,
				'controller_action' => 'new',
				'record' => (object) ['id' => -1, 'active' => 1, 'parent_id' => $parent_id, 'sort' => 4294967294],
			])
			->build($this->controller_path.'/form');
	}

	public function newPostAction() {
		$this->_get_data('insert');

		if ($id = $this->o_menubar_model->insert($this->data, false)) {
			$this->wallet->created($this->content_title,$this->controller_path);
		}

		$this->wallet->failed($this->content_title,$this->controller_path);
	}


	public function editAction($id = null,$advanced = null) {
		if ($advanced == 'advanced' && has_access('Orange::Advanced Menubar')) {
			$this->page->data('advanced',true);
		}

		$this->page->data('return_to',$this->input->server('HTTP_REFERER'));

		$record = $this->o_menubar_model->get($id);
		$catalog = $this->o_menubar_model->catalog();
		
		$title = 'Edit &ldquo;'.$record->text.'&rdquo;';
		
		if (!empty($catalog[$record->parent_id]->text)) {
			$title .= ' Menu Under &ldquo;'.$catalog[$record->parent_id]->text.'&rdquo;';		
		}
				
		$data = [
			'controller_title' => $title,
			'record' => $record,
			'controller_action' => 'edit',
		];

		$this->page
			->data($data)
			->build($this->controller_path.'/form');
	}

	public function editPostAction() {
		$this->_get_data('update');

		$return_to = (!empty($this->input->post('return_to'))) ? $this->input->post('return_to') : $this->controller_path;

		if ($this->o_menubar_model->update($this->data['id'], $this->data, false)) {
			$this->wallet->updated($this->content_title,$return_to);
		}

		$this->wallet->failed($this->content_title,$return_to);
	}

	public function deleteAction($id=null) {
		/* if somebody is sending in bogus id's send them to a fiery death */
		$this->input->is_valid('is_a_id', $id);

		/* reassign the childern to the placeholder menu */
		$has_childern = $this->o_menubar_model->has_childern($id);

		if ($has_childern) {
			$responds = ['err'=>true,'msg'=>'This menu item has childern you must move them first before deleteing this record'];
		} else {
			$responds = ['err'=>!$this->o_menubar_model->delete($id)];
		}

		$this->output->json($responds);
	}

	/* internal create the tree */
	protected function make($tree, $parent_id = 0) {
		$child = $this->hasChildren($tree, $parent_id);

		$content = '';

		if (!empty($child)) {
			$content .= '<ol class="dd-list">';

			foreach ($child as $node) {
				$disabled = ($node->active == 0) ? 'text-muted' : '';

				$content .= '<li id="node_'.$node->id.'" class="panel-default  dd-item dd3-item" data-id="'.$node->id.'">';
				$content .= '<div class="btn-primary dd-handle dd3-handle">Drag</div>';
				$content .= '<div class="btn btn-default dd3-content"><span class="'.$disabled.'">'.$node->text.'</span> <small>'.rtrim($node->url,'/#').'</small>';
				$content .= '</div>';
				$content .= $this->make($tree, $node->id);
				$content .= '</li>';
			}

			$content .= '</ol>';
		}

		return $content;
	}

	/* internal */
	protected function hasChildren($tree, $parent_id) {
		return array_filter($tree, function ($var) use ($parent_id) {
			return $var->parent_id == $parent_id;
		});
	}

} /* end controller */
