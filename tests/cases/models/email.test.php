<?php
App::import('Model', array('Email.Email'));

class TestEmail extends Email {
	public $useDbConfig = 'test_suite';
	public $useTable = 'emails';
	protected $testMail;
	protected $requestedAction;

	public function run($method) {
		$args = array_slice(func_get_args(), 1);
		return call_user_method_array($method, $this, $args);
	}

	public function schedule($id) {
		return true;
	}

	protected function mail($mail) {
		$this->testMail = false;
		if (empty($mail)) {
			return false;
		}
		$this->testMail = $mail;
		return true;
	}

	public function requestAction($url, $extra = array()) {
		$this->requestedAction = $url;
		return true;
	}

	public function testSentEmail() {
		$mail = $this->testMail;
		$this->testMail = false;
		return $mail;
	}

	public function testRequestedAction() {
		$action = $this->requestedAction;
		$this->requestedAction = false;
		return $action;
	}
}

class EmailTest extends CakeTestCase {
	public $fixtures = array(
		'plugin.email.email_attachment', 'plugin.email.email_destination', 'plugin.email.email_template', 'plugin.email.email'
	);

	public function startTest($method) {
		$this->Email = ClassRegistry::init('TestEmail');
		Configure::delete('Email');
	}

	public function endTest($method) {
		unset($this->Email);
		ClassRegistry::flush();
	}

	public function testCompress() {
		if ($this->skipIf(!App::import('Syrup.Compressible'), 'Syrup.Compressible behavior is not accessible')) {
			return;
		}

		$result = $this->Email->find('first', array('conditions' => array('id' => '5a8f70ed-437c-45c5-ac04-0dc97f000101'), 'recursive' => -1));
		$this->assertTrue(!empty($result));
		$result = array_values(array_filter(array_map('trim', split("\n", strip_tags($result[$this->Email->alias]['html'])))));
		$expected = array(
			'Dear Mariano Iglesias,',
			'We\'d like to welcome you to My Website.',
			'Click here to edit your profile: http://localhost/profiles/edit'
		);
		$this->assertEqual($result, $expected);

		$this->Email->Behaviors->disable('Compressible');
		$result = $this->Email->find('first', array('conditions' => array('id' => '5a8f70ed-437c-45c5-ac04-0dc97f000101'), 'recursive' => -1));
		$this->Email->Behaviors->enable('Compressible');
		$this->assertTrue(!empty($result));
		$result[$this->Email->alias]['html'] = $this->Email->uncompress($result[$this->Email->alias]['html']);

		$this->assertTrue(!empty($result));
		$result = array_values(array_filter(array_map('trim', split("\n", strip_tags($result[$this->Email->alias]['html'])))));
		$expected = array(
			'Dear Mariano Iglesias,',
			'We\'d like to welcome you to My Website.',
			'Click here to edit your profile: http://localhost/profiles/edit'
		);
		$this->assertEqual($result, $expected);
	}

	public function testReplace() {
		$result = $this->Email->EmailTemplate->replace(
			'Your name is ${name}',
			array(
				'name' => 'Mariano'
			)
		);
		$expected = 'Your name is Mariano';
		$this->assertEqual($result, $expected);

		$result = $this->Email->EmailTemplate->replace(
			'Your name is ${NAME}',
			array(
				'name' => 'Mariano'
			)
		);
		$expected = 'Your name is Mariano';
		$this->assertEqual($result, $expected);

		$result = $this->Email->EmailTemplate->replace(
			'Your name is ${NAME}, ${Name}, and ${namE}',
			array(
				'name' => 'Mariano'
			)
		);
		$expected = 'Your name is Mariano, Mariano, and Mariano';
		$this->assertEqual($result, $expected);

		$result = $this->Email->EmailTemplate->replace('URL: ${url(/profiles/view/4)}');
		$expected = 'URL: ' . Router::url('/profiles/view/4', true);
		$this->assertEqual($result, $expected);

		$result = $this->Email->EmailTemplate->replace(
			'Your name is ${NAME}. Your age is ${profile.age} and you have ${comment.new.count} new comments',
			array(
				'name' => 'Mariano',
				'profile' => array('age' => 30),
				'comment' => array(
					'new' => array('type' => 'new', 'count' => 7),
					'made' => array('type' => 'made', 'count' => 15),
				)
			)
		);
		$expected = 'Your name is Mariano. Your age is 30 and you have 7 new comments';
		$this->assertEqual($result, $expected);
	}

