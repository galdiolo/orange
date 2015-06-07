<?php

class bootstrap_menu {
	
	static public function left() {
		$start_at = 0;
		$filter_empty = true;
		
		/* first we get all menus this user has access to */
		$all_menus = ci()->o_menubar_model->get_menus(array_keys(ci()->user->access));

		/* then we build the menus array */
		$menus = ci()->o_menubar_model->get_menus_ordered_by_parent_ids($all_menus);

		$new_menus = [];

		if (is_array($menus)) {
			foreach ($menus[$start_at] as $key => $item) {
				$new_menus[$key]['class'] = $item->class;
				$new_menus[$key]['href']  = rtrim($item->url, '/#');
				$new_menus[$key]['text']  = $item->text;
				$new_menus[$key]['color'] = $item->color;
				$new_menus[$key]['icon'] = $item->icon;

				if (isset($menus[$key])) {
					/* has children */
					foreach ($menus[$key] as $key2 => $item2) {
						$new_menus[$key]['childern'][$key2]['class'] = $menus[$key][$key2]->class;
						$new_menus[$key]['childern'][$key2]['href']  = rtrim($menus[$key][$key2]->url, '/');
						$new_menus[$key]['childern'][$key2]['text']  = $menus[$key][$key2]->text;
						$new_menus[$key]['childern'][$key2]['icon']  = $menus[$key][$key2]->icon;
						$new_menus[$key]['childern'][$key2]['color']  = $menus[$key][$key2]->color;
					}
				}
			}
		}

    /* filter out empty or menu items without urls */
    if ($filter_empty) {
	    foreach ($new_menus as $idx=>$menu) {
	      if (count($menu['childern']) == 0 && $menu['href'] == '') {
	        unset($new_menus[$idx]);
	      }
	    }
		}
		
		$left_navigation_menu = self::build_twitter_bootstrap_menu($new_menus);

		ci()->event->trigger('menubar.left_navigation_menu',$left_navigation_menu,$start_at,$access,$filter_empty);
		
		echo $left_navigation_menu;
	}

	static public function right() {
		/* Example: <li><a href="#"><i class="fa fa-envelope"></i> <span class="badge">42</span></a></li> */
		$right_navigation_menu = '';
		
		ci()->event->trigger('menubar.right_navigation_menu',$right_navigation_menu,$start_at,$access,$filter_empty);

		echo $right_navigation_menu;
	}

	static public function user() {
		$childern = (array)setting('menubar','Childern User Menus');

		$user_navigation_menu = '';

		foreach ($childern as $c) {
			$user_navigation_menu .= '<li class="dropdown-header">'.ci()->user->$c.'</li>';
		}
		
		if (count($childern)) {
			$user_navigation_menu .= '<li class="divider"></li>';
		}

		$user_navigation_menu .= '<li class=""><a href="/" target="_blank">View Site</a></li>';
		$user_navigation_menu .= '<li class=""><a href="/orange/logout">Logout</a></li>';

		ci()->event->trigger('menubar.user_menu',$user_navigation_menu,$start_at,$access,$filter_empty);

		echo $user_navigation_menu;
	}
	
	static public function user_text() {
		$root = setting('menubar','Root User Menu','username');
		
		echo ci()->user->$root;
	}
	
	/* complete */
	static public function nav() {
		echo '<nav class="navbar navbar-'.setting('menubar','Inverse Menubar','inverse').' navbar-fixed-top">
			<div class="container">
				<div class="navbar-header"><button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
						<span class="sr-only">Toggle</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button>';

		if (ci()->user->is_active) {
			echo '<a class="navbar-brand" title="Project Orange Box" href="'.setting('auth','URL Dashboard').'"><img src="/themes/orange/assets/images/box.png" width="32" height="32" style="top:4px;position:relative;"></a>';
		}

		echo '</div><div id="navbar" class="navbar-collapse collapse"><ul class="nav navbar-nav">';

		if (ci()->user->is_active) {
			self::left();
		}
		
		echo '</ul><ul class="nav navbar-nav navbar-right">';
		
		if (ci()->user->is_active) {
			self::right();
			echo '<li class="dropdown"><a href="#" class="dropdown-toggle gravatar" data-toggle="dropdown" role="button" aria-expanded="false">';
			self::user_text();
			echo '<span class="caret"></span></a><ul class="dropdown-menu" role="menu">';
			self::user();
			echo '</ul></li>';
		}
	
		echo '</ul></div></div></nav>';	
	}
	
	static protected function build_twitter_bootstrap_menu($menu) {
		$html = '';

		if (is_array($menu)) {
			foreach ($menu as $item) {
				if (isset($item['childern'])) {
					/* has children */
					$html .= '<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">';
					$html .= $item['text'].' <b class="caret"></b></a><ul class="dropdown-menu">';
					foreach ($item['childern'] as $row) {
						$html .= '<li><a data-color="'.$row['color'].'" data-icon="'.$row['icon'].'" class="'.$row['class'].'" href="'.$row['href'].'">'.$row['text'].'</a></li>';
					}
					$html .= '</ul></li>';
				} else {
					/* no children */
					$html .= '<li><a class="'.$item['class'].'" href="'.$item['href'].'">'.$item['text'].'</a></li>';
				}
			}
		}

		return $html;
	}

} /* end class */