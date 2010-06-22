<?php
class EmailAttachment extends EmailAppModel {
	/**
	 * belongsTo bindings
	 *
	 * @var array
	 * @access public
	 */
	public $belongsTo = array(
		'Email' => array('className' => 'Email.Email')
	);
}
?>