	public function testValidateLayout() {
		$result = $this->Email->EmailTemplate->validateEmailLayout(array('layout' => null));
		$this->assertFalse($result);

		$result = $this->Email->EmailTemplate->validateEmailLayout(array('layout' => 'non_existing_layout'));
		$this->assertFalse($result);

		$result = $this->Email->EmailTemplate->validateEmailLayout(array('layout' => 'default'));
		$this->assertTrue($result);

		$data = array('EmailTemplate' => array(
			'layout' => ''
		));
		$this->Email->EmailTemplate->create($data);
		$result = $this->Email->EmailTemplate->validates();
		$this->assertTrue(empty($this->Email->EmailTemplate->validationErrors['layout']));
	}

	public function testLayouts() {
		$layouts = $this->Email->EmailTemplate->layouts();
		$this->assertTrue(in_array('default', $layouts));
	}

	public function testRender() {
		$id = '44c3eae2-e608-102c-9d65-00138fbbb402';
		$result = $this->Email->render($id);
		$this->assertTrue(!empty($result));
		$this->assertEqual($result['from']['name'], 'site@email.com');
		$this->assertEqual($result['from']['email'], 'site@email.com');
		$this->assertEqual($result['subject'], 'Welcome to our site');
		$this->assertTrue(empty($result['text']));
		$result = array_values(array_filter(array_map('trim', split("\n", strip_tags($result['html'])))));
		$expected = array(
			'Dear Simba Iglesias,',
			'We\'d like to welcome you to My Cat Website.',
			'Click here to edit your profile: ' . Router::url('/profiles/edit', true)
		);
		$this->assertEqual($result, $expected);
		$result = $this->Email->find('first', array(
			'conditions' => array($this->Email->alias . '.' . $this->Email->primaryKey => $id),
			'recursive' => -1
		));
		$this->assertTrue(!empty($result[$this->Email->alias]['from_name']));
		$this->assertTrue(!empty($result[$this->Email->alias]['from_email']));
		$this->assertTrue(!empty($result[$this->Email->alias]['subject']));
		$this->assertTrue(!empty($result[$this->Email->alias]['html']));

		$id = '79620a26-e609-102c-9d65-00138fbbb402';
		$result = $this->Email->render($id);
		$this->assertTrue(!empty($result));
		$this->assertEqual($result['from']['name'], 'site@email.com');
		$this->assertEqual($result['from']['email'], 'site@email.com');
		$this->assertEqual($result['subject'], 'Welcome to My Second Cat Website!');
		$this->assertTrue(empty($result['text']));
		$result = array_values(array_filter(array_map('trim', split("\n", strip_tags($result['html'])))));
		$expected = array(
			'Dear Floo Iglesias,',
			'We\'d like to welcome you to My Second Cat Website.',
			'Click here to edit your profile: ' . Router::url('/profiles/edit', true)
		);
		$this->assertEqual($result, $expected);
		$result = $this->Email->find('first', array(
			'conditions' => array($this->Email->alias . '.' . $this->Email->primaryKey => $id),
			'recursive' => -1
		));
		$this->assertTrue(!empty($result[$this->Email->alias]['from_name']));
		$this->assertTrue(!empty($result[$this->Email->alias]['from_email']));
		$this->assertTrue(!empty($result[$this->Email->alias]['subject']));
		$this->assertTrue(!empty($result[$this->Email->alias]['html']));
	}

	public function testSendNow() {
		$id = '44c3eae2-e608-102c-9d65-00138fbbb402';
		$result = $this->Email->sendNow($id);
		$this->assertTrue($result);

		$result = $this->Email->testSentEmail();
		$this->assertTrue(!empty($result));
		$this->assertEqual($result['from']['name'], 'site@email.com');
		$this->assertEqual($result['from']['email'], 'site@email.com');
		$this->assertEqual($result['subject'], 'Welcome to our site');
		$this->assertEqual(count($result['destinations']), 1);
		$this->assertEqual($result['destinations'][0]['type'], 'to');
		$this->assertEqual($result['destinations'][0]['name'], 'simba@email.com');
		$this->assertEqual($result['destinations'][0]['email'], 'simba@email.com');
		$this->assertTrue(empty($result['text']));
		$result = array_values(array_filter(array_map('trim', split("\n", strip_tags($result['html'])))));
		$expected = array(
			'Dear Simba Iglesias,',
			'We\'d like to welcome you to My Cat Website.',
			'Click here to edit your profile: ' . Router::url('/profiles/edit', true)
		);
		$this->assertEqual($result, $expected);
	}

