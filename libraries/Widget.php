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
	public static function show($command, $cache_time = 0, $cache_name = NULL) {
		// Users should be allowed to customize the cache name
		// so they can account for user role, logged in status,
		// or simply be able to easily clear the cache items elsewhere.
		if (empty($cache_name)) {
			$cache_name = 'theme_call_' . md5($command . $params);
		}

		if (!$output = $this->ci->cache->get($cache_name)) {
			$first_space = strpos($command,' ');
		
			$class_method = substr($command,0,$first_space);
			$params = substr($command,$first_space);
		
			list($class, $method) = explode(':', $class_method);

			/* add widget to the begining of the class name */
			$class = 'Widget_'.$class;

			// Since $params is a string, we need to split it into
			// an array of 'key=value' segments
			$parts = explode($params);

			$params = array();

			// Prepare our parameter list to send to the callback
			// by splitting $parts on equal signs.
			foreach ($parts as $part) {
				$p = explode('=', $part);

				if (empty($p[0]) || empty($p[1])) {
					continue;
				}

				$params[$p[0]] = $p[1];
			}

			// Let PHP try to autoload it through any available autoloaders
			// (including Composer and user's custom autoloaders). If we
			// don't find it, then assume it's a CI library that we can reach.
			if (class_exists($class)) {
				$class = new $class();
			} else {
				ci()->load->library($class);
				$class =& ci()->$class;
			}

			if ( ! method_exists($class, $method)) {
				throw new \RuntimeException("Unable to display the Widget at {$class}::{$method}");
			}

			// Call the class with our parameters
			$output = $class->{$method}($params);

			// Cache it
			if ((int)$cache_time > 0) {
				ci()->cache->save($cache_name, $output, (int)$cache_time * 60);
			}
		}

		return $output;
	}

} /* end class */