<?php
class EmailAttachmentFixture extends CakeTestFixture {
	public $name = 'EmailAttachment';
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'email_id' => array('type' => 'string', 'length' => 36),
		'file' => array('type' => 'string', 'length' => 255),
	);
}
?>
