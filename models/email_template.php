<?php
App::import('Core', 'Router');

class EmailTemplateI18n extends EmailAppModel {
    public $useTable = 'email_template_i18n';
    public $displayField = 'field';
}

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

	public function __construct($id = false, $table = null, $ds = null) {
        $i18n = Configure::read('Email.i18n');
        if (!empty($i18n)) {
            if (!is_array($i18n)) {
                $i18n = array('enabled' => !empty($i18n));
            } else if (Set::numeric($i18n)) {
                $i18n = array('enabled' => true, 'fields' => $i18n);
            }

            $i18n = Set::merge(array(
                'enabled' => false,
                'fields' => array('subject', 'html', 'text'),
                'model' => 'EmailTemplateI18n',
                'table' => 'email_template_i18n'
            ), $i18n);

            if ($i18n['enabled']) {
                $this->translateModel = $i18n['model'];
                $this->translateTable = $i18n['table'];
                $this->actsAs['Translate'] = $i18n['fields'];
            }
        }
        parent::__construct($id, $table, $ds);
    }

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
		$layoutPaths = array('email' . DS . 'text', 'email' . DS . 'html');
		if (!empty($type)) {
			$layoutPaths = array('email' . DS . $type);
		}

		$viewPaths = App::path('views');
		$pluginPaths = App::path('plugins');
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

		$layoutPaths = array('email' . DS . 'text', 'email' . DS . 'html');
		if (!empty($type)) {
			$layoutPaths = array('email' . DS . $type);
		}

		$viewPaths = App::path('views');
		$pluginPaths = App::path('plugins');
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
