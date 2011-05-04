<?php
class EmailTemplateI18nFixture extends CakeTestFixture {
	public $name = 'EmailTemplateI18n';
    public $table = 'email_template_i18n';
	public $fields = array(
        'id' => array('type'=>'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
        'locale' => array('type'=>'string', 'null' => false, 'length' => 6, 'key' => 'index'),
        'model' => array('type'=>'string', 'null' => false, 'key' => 'index'),
        'foreign_key' => array('type'=>'string', 'null' => false, 'length' => 36, 'key' => 'index'),
        'field' => array('type'=>'string', 'null' => false, 'key' => 'index'),
        'content' => array('type'=>'text', 'null' => true, 'default' => NULL),
        'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'locale' => array('column' => 'locale', 'unique' => 0), 'model' => array('column' => 'model', 'unique' => 0), 'row_id' => array('column' => 'foreign_key', 'unique' => 0), 'field' => array('column' => 'field', 'unique' => 0))
	);
	public $records = array(
		array(
            'id' => '907c8e5c-7638-11e0-8e03-0013f78a4bcc',
            'locale' => 'en_us',
            'model' => 'TestEmailTemplateForI18n',
			'foreign_key' => '4a8f70ed-437c-45c5-ac04-0dc97f000101',
            'field' => 'subject',
			'content' => 'Welcome to our site'
		),
		array(
            'id' => '917c8e5c-7638-11e0-8e03-0013f78a4bcc',
            'locale' => 'en_us',
            'model' => 'TestEmailTemplateForI18n',
			'foreign_key' => '4a8f70ed-437c-45c5-ac04-0dc97f000101',
            'field' => 'html',
			'content' => '
				<p>Dear ${name},</p>
				<p>We\'d like to welcome you to ${site}.</p>
				<p><a href="${url(/profiles/edit)}">Click here to edit your profile: ${url(/profiles/edit)}</a></p>
			'
		),
		array(
            'id' => '927c8e5c-7638-11e0-8e03-0013f78a4bcc',
            'locale' => 'en_us',
            'model' => 'TestEmailTemplateForI18n',
			'foreign_key' => '4a8f70ed-437c-45c5-ac04-0dc97f000101',
            'field' => 'text',
			'content' => null
		),
		array(
            'id' => '937c8e5c-7638-11e0-8e03-0013f78a4bcc',
            'locale' => 'en_us',
            'model' => 'TestEmailTemplateForI18n',
			'foreign_key' => '86071d92-e60f-102c-9d65-00138fbbb402',
            'field' => 'subject',
			'content' => 'Welcome to ${site}'
		),
		array(
            'id' => '947c8e5c-7638-11e0-8e03-0013f78a4bcc',
            'locale' => 'en_us',
            'model' => 'TestEmailTemplateForI18n',
			'foreign_key' => '86071d92-e60f-102c-9d65-00138fbbb402',
            'field' => 'html',
			'content' => '
				<p>Dear ${name},</p>
				<p>We\'d like to welcome you to ${site}.</p>
				<p><a href="${url(/users/login)}">Click here to login: ${url(/users/login)}</a></p>
			'
		),
		array(
            'id' => '957c8e5c-7638-11e0-8e03-0013f78a4bcc',
            'locale' => 'en_us',
            'model' => 'TestEmailTemplateForI18n',
			'foreign_key' => '86071d92-e60f-102c-9d65-00138fbbb402',
            'field' => 'text',
			'content' => null
		),
		array(
            'id' => '967c8e5c-7638-11e0-8e03-0013f78a4bcc',
            'locale' => 'en_us',
            'model' => 'TestEmailTemplateForI18n',
			'foreign_key' => 'f13f5408-6f6d-11df-a4bb-002618f2d9f9',
            'field' => 'subject',
			'content' => 'Welcome to ${school}'
		),
		array(
            'id' => '977c8e5c-7638-11e0-8e03-0013f78a4bcc',
            'locale' => 'en_us',
            'model' => 'TestEmailTemplateForI18n',
			'foreign_key' => 'f13f5408-6f6d-11df-a4bb-002618f2d9f9',
            'field' => 'html',
			'content' => '
				<p>Dear ${name},</p>
				<p>We\'d like to welcome you to ${school}.</p>
				<p><a href="${url(/users/login)}">Click here to login: ${url(/users/login)}</a></p>
				<p>${message}</p>
			'
		),
		array(
            'id' => '987c8e5c-7638-11e0-8e03-0013f78a4bcc',
            'locale' => 'en_us',
            'model' => 'TestEmailTemplateForI18n',
			'foreign_key' => 'f13f5408-6f6d-11df-a4bb-002618f2d9f9',
            'field' => 'text',
			'content' => '
				Dear ${name},
				We\'d like to welcome you to ${school}.
				Click here to login: ${url(/users/login)}
				${message}
			'
		),
		array(
            'id' => '997c8e5c-7638-11e0-8e03-0013f78a4bcc',
            'locale' => 'es',
            'model' => 'TestEmailTemplateForI18n',
			'foreign_key' => 'f13f5408-6f6d-11df-a4bb-002618f2d9f9',
            'field' => 'subject',
			'content' => 'Bienvenido/a a ${school}'
		),
		array(
            'id' => '100c8e5c-7638-11e0-8e03-0013f78a4bcc',
            'locale' => 'es',
            'model' => 'TestEmailTemplateForI18n',
			'foreign_key' => 'f13f5408-6f6d-11df-a4bb-002618f2d9f9',
            'field' => 'html',
			'content' => '
				<p>Estimado/a ${name},</p>
				<p>Queremos darle la bienvenida a ${school}.</p>
				<p><a href="${url(/users/login)}">Haga click aquí para loguearse: ${url(/users/login)}</a></p>
				<p>${message}</p>
			'
		),
		array(
            'id' => '101c8e5c-7638-11e0-8e03-0013f78a4bcc',
            'locale' => 'es',
            'model' => 'TestEmailTemplateForI18n',
			'foreign_key' => 'f13f5408-6f6d-11df-a4bb-002618f2d9f9',
            'field' => 'text',
			'content' => '
				Estimado/a ${name},
				Queremos darle la bienvenida a ${school}.
				Haga click aquí para loguearse: ${url(/users/login)}
				${message}
			'
		)
	);
}
?>
