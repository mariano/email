<?php
class EmailTemplateFixture extends CakeTestFixture {
	public $name = 'EmailTemplate';
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'key' => array('type' => 'string', 'length' => 255),
		'from_name' => array('type' => 'string', 'length' => 255, 'null' => true),
		'from_email' => array('type' => 'string', 'length' => 255, 'null' => true),
		'subject' => array('type' => 'string', 'length' => 255),
		'layout' => array('type' => 'string', 'length' => 255, 'null' => true),
		'html' => array('type' => 'binary', 'null' => true),
		'text' => array('type' => 'binary', 'null' => true)
	);
	public $records = array(
		array(
			'id' => '4a8f70ed-437c-45c5-ac04-0dc97f000101',
			'key' => 'signup',
			'from_name' => null,
			'from_email' => 'site@email.com',
			'subject' => 'Welcome to our site',
			'layout' => null,
			'html' => '
				<p>Dear ${name},</p>
				<p>We\'d like to welcome you to ${site}.</p>
				<p><a href="${url(/profiles/edit)}">Click here to edit your profile: ${url(/profiles/edit)}</a></p>
			',
			'text' => null
		),
		array(
			'id' => '86071d92-e60f-102c-9d65-00138fbbb402',
			'key' => 'signup_with_layout',
			'from_name' => null,
			'from_email' => 'layout@email.com',
			'subject' => 'Welcome to ${site}',
			'layout' => 'default',
			'html' => '
				<p>Dear ${name},</p>
				<p>We\'d like to welcome you to ${site}.</p>
				<p><a href="${url(/users/login)}">Click here to login: ${url(/users/login)}</a></p>
			',
			'text' => null
		),
		array(
			'id' => 'f13f5408-6f6d-11df-a4bb-002618f2d9f9',
			'key' => 'signup_school',
			'from_name' => null,
			'from_email' => 'layout@email.com',
			'subject' => 'Welcome to ${school}',
			'layout' => 'default',
			'html' => '
				<p>Dear ${name},</p>
				<p>We\'d like to welcome you to ${school}.</p>
				<p><a href="${url(/users/login)}">Click here to login: ${url(/users/login)}</a></p>
				<p>${message}</p>
			',
			'text' => '
				Dear ${name},
				We\'d like to welcome you to ${school}.
				Click here to login: ${url(/users/login)}
				${message}
			'
		)
	);
}
?>