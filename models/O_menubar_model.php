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
class o_menubar_model extends Database_model {
	protected $table = 'orange_nav';
	protected $rules = [
		'id'             => ['field' => 'id','label' => 'Id','rules' => 'required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'created_on'     => ['field' => 'created_on','label' => 'Created On','rules' => 'if_empty[now(Y-m-d H:i:s)]|required|max_length[24]|valid_datetime|filter_input[24]'],
		'created_by'     => ['field' => 'created_by','label' => 'Created By','rules' => 'if_empty[user()]|required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'created_ip'     => ['field' => 'created_ip','label' => 'Created IP','rules' => 'if_empty[ip()]|required|filter_input[16]'],
		'updated_on'     => ['field' => 'updated_on','label' => 'Updated On','rules' => 'if_empty[now(Y-m-d H:i:s)]|required|max_length[24]|valid_datetime|filter_input[24]'],
		'updated_by'     => ['field' => 'updated_by','label' => 'Updated By','rules' => 'if_empty[user()]|required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'updated_ip'     => ['field' => 'updated_ip','label' => 'Updated IP','rules' => 'if_empty[ip()]|required|filter_input[16]'],
		'is_editable'    => ['field' => 'is_editable','label' => 'Editable','rules' => 'if_empty[1]|one_of[0,1]|filter_int[1]|max_length[1]'],
		'is_deletable'   => ['field' => 'is_deletable','label' => 'Deletable','rules' => 'if_empty[1]|one_of[0,1]|filter_int[1]|max_length[1]'],
		'access_id'      => ['field' => 'access_id','label' => 'Access Id','rules' => 'required|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'url'            => ['field' => 'url','label' => 'Url','rules' => 'required|filter_uri[255]|max_length[255]|filter_input[255]|strtolower'],
		'text'           => ['field' => 'text','label' => 'Text','rules' => 'required|max_length[255]|filter_input[255]'],
		'parent_id'      => ['field' => 'parent_id','label' => 'Parent Id','rules' => 'if_empty[0]|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'sort'           => ['field' => 'sort','label' => 'Sort','rules' => 'if_empty[0]|integer|max_length[10]|less_than[4294967295]|filter_int[10]'],
		'class'          => ['field' => 'class','label' => 'Class','rules' => 'filter_input[32]'],
		'active'         => ['field' => 'active','label' => 'Active','rules' => 'if_empty[0]|one_of[0,1]|filter_int[1]|max_length[1]|less_than[2]'],
		'color'          => ['field' => 'color','label' => 'Color','rules' => 'if_empty[d28445]|filter_hex[6]|max_length[7]|filter_input[7]'],
		'icon'           => ['field' => 'icon','label' => 'Icon','rules' => 'if_empty[square]|max_length[32]|filter_input[32]'],
		'internal'       => ['field' => 'internal','label' => 'Internal','rules' => 'filter_input[255]'],
		'target'         => ['field' => 'target','label' => 'Target','rules' => 'filter_input[128]'],
	];
	protected $rule_sets = [
		'insert'          => 'created_on,created_by,created_ip,updated_on,updated_by,updated_ip,access_id,is_editable,is_deletable,url,text,parent_id,sort,class,active,color,icon,internal,target',
		'update'          => 'id,updated_on,updated_by,updated_ip,access_id,url,text,parent_id,sort,class,active,color,icon,target',
		'update_on_order' => 'sort,parent_id',
	];
	public $sort = 10;

	public function get_menus($access = null) {
		if (!is_array($access)) {
			$access[0] = 0; /* everyone */
		}

		$menus = [];

		if ($access != NULL) {
			$key = $this->cache_prefix.'_'.o::filesafe(ci()->user->role_name);

			if (!$cache = ci()->cache->get($key)) {
				$cache = [];

				$results = $this->order_by('sort')->get_many_by('active', 1);

				foreach ((array) $results as $row) {
					$cache[$row->id] = $row;
				}

				ci()->cache->save($key, $cache);
			}
			
			/* does the user have access to this menu? */
			foreach ($cache as $id => $rec) {
				/* access is the complete array */
				if (in_array($rec->access_id, $access)) {
					$menus[$rec->id] = $rec;
				}
			}
		}

		return $menus;
	}

	public function get_menus_by_parent_id($parent_id, $menus = []) {
		$result = [];

		foreach ($menus as $id => $rec) {
			if ($rec->parent_id == $parent_id) {
				$result[$id] = $rec;
			}
		}

		return $result;
	}

	public function get_menus_ordered_by_parent_ids($user_access) {
		$key = $this->cache_prefix.'_access_menu_'.md5(serialize($user_access));
		
		if (!$result = ci()->cache->get($key)) {
			$all_menus = ci()->o_menubar_model->get_menus($user_access);
	
			$result = [];
	
			foreach ($all_menus as $id => $rec) {
				$result[$rec->parent_id][$rec->id] = $rec;
			}
			
			ci()->cache->save($key, $result);
		}

		return $result;
	}
	
	/* make sure there is a empty placeholder record if the table is complete empty */
	public function test_internal_placeholder() {
		$records = $this->get_many();

		if (count($records) == 0) {
			$this->insert(['parent_id'=>0,'url'=>'','text'=>'New Menu Placeholder','icon'=>'eye-slash','is_deletable'=>0,'active'=>0],true);
		}

		$record = $this->get(1);

		return ($record->internal !== 'new menu placeholder') ? true : false;
	}

	public function orderNodes($orders, $parent_id) {
		foreach ($orders as $order) {
			$this->sort = $this->sort + 10;

			$this->update($order['id'], ['sort' => $this->sort, 'parent_id' => $parent_id], 'update_on_order');

			if (isset($order['children'])) {
				$this->orderNodes($order['children'], $order['id']);
			}
		}

		$this->flush_caches();
	}

	public function has_childern($id) {
		$results = $this->_database->get_where($this->table,['parent_id'=>$id]);

		return (count($results->result()) > 0);
	}

} /* end class */