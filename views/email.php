<?php
App::import('View', 'View');

class EmailView extends View {
    /**
     * All available helpers, including plugin helpers
     *
     * @var array
     */
    protected static $_helpers;

    /**
     * Called when member variable is not set, used for
     * lazy loading helpers
     *
     * @param string $name Member variable name
     * @return mixed Variable value
     */
    public function __get($name) {
        if (!isset($this->loaded[$name])) {
            $helpers = $this->_helpers();
            if (isset($helpers[$name])) {
                $helper = $name;
                if (!empty($helpers[$helper])) {
                    $helper = $helpers[$helper] . '.' . $helper;
                }
                $this->loaded = $this->_loadHelpers($this->loaded, (array) $helper);
            }
        }
        $this->{$name} = isset($this->loaded[$name]) ? $this->loaded[$name] : null;
        if (!is_null($this->{$name})) {
            return $this->{$name};
        }
    }

    /**
     * OVERRIDEN to get rid of PHP object references that cause problems when overloading
     * __get()
     *
     * Renders and returns output for given view filename with its
     * array of data.
     *
     * @param string $___viewFn Filename of the view
     * @param array $___dataForView Data to include in rendered view
     * @param boolean $loadHelpers Boolean to indicate that helpers should be loaded.
     * @param boolean $cached Whether or not to trigger the creation of a cache file.
     * @return string Rendered output
     * @access protected
     */
	public function _render($___viewFn, $___dataForView, $loadHelpers = true, $cached = false) {
		$loadedHelpers = array();

		if ($this->helpers != false && $loadHelpers === true) {
			$loadedHelpers = $this->_loadHelpers($loadedHelpers, $this->helpers);
			$helpers = array_keys($loadedHelpers);
			$helperNames = array_map(array('Inflector', 'variable'), $helpers);

			for ($i = count($helpers) - 1; $i >= 0; $i--) {
				$name = $helperNames[$i];
				$helper = $loadedHelpers[$helpers[$i]];

				if (!isset($___dataForView[$name])) {
					${$name} = $helper;
				}
				$this->loaded[$helperNames[$i]] = $helper;
				$this->{$helpers[$i]} = $helper;
			}
			$this->_triggerHelpers('beforeRender');
			unset($name, $loadedHelpers, $helpers, $i, $helperNames, $helper);
		}

		extract($___dataForView, EXTR_SKIP);
		ob_start();

		if (Configure::read() > 0) {
			include ($___viewFn);
		} else {
			@include ($___viewFn);
		}

		if ($loadHelpers === true) {
			$this->_triggerHelpers('afterRender');
		}

		$out = ob_get_clean();
		$caching = (
			isset($this->loaded['cache']) &&
			(($this->cacheAction != false)) && (Configure::read('Cache.check') === true)
		);

		if ($caching) {
			if (is_a($this->loaded['cache'], 'CacheHelper')) {
				$cache =& $this->loaded['cache'];
				$cache->base = $this->base;
				$cache->here = $this->here;
				$cache->helpers = $this->helpers;
				$cache->action = $this->action;
				$cache->controllerName = $this->name;
				$cache->layout = $this->layout;
				$cache->cacheAction = $this->cacheAction;
				$cache->cache($___viewFn, $out, $cached);
			}
		}
		return $out;
	}

    /**
     * Get list of helpers, including plugin helpers
     *
     * @param bool $cache If set to true, use cache to keep track of helpers
     * @return array Helpers, indexed by helper name, and where value may be the name of a plugin
     */
    protected function _helpers($cache = true) {
        if (!isset(static::$helpers) && $cache === true) {
            $helpers = Cache::read('app_helpers');
            if ($helpers !== false) {
                static::$_helpers = $helpers;
            }
        }

        if (!isset(static::$_helpers)) {
            $helpers = array();
            foreach (App::objects('helper', null, false) as $helper) {
                $helpers[$helper] = '';
            }

            foreach(App::objects('plugin') as $plugin) {
                foreach (App::objects('helper', App::pluginPath($plugin) . 'views' . DS . 'helpers', false) as $helper) {
                    $helpers[$helper] = $plugin;
                }
            }

            if ($cache === true) {
                Cache::write('app_helpers', $helpers);
            }

            static::$_helpers = $helpers;
        }
        return static::$_helpers;
    }

    /**
     * Renders a piece of PHP with provided parameters and returns HTML, XML, or any other string.
     *
     * This realizes the concept of Elements, (or "partial layouts")
     * and the $params array is used to send data to be used in the
     * Element.  Elements can be cached through use of the cache key.
     *
     * ### Special params
     *
     * - `cache` - enable caching for this element accepts boolean or strtotime compatible string.
     *   Can also be an array. If `cache` is an array,
     *   `time` is used to specify duration of cache.
     *   `key` can be used to create unique cache files.
     * - `plugin` - Load an element from a specific plugin.
     *
     * @param string $name Name of template file in the/app/views/elements/ folder
     * @param array $params Array of data to be made available to the for rendered
     *    view (i.e. the Element)
     * @return string Rendered Element
     * @access public
     */
	public function element($name, $params = array(), $loadHelpers = false) {
        if (is_file($name)) {
            $file = $name;
			$vars = array_merge($this->viewVars, $params);
			foreach ($this->loaded as $name => $helper) {
				if (!isset($vars[$name])) {
					$vars[$name] =& $this->loaded[$name];
				}
			}
			$element = $this->_render($file, $vars, $loadHelpers);
			if (isset($params['cache']) && isset($cacheFile) && isset($expires)) {
				cache('views' . DS . $cacheFile, $element, $expires);
			}
			return $element;
        }
        return parent::element($name, $params, $loadHelpers);
    }

	/**
	 * Returns layout filename for this template as a string.
	 *
	 * @return string Filename for layout file (.ctp).
	 */
	public function _getLayoutFileName($name = null) {
		return !empty($name) && is_file($name) ? $name : parent::_getLayoutFileName($name);
	}
}
?>
