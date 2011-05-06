<?php
class EmailFixture extends CakeTestFixture {
	public $name = 'Email';
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'email_template_id' => array('type' => 'string', 'length' => 36, 'null' => true),
		'template' => array('type' => 'string', 'length' => 255, 'null' => true),
		'from_name' => array('type' => 'string', 'length' => 255, 'null' => true),
		'from_email' => array('type' => 'string', 'length' => 255),
		'subject' => array('type' => 'string', 'length' => 255),
		'variables' => array('type' => 'binary', 'null' => true),
		'html' => array('type' => 'binary', 'null' => true),
		'text' => array('type' => 'binary', 'null' => true),
		'attachments' => array('type' => 'text', 'null' => true),
		'queued' => array('type' => 'datetime'),
		'processed' => array('type' => 'datetime', 'null' => true),
		'failed' => array('type' => 'integer', 'default' => 0),
		'sent' => array('type' => 'datetime', 'null' => true)
	);
	public $records = array(
		array(
			'id' => '5a8f70ed-437c-45c5-ac04-0dc97f000101',
			'email_template_id' => '4a8f70ed-437c-45c5-ac04-0dc97f000101',
			'from_name' => null,
			'from_email' => 'test@email.com',
			'subject' => 'Welcome to our site',
			'variables' => array(
				'from' => array('email' => 'test@email.com'),
				'to' => 'mariano@email.com',
				'name' => 'Mariano Iglesias',
				'site' => 'My Website'
			),
			'html' => '
				<p>Dear Mariano Iglesias,</p>
				<p>We\'d like to welcome you to My Website.</p>
				<p><a href="http://localhost/profiles/edit">Click here to edit your profile: http://localhost/profiles/edit</a></p>
			',
			'text' => null,
			'attachments' => null,
			'queued' => '2009-08-27 22:51:14',
			'sent' => '2009-08-27 22:52:14'
		),
		array(
			'id' => '44c3eae2-e608-102c-9d65-00138fbbb402',
			'email_template_id' => '4a8f70ed-437c-45c5-ac04-0dc97f000101',
			'from_name' => null,
			'from_email' => null,
			'subject' => null,
			'variables' => array(
				'to' => 'simba@email.com',
				'name' => 'Simba Iglesias',
				'site' => 'My Cat Website'
			),
			'html' => null,
			'text' => null,
			'attachments' => null,
			'queued' => '2009-08-27 22:51:14',
			'sent' => null
		),
		array(
			'id' => '79620a26-e609-102c-9d65-00138fbbb402',
			'email_template_id' => '4a8f70ed-437c-45c5-ac04-0dc97f000101',
			'from_name' => null,
			'from_email' => null,
			'subject' => null,
			'variables' => array(
				'to' => 'floo@email.com',
				'subject' => 'Welcome to My Second Cat Website!',
				'name' => 'Floo Iglesias',
				'site' => 'My Second Cat Website'
			),
			'html' => null,
			'text' => null,
			'attachments' => null,
			'queued' => '2009-08-27 22:51:14',
			'sent' => null
		)
	);

	/**
	 * Initialize the fixture. Overriden to gz compress content
	 *
	 * @param object	Cake's DBO driver (e.g: DboMysql).
	 * @access public
	 */
	public function init() {
		foreach($this->records as $i => $record) {
			foreach(array('variables', 'html', 'text') as $field) {
				if (!isset($record[$field])) {
					continue;
				} else if (is_array($record[$field])) {
					$record[$field] = serialize($record[$field]);
				}
				$this->records[$i][$field] = $this->compress($record[$field]);
			}
		}
		return parent::init();
	}

	/**
	 * Compress data using zlib in a format compatible with MySQL's COMPRESS() function.
	 *
	 * @url http://dev.mysql.com/doc/refman/5.0/en/encryption-functions.html#function_compress
	 * @param $data Data to compress.
	 * @return string Compressed data
	 * @access private
	 */
	private function compress($data) {
		if (!empty($data)) {
			// MySQL requires the compressed data to start with a 32-bit little-endian integer of the original length of the data
			$data = pack('V', strlen($data)) . gzcompress($data, 9);
			// If the compressed data ends with a space, MySQL adds a period
			if (substr($data, -1) == ' ' ) {
				$data .= '.';
			}
		}
		return $data;
	}
}
?>