	public function testSend() {
		$id = $this->Email->send('signup', array(
			'to' => 'claudia@email.com',
			'name' => 'Claudia Mansilla',
			'site' => 'Test CakePHP Site'
		));
		$this->assertTrue(!empty($id));
		$result = $this->Email->testSentEmail();
		$this->assertTrue(empty($result));
		$result = $this->Email->sendNow($id);
		$this->assertTrue($result);
		$result = $this->Email->testSentEmail();
		$this->assertTrue(!empty($result));
		$this->assertEqual($result['from']['name'], 'site@email.com');
		$this->assertEqual($result['from']['email'], 'site@email.com');
		$this->assertEqual($result['subject'], 'Welcome to our site');
		$this->assertEqual(count($result['destinations']), 1);
		$this->assertEqual($result['destinations'][0]['type'], 'to');
		$this->assertEqual($result['destinations'][0]['name'], 'Claudia Mansilla');
		$this->assertEqual($result['destinations'][0]['email'], 'claudia@email.com');
		$this->assertTrue(empty($result['text']));
		$result = array_values(array_filter(array_map('trim', split("\n", strip_tags($result['html'])))));
		$expected = array(
			'Dear Claudia Mansilla,',
			'We\'d like to welcome you to Test CakePHP Site.',
			'Click here to edit your profile: ' . Router::url('/profiles/edit', true)
		);
		$this->assertEqual($result, $expected);

		$id = $this->Email->send('signup', array(
			'to' => 'jose@email.com',
			'name' => 'Jose Iglesias',
			'subject' => 'Welcome to New CakePHP Site',
			'site' => 'New CakePHP Site'
		), true);
		$this->assertTrue(!empty($id));
		$result = $this->Email->testSentEmail();
		$this->assertTrue(!empty($result));
		$this->assertEqual($result['from']['name'], 'site@email.com');
		$this->assertEqual($result['from']['email'], 'site@email.com');
		$this->assertEqual($result['subject'], 'Welcome to New CakePHP Site');
		$this->assertEqual(count($result['destinations']), 1);
		$this->assertEqual($result['destinations'][0]['type'], 'to');
		$this->assertEqual($result['destinations'][0]['name'], 'Jose Iglesias');
		$this->assertEqual($result['destinations'][0]['email'], 'jose@email.com');
		$this->assertTrue(empty($result['text']));
		$result = array_values(array_filter(array_map('trim', split("\n", strip_tags($result['html'])))));
		$expected = array(
			'Dear Jose Iglesias,',
			'We\'d like to welcome you to New CakePHP Site.',
			'Click here to edit your profile: ' . Router::url('/profiles/edit', true)
		);
		$this->assertEqual($result, $expected);

		$id = $this->Email->send('signup_with_layout', array(
			'to' => 'nicolas@email.com',
			'name' => 'Nicolas Iglesias',
			'subject' => 'Welcome to Layout CakePHP Site',
			'site' => 'Layout CakePHP Site'
		), true);
		$this->assertTrue(!empty($id));
		$result = $this->Email->testSentEmail();
		$this->assertTrue(!empty($result));
		$this->assertEqual($result['from']['name'], 'layout@email.com');
		$this->assertEqual($result['from']['email'], 'layout@email.com');
		$this->assertEqual($result['subject'], 'Welcome to Layout CakePHP Site');
		$this->assertEqual(count($result['destinations']), 1);
		$this->assertEqual($result['destinations'][0]['type'], 'to');
		$this->assertEqual($result['destinations'][0]['name'], 'Nicolas Iglesias');
		$this->assertEqual($result['destinations'][0]['email'], 'nicolas@email.com');
		$this->assertTrue(empty($result['text']));
		$this->assertPattern('/<html[^>]*>.+<\/html>/si', $result['html']);
		$this->assertPattern('/<head[^>]*>.+<\/head>/si', $result['html']);
		$result = array_values(array_filter(array_map('trim', split("\n", strip_tags(preg_replace('/<head[^>]*>.+<\/head>/si', '', $result['html']))))));
		$expected = array(
			'Dear Nicolas Iglesias,',
			'We\'d like to welcome you to Layout CakePHP Site.',
			'Click here to login: ' . Router::url('/users/login', true)
		);
		foreach($expected as $line) {
			$this->assertTrue(in_array($line, $result));
		}

		$result = $this->Email->find('first', array(
			'conditions' => array($this->Email->alias . '.' . $this->Email->primaryKey => $id),
			'recursive' => -1
		));
		$this->assertTrue(!empty($result));
		$this->assertEqual($result[$this->Email->alias]['failed'], 0);
	}

