<?php
class EmailAppModel extends AppModel {
	/**
	 * Behaviors
	 *
	 * @var array
	 */
	public $actsAs = array('Containable');

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
}
?>
