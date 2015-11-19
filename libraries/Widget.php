<?php

class Widget {
	/**
   * Runs a callback method and returns the contents to the view, allowing
   * you to create re-usable, cacheable "widgets" for your views.
   *
   * Example:
   *     Widgets::show('blog/posts:list limit=5 sort=publish_on dir=desc');
   *
   * @param string $command
   * @param string $params
   * @param int $cache_time		// Number of MINUTES to cache output
   * @param string $cache_name
   * @return mixed|void
   */
	public static function show($command) {
		// Users should be allowed to customize the cache name
		// so they can account for user role, logged in status,
		// or simply be able to easily clear the cache items elsewhere.
		if (empty($cache_name)) {
			$cache_name = 'widget_' . md5($command);
		}

		if (!$output = ci()->cache->get($cache_name)) {
			$command = substr($command,1,-1);
			$first_space = strpos($command,' ');

			list($class, $method) = explode(':',substr($command,0,$first_space));

			$params = new SimpleXMLElement('<element '.substr($command,$first_space + 1).' />');
			$params = (array)$params;

			/* add widget to the begining of the class name */
			$classname = 'Widget_'.$class;

			// Let PHP try to autoload it through any available autoloaders
			// (including Composer and user's custom autoloaders). If we
			// don't find it, then assume it's a CI library that we can reach.
			if (class_exists($classname)) {
				$obj = new $classname();
			} else {
				ci()->load->library($classname);

				$obj =& ci()->$classname;
			}

			if (!method_exists($obj, $method)) {
				return 'can\'t find '.$class.':'.$method;
			}

			// Call the class with our parameters
			$output = $obj->{$method}($params['@attributes']);

			// Cache it
			if ((int)$params['@attributes']['cache'] > 0) {
				ci()->cache->save($cache_name, $output, (int)$params['@attributes']['cache']);
			}
		}

		return $output;
	}

} /* end class */