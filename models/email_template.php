<?php
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
			'valid' => array('rule' => array('validateOneNotEmpty', 'fields' => array(
				'html', 'text'
			)))
		)
	);

	/**
	 * Validates that at least one of the given fields is not empty
	 *
	 * @param array $value Array in the form of $field => $value
	 * @param array $parameters Parameters ('fields')
	 * @return bool Success
	 */
	public function validateOneNotEmpty($value, $parameters = array()) {
		reset($value);
		$field = key($value);
		$value = array_shift($value);
		if (empty($parameters['fields'])) {
			$parameters['fields'] = array($field);
		}

		$valid = false;
		foreach((array) $parameters['fields'] as $field) {
			if (!empty($this->data[$this->alias][$field]) && Validation::notEmpty($this->data[$this->alias][$field])) {
				$valid = true;
				break;
			}
		}
		return $valid;
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
	 * Checks if the value defined is unique for the given data model.
	 * The check for uniqueness is case-insensitive.  If
	 * {@link $params}['conditions'] is given, this is used as a constraint.
	 * If {@link $params}['scope'] is given, the value is only checked against
	 * records that match the value of the column/field defined by
	 * {@link $params}['scope'].
	 *
	 * @param array $value Array in the form of $field => $value.
	 * @return bool True if value is unique; false otherwise.
	 * @access public
	 */
	public function validateUnique($value, $params) {
		$value = array_shift($value);
		$column = $this->alias . '.' . $params['field'];
		$id = $this->alias . '.' . $this->primaryKey;

		$conditions = array();
		if (isset($params['conditions'])) {
			$conditions = $params['conditions'];
		}

		if (isset($params['scope'])) {
			if (is_array($params['scope'])) {
				foreach ($params['scope'] as $scope) {
					$conditions[$scope] = $this->data[$this->alias][$scope];
				}
			} else if (is_string($params['scope'])) {
				$conditions[$params['scope']] = $this->data[$this->alias][$params['scope']];
			}
		}
		$conditions[$column] = $value;

		if (isset($this->data[$this->alias][$this->primaryKey])) {
			$conditions[$id . ' !='] = $this->data[$this->alias][$this->primaryKey];
		} else if (!empty($this->id)) {
			$conditions[$id . ' !='] = $this->id;
		}

		return !$this->hasAny($conditions);
	}

	/**
	 * Render content within given layout
	 *
	 * @param string $content Content
	 * @param string $layout Layout name
	 * @param string $type Type (text / html)
	 * @param array $parameters Parameters (title, webroot)
	 * @return string Content
	 */
	public function renderLayout($content, $layout, $type, $parameters = array()) {
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
		return $this->View->renderLayout($content, $layout);
	}

	/**
	 * Look for ${variable} and replace with value
	 *
	 * @param string $content Text to replace
	 * @param array $variables Replacement variables (variable => value)
	 * @return string Replaced text
	 */
	public function replace($content, $variables = array()) {
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

		preg_match_all('/\${(.+?)}/', $content, $matches, PREG_SET_ORDER);
		if (!empty($matches)) {
			foreach($matches as $i => $match) {
				$variable = strtolower($match[1]);
				$content = str_replace($match[0], isset($variables[$variable]) ? $variables[$variable] : '', $content);
			}
		}

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