	public function testSendNoTemplate() {
		$id = $this->Email->send(array(
			'from' => 'site@email.com',
			'to' => 'jose@email.com',
			'subject' => 'Welcome to New CakePHP Site',
			'html' => '<p>Dear Jose Iglesias,</p>
			<p>We\'d like to welcome you to our new site.</p>
			<p>Click here to edit your profile: ${url(/profiles/edit)}</p>'
		), true);
		$this->assertTrue(!empty($id));
		$result = $this->Email->testSentEmail();
		$this->assertTrue(!empty($result));
		$this->assertEqual($result['from']['name'], 'site@email.com');
		$this->assertEqual($result['from']['email'], 'site@email.com');
		$this->assertEqual($result['subject'], 'Welcome to New CakePHP Site');
		$this->assertEqual(count($result['destinations']), 1);
		$this->assertEqual($result['destinations'][0]['type'], 'to');
		$this->assertEqual($result['destinations'][0]['name'], 'jose@email.com');
		$this->assertEqual($result['destinations'][0]['email'], 'jose@email.com');
		$this->assertTrue(empty($result['text']));
		$this->assertNoPattern('/<title[^>]*>.+?<\/title>/i', $result['html']);
		$result = array_values(array_filter(array_map('trim', split("\n", strip_tags($result['html'])))));
		$expected = array(
			'Dear Jose Iglesias,',
			'We\'d like to welcome you to our new site.',
			'Click here to edit your profile: ' . Router::url('/profiles/edit', true)
		);
		$this->assertEqual($result, $expected);

		$id = $this->Email->send(array(
			'from' => 'site@email.com',
			'to' => 'jose@email.com',
			'subject' => 'Welcome to New CakePHP Site',
			'layout' => 'default',
			'html' => '<p>Dear Jose Iglesias,</p>
			<p>We\'d like to welcome you to our new site.</p>
			<p>Click here to edit your profile: ${url(/profiles/edit)}</p>'
		), true);
		$this->assertTrue(!empty($id));
		$result = $this->Email->testSentEmail();
		$this->assertTrue(!empty($result));
		$this->assertEqual($result['from']['name'], 'site@email.com');
		$this->assertEqual($result['from']['email'], 'site@email.com');
		$this->assertEqual($result['subject'], 'Welcome to New CakePHP Site');
		$this->assertEqual(count($result['destinations']), 1);
		$this->assertEqual($result['destinations'][0]['type'], 'to');
		$this->assertEqual($result['destinations'][0]['name'], 'jose@email.com');
		$this->assertEqual($result['destinations'][0]['email'], 'jose@email.com');
		$this->assertTrue(empty($result['text']));
		$this->assertPattern('/<title[^>]*>.+?<\/title>/i', $result['html']);
		$result = array_values(array_filter(array_map('trim', split("\n", strip_tags(preg_replace('/<head[^>]*>.+<\/head>/si', '', $result['html']))))));
		$expected = array(
			'Dear Jose Iglesias,',
			'We\'d like to welcome you to our new site.',
			'Click here to edit your profile: ' . Router::url('/profiles/edit', true)
		);
		foreach($expected as $line) {
			$this->assertTrue(in_array($line, $result));
		}
	}

