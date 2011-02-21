<?php
App::import('Core', 'Router');

class EmailTemplate extends EmailAppModel {
	/**
	 * Validation rules
	 *
	 * @var array
	 */
	public $validate = array(
		'key' => array(
			'required' => 'notEmpty',
			'unique' => array('rule' => 'validateUnique', 'field' => 'key')
		),
		'subject' => array(
			'required' => 'notEmpty'
		),
		'layout' => array(
			'valid' => array('allowEmpty' => true, 'rule' => 'validateEmailLayout')
		),
		'html' => array(
			'valid' => array('rule' => array('validateOneNotEmpty', array(
				'html', 'text'
			)))
		)
	);

	/**
	 * Checks if value is a valid email layout
	 *
	 * @param array $value Array in the form of $field => $value
	 * @return bool Success
	 */
	public function validateEmailLayout($value) {
		$value = array_shift($value);
		if (empty($value)) {
			return false;
		}

		return in_array($value, $this->layouts());
	}

	/**
	 * Render content within given layout
	 *
	 * @param string $content Content
	 * @param string $layout Layout name
	 * @param string $type Type (text / html)
	 * @param array $variables Replacement variables
	 * @param array $parameters Parameters (title, webroot)
	 * @return string Content
	 */
	public function renderLayout($content, $layout, $type, $variables = array(), $parameters = array()) {
		$layout = $this->layoutPath($layout, $type);
		if (empty($layout)) {
			return $content;
		}

		if (empty($this->View)) {
			if (!App::import('View', 'Email.Email')) {
				return $content;
			}

			$controller = null;
			$this->View = new EmailView($controller, false);
			$this->View->webroot = !empty($parameters['webroot']) ? $parameters['webroot'] : '/';
		}

		if (isset($parameters['title'])) {
			$this->View->pageTitle = $parameters['title'];
		}

		if (!empty($variables)) {
			$this->View->set($variables);
		}

		return $this->View->renderLayout($content, $layout);
	}

	/**
	 * Look for ${variable} and replace with value
	 *
	 * @param string $content Text to replace
	 * @param array $variables Replacement variables (variable => value)
	 * @param boolean $escape Escape variables
	 * @return string Replaced text
	 */
	public function replace($content, $variables = array(), $escape = false) {
		if (empty($content)) {
			return $content;
		}
    
    $variables = Set::flatten($variables);
    
    /**
     * On the fly profile modifications. If the user is not an admin and
     * the profile has been deleted, disable the URL and alter the name.
     * See Case 3353.
     */
    if( isset( $variables['Trigger.Profile.deleted'] ) && $variables['Trigger.Profile.deleted'] ) {
        if( !Configure::read( 'Account.is_admin' ) ) {
            $variables['Trigger.Profile.url']       = '';
            $variables['Trigger.Profile.firstname'] = 'Inactive';
            $variables['Trigger.Profile.lastname']  = 'User';
            $variables['Trigger.Profile.fullname']  = 'Inactive User';
            $variables['Trigger.Profile.username']  = 'Inactive User';
        } else {
            $variables['Trigger.Profile.fullname'] .= ' (inactive)';
            $variables['Trigger.Profile.username'] .= ' (inactive)';
        }
    }
    
		foreach($variables as $key => $value) {
			unset($variables[$key]);
			$variables[strtolower($key)] = $value;
		}
		$replacementCallbacks = array(
			'/\$\{\s*url\s*\(([^\)]+)\s*\)\s*\}/i' => create_function('$matches', 'return Router::url($matches[1], true);')
		);
    
		foreach($replacementCallbacks as $pattern => $replacement) {
      $content = preg_replace_callback($pattern, $replacement, $content);
		}
    
		if ($escape) {
			foreach($variables as $variable => $value) {
				if (!is_string($value)) {
					continue;
				}

				$value = trim($value);
				if ($escape && !empty($value)) {
					$value = htmlentities($value, ENT_QUOTES, Configure::read('App.encoding'));
					$value = str_replace("\r", "\n", str_replace("\r\n", "\n", $value));
					$value = nl2br($value);
				}
				$variables[$variable] = $value;
			}
		}
    
		if (preg_match_all('/\${(.+?)}/', $content, $matches, PREG_SET_ORDER)) {
			foreach($matches as $i => $match) {
				$variable = strtolower($match[1]);
				$content = str_replace($match[0], isset($variables[$variable]) ? $variables[$variable] : '', $content);
			}
		}
    
		// Quick hack to remove links when the url data isn't available, until we have a proper templating engine
		$content = preg_replace('%<a href="">([^<]+)</a>%', '\1', $content);
		return $content;
	}

	/**
	 * Get layouts that are defined for both html and text
	 *
	 * @return array Layout names
	 */
	public function layouts() {
		$layouts = array();
		$configure = Configure::getInstance();
		$layoutPaths = array('email' . DS . 'text', 'email' . DS . 'html');
		if (!empty($type)) {
			$layoutPaths = array('email' . DS . $type);
		}

		$viewPaths = $configure->viewPaths;
		$pluginPaths = $configure->pluginPaths;
		foreach($pluginPaths as $pluginPath) {
			$viewPaths[] = $pluginPath . 'email' . DS . 'views' . DS;
		}

		foreach($viewPaths as $viewPath) {
			foreach($layoutPaths as $currentLayoutPath) {
				$path = $viewPath . 'layouts' . DS . $currentLayoutPath . DS;
				if (is_dir($path)) {
					$folder = new Folder($path);
					list($dirs, $files) = $folder->read(true, true);
					foreach($files as $file) {
						if (preg_match('/\.ctp$/', $file)) {
							$layouts[] = preg_replace('/\.ctp$/', '', $file);
						}
					}
				}
			}
		}

		return array_unique($layouts);
	}

	/**
	 * Get email layout path for given layout, and given type.
	 *
	 * @param string $layout Layout name
	 * @param string $type Either 'html', or 'text', or null to see if at least one is there
	 * @return string Path, or null if not found
	 */
	public function layoutPath($layout, $type = 'html') {
		$layoutPath = null;

		if (!empty($type)) {
			$type = strtolower($type);
			if (!in_array($type, array('html', 'text'))) {
				return $layoutPath;
			}
		}

		$configure = Configure::getInstance();
		$layoutPaths = array('email' . DS . 'text', 'email' . DS . 'html');
		if (!empty($type)) {
			$layoutPaths = array('email' . DS . $type);
		}

		$viewPaths = $configure->viewPaths;
		$pluginPaths = $configure->pluginPaths;
		foreach($pluginPaths as $pluginPath) {
			$viewPaths[] = $pluginPath . 'email' . DS . 'views' . DS;
		}

		foreach($viewPaths as $viewPath) {
			foreach($layoutPaths as $currentLayoutPath) {
				$candidate = $viewPath . 'layouts' . DS . $currentLayoutPath . DS . $layout . '.ctp';
				if (is_file($candidate)) {
					$layoutPath = $candidate;
					break;
				}
			}
			if (!empty($layoutPath)) {
				break;
			}
		}

		return $layoutPath;
	}
}
?>
