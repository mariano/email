<?php
class EmailDestinationFixture extends CakeTestFixture {
	public $name = 'EmailDestination';
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'email_id' => array('type' => 'string', 'length' => 36, 'null' => true),
		'type' => array('type' => 'string', 'length' => 255, 'default' => 'to'),
		'name' => array('type' => 'string', 'length' => 255, 'null' => true),
		'email' => array('type' => 'string', 'length' => 255)
	);
	public $records = array(
		array(
			'id' => '10b63a18-e570-102c-863e-00138fbbb402',
			'email_id' => '5a8f70ed-437c-45c5-ac04-0dc97f000101',
			'type' => 'to',
			'name' => 'Mariano Iglesias',
			'email' => 'mariano@email.com'
		),
		array(
			'id' => 'd6d82490-e60b-102c-9d65-00138fbbb402',
			'email_id' => '44c3eae2-e608-102c-9d65-00138fbbb402',
			'type' => 'to',
			'name' => null,
			'email' => 'simba@email.com'
		),
		array(
			'id' => 'd71a8c0e-e60b-102c-9d65-00138fbbb402',
			'email_id' => '79620a26-e609-102c-9d65-00138fbbb402',
			'type' => 'to',
			'name' => null,
			'email' => 'floo@email.com'
		)
	);
}
?>
