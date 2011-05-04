<?php
class EmailTemplateForI18nFixture extends CakeTestFixture {
	public $name = 'EmailTemplateForI18n';
    public $table = 'email_template_for_i18n';
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'key' => array('type' => 'string', 'length' => 255),
		'from_name' => array('type' => 'string', 'length' => 255, 'null' => true),
		'from_email' => array('type' => 'string', 'length' => 255, 'null' => true),
		'layout' => array('type' => 'string', 'length' => 255, 'null' => true)
	);
	public $records = array(
		array(
			'id' => '4a8f70ed-437c-45c5-ac04-0dc97f000101',
			'key' => 'signup',
			'from_name' => null,
			'from_email' => 'site@email.com',
			'layout' => null
		),
		array(
			'id' => '86071d92-e60f-102c-9d65-00138fbbb402',
			'key' => 'signup_with_layout',
			'from_name' => null,
			'from_email' => 'layout@email.com',
			'layout' => 'default'
		),
		array(
			'id' => 'f13f5408-6f6d-11df-a4bb-002618f2d9f9',
			'key' => 'signup_school',
			'from_name' => null,
			'from_email' => 'layout@email.com',
			'layout' => 'default',
		)
	);
}
?>