	public function testCallback() {
		$id = $this->Email->send(array(
			'from' => 'site@email.com',
			'to' => 'jose@email.com',
			'subject' => 'Welcome to New CakePHP Site',
			'html' => '<p>Dear Jose Iglesias,</p>
			<p>We\'d like to welcome you to our new site.</p>
			<p>Click here to edit your profile: ${url(/profiles/edit)}</p>'
		), true);
		$this->assertTrue(!empty($id));
		$result = $this->Email->testSentEmail();
		$this->assertTrue(!empty($result));
		$result = $this->Email->testRequestedAction();
		$this->assertTrue(empty($result));

		$id = $this->Email->send(array(
			'callback' => '/emails/sent',
			'from' => 'site@email.com',
			'to' => 'jose@email.com',
			'subject' => 'Welcome to New CakePHP Site',
			'html' => '<p>Dear Jose Iglesias,</p>
			<p>We\'d like to welcome you to our new site.</p>
			<p>Click here to edit your profile: ${url(/profiles/edit)}</p>',
		), true);
		$this->assertTrue(!empty($id));
		$result = $this->Email->testSentEmail();
		$this->assertTrue(!empty($result));
		$result = $this->Email->testRequestedAction();
		$this->assertEqual($result, '/emails/sent');

		$id = $this->Email->send(array(
			'callback' => '/emails/sent/${id}',
			'from' => 'site@email.com',
			'to' => 'jose@email.com',
			'subject' => 'Welcome to New CakePHP Site',
			'html' => '<p>Dear Jose Iglesias,</p>
			<p>We\'d like to welcome you to our new site.</p>
			<p>Click here to edit your profile: ${url(/profiles/edit)}</p>',
		), true);
		$this->assertTrue(!empty($id));
		$result = $this->Email->testSentEmail();
		$this->assertTrue(!empty($result));
		$result = $this->Email->testRequestedAction();
		$this->assertEqual($result, '/emails/sent/' . $id);

		$id = $this->Email->send(array(
			'callback' => array('controller' => 'emails', 'action' => 'sent', '${id}'),
			'from' => 'site@email.com',
			'to' => 'jose@email.com',
			'subject' => 'Welcome to New CakePHP Site',
			'html' => '<p>Dear Jose Iglesias,</p>
			<p>We\'d like to welcome you to our new site.</p>
			<p>Click here to edit your profile: ${url(/profiles/edit)}</p>',
		), true);
		$this->assertTrue(!empty($id));
		$result = $this->Email->testSentEmail();
		$this->assertTrue(!empty($result));
		$result = $this->Email->testRequestedAction();
		$this->assertEqual($result, '/emails/sent/' . $id);

		$id = $this->Email->send(array(
			'callback' => array('controller' => 'emails', 'action' => 'sent', '${id}', '${success}'),
			'from' => 'site@email.com',
			'to' => 'jose@email.com',
			'subject' => 'Welcome to New CakePHP Site',
			'html' => '<p>Dear Jose Iglesias,</p>
			<p>We\'d like to welcome you to our new site.</p>
			<p>Click here to edit your profile: ${url(/profiles/edit)}</p>',
		), true);
		$this->assertTrue(!empty($id));
		$result = $this->Email->testSentEmail();
		$this->assertTrue(!empty($result));
		$result = $this->Email->testRequestedAction();
		$this->assertEqual($result, '/emails/sent/' . $id . '/1');
	}

	public function testI18nTemplate() {
		$id = $this->Email->send('signup', array(
			'to' => 'claudia@email.com',
			'name' => 'Claudia Mansilla',
			'site' => 'Test CakePHP Site'
		));
		$this->assertTrue(!empty($id));
		$result = $this->Email->testSentEmail();
		$this->assertTrue(empty($result));
		$result = $this->Email->sendNow($id);
		$this->assertTrue($result);
		$result = $this->Email->testSentEmail();
		$this->assertTrue(!empty($result));
		$this->assertEqual($result['from']['name'], 'site@email.com');
		$this->assertEqual($result['from']['email'], 'site@email.com');
		$this->assertEqual($result['subject'], 'Welcome to our site');
		$this->assertEqual(count($result['destinations']), 1);
		$this->assertEqual($result['destinations'][0]['type'], 'to');
		$this->assertEqual($result['destinations'][0]['name'], 'Claudia Mansilla');
		$this->assertEqual($result['destinations'][0]['email'], 'claudia@email.com');
		$this->assertTrue(empty($result['text']));
		$result = array_values(array_filter(array_map('trim', split("\n", strip_tags($result['html'])))));
		$expected = array(
			'Dear Claudia Mansilla,',
			'We\'d like to welcome you to Test CakePHP Site.',
			'Click here to edit your profile: ' . Router::url('/profiles/edit', true)
		);
		$this->assertEqual($result, $expected);
	}
}
?>
