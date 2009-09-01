<?php
App::import('View', 'View');

class EmailView extends View {
	/**
	 * Returns layout filename for this template as a string.
	 *
	 * @return string Filename for layout file (.ctp).
	 * @access public
	 */
	public function _getLayoutFileName($name = null) {
		return !empty($name) && is_file($name) ? $name : parent::_getLayoutFileName($name);
	}
}
?>
