<?php
class EmailSendException extends Exception {
	protected $mail;
	protected $emails;

	public function __construct($mail, $emails) {
		parent::__construct(__('Could not send email', true));

		$this->mail = $mail;
		$this->emails = $emails;
	}

	public function getMail() {
		return $this->mail;
	}

	public function getEmails() {
		return $this->emails;
	}
}

class Email extends EmailAppModel {
	/**
	 * belongsTo bindings
	 *
	 * @var array
	 */
	public $belongsTo = array(
		'EmailTemplate' => array('className' => 'Email.EmailTemplate')
	);

	/**
	 * hasMany bindings
	 *
	 * @var array
	 */
	public $hasMany = array(
		'EmailAttachment' => array('className' => 'Email.EmailAttachment', 'dependent'=>true),
		'EmailDestination' => array('className' => 'Email.EmailDestination', 'dependent'=>true)
	);

	/**
	 * Fields to be compressed. Can be overriden with configure variable Email.compress
	 *
	 * @var array
	 */
	protected $compress = array('variables', 'html', 'text');

	/**
	 * Mailer
	 *
	 * @var object
	 */
	protected $mailer;

	/**
	 * Constructor. Binds the model's database table to the object.
	 *
	 * @param integer $id Set this ID for this model on startup
	 * @param string $table Name of database table to use.
	 * @param object $ds DataSource connection object.
	 */
	public function __construct($id = false, $table = null, $ds = null) {
		$compress = Configure::read('Email.compress');
		if (!empty($compress) && !is_array($compress)) {
			$compress = $this->compress;
        }

		foreach($this->hasMany as $key => $binding) {
			$this->hasMany[$key]['dependent'] = true;
		}

		parent::__construct($id, $table, $ds);

		if (!empty($compress) && App::import('Behavior', 'Syrup.Compressible')) {
			$this->Behaviors->attach('Syrup.Compressible', $compress);
		}
	}

	/**
	 * Queue an email (sends now if $scheduled == true or Configure::read('Email.queue') == false)
	 *
	 * @param mixed $key If string, key for EmailTemplate, if array will be used as $variables, and $variables as $scheduled
	 * @param array $variables Replacement variables (variable => value)
	 * @param mixed $scheduled true to not queue (override with config Email.queue), or a string to pass to strtotime(), or the time value
	 * @return mixed Email ID, or false if error
	 */
	public function send($key, $variables = array(), $scheduled = null) {
		if (is_array($key)) {
			$scheduled = !is_array($variables) ? $variables : null;
			$variables = $key;
			$key = null;
		}

		if (is_null($scheduled)) {
			$scheduled = (Configure::read('Email.queue') === false);
		}

		$destinations = array();
		foreach(array('to', 'cc', 'bcc') as $destinationType) {
			$destinations[$destinationType] = array();
			if (!empty($variables[$destinationType])) {
				if (!is_array($variables[$destinationType])) {
					$variables[$destinationType] = array('email' => $variables[$destinationType]);
				}
				if (!empty($variables[$destinationType]['email'])) {
					$variables[$destinationType] = array(array_intersect_key($variables[$destinationType], array('name'=>true, 'email'=>true)));
				}
				foreach($variables[$destinationType] as $i => $destination) {
					$destination = array_merge(array(
						'name' => null,
						'email' => null
					), $destination);

					if (!empty($variables['name']) && empty($destination['name'])) {
						$destination['name'] = $variables['name'];
					} else if (empty($destination['name'])) {
						$destination['name'] = $destination['email'];
					}

					if (!empty($destination['email'])) {
						$destinations[$destinationType][] = $destination;
					}
				}
			}
		}

		$destinations = array_filter($destinations);
		if (empty($destinations)) {
			return false;
		}

		$attachments = array();
		if (!empty($variables['attachments'])) {
			foreach((array) $variables['attachments'] as $attachment)  {
				if (is_file($attachment)) {
					$attachments[] = $attachment;
				}
			}
		}

		$emailTemplate = null;
		if (!empty($key)) {
            $emailTemplate = $this->EmailTemplate->get($key);
            if (empty($emailTemplate)) {
                return false;
            }
        }

        $email = array($this->alias => array(
            'queued' => date('Y-m-d H:i:s'),
            'variables' => serialize(array_diff_key($variables, array('attachments'=>true, 'to'=>true, 'cc'=>true, 'bcc'=>true)))
        ));

        if ($this->EmailTemplate->engine === 'db') {
            $email[$this->alias]['email_template_id'] = !empty($emailTemplate) ? $emailTemplate['EmailTemplate']['id'] : null;
        } else {
            $email[$this->alias]['template'] = !empty($emailTemplate) ? $emailTemplate['EmailTemplate']['key'] : null;
        }

        $this->create();
        if (!$this->save($email)) {
            return false;
        }

        $id = $this->id;

        foreach($destinations as $type => $people) {
            foreach($people as $person) {
                $this->EmailDestination->create();
                $this->EmailDestination->save(array('EmailDestination' => array(
                    'email_id' => $id,
                    'type' => $type,
                    'name' => $person['name'],
                    'email' => $person['email']
                )));
            }
        }

        foreach($attachments as $attachment) {
            $this->EmailAttachment->create();
            $this->EmailAttachment->save(array('EmailAttachment' => array(
                'email_id' => $id,
                'file' => $attachment
            )));
        }

        if ($scheduled === true) {
            try {
                $this->sendNow($id);
            } catch(Exception $e) { }
        } else {
            $this->schedule($id, !empty($scheduled) ? $scheduled : null);
        }

        return $id;
    }

    /**
     * Schedules an email sending using Robot plugin
     *
     * @param string $id Email ID
     * @param mixed $scheduled A string to pass to strtotime(), or the time value
     * @return mixed Task ID, or false if error
     */
    public function schedule($id, $scheduled = null) {
        if (!isset($this->RobotTask)) {
            $this->RobotTask = ClassRegistry::init('Robot.RobotTask');
            if (!isset($this->RobotTask)) {
                return false;
            }
        }

        return $this->RobotTask->schedule(
            array('admin' => false, 'plugin' => 'email', 'controller' => 'emails', 'action' => 'send'),
            compact('id'),
            $scheduled
        );
    }

    /**
     * Send the specified email
     *
     * @param string $id Email ID
     * @throws Exception, EmailSendException
     */
    public function sendNow($id) {
        $email = $this->find('first', array(
            'conditions' => array($this->alias . '.' . $this->primaryKey => $id),
            'contain' => array('EmailDestination', 'EmailAttachment')
        ));
        if (empty($email) || empty($email['EmailDestination'])) {
            if (empty($email)) {
                throw new Exception(sprintf(__('Could not find email with ID "%s"', true), $id));
            }
            throw new Exception(__('Could not find a destination to where to send the email', true));
        }

        $mail = $this->render($email);
        if (empty($mail) || !is_array($mail)) {
            throw new Exception(__('Unable to render email', true));
        }

        foreach(array('from', 'replyTo', 'sender') as $key) {
            if (!empty($mail[$key]) && empty($mail[$key]['name'])) {
                $mail[$key]['name'] = $mail[$key]['email'];
            }
        }

        $mail['destinations'] = array();
        foreach($email['EmailDestination'] as $destination) {
            $mail['destinations'][] = array(
                'type' => $destination['type'],
                'name' => !empty($destination['name']) ? $destination['name'] : $destination['email'],
                'email' => $destination['email']
            );
        }

        $mail['attachments'] = array();
        if (!empty($email['EmailAttachment'])) {
            $mail['attachments'] = Set::extract($email['EmailAttachment'], '/file');
        }

        $variables = $this->variables($email);

        $result = $this->mail($mail);
        $failedEmails = array();
        if (is_array($result)) {
            $failedEmails = $result;
            $result = false;
        }

        if (empty($email[$this->alias]['failed'])) {
            $email[$this->alias]['failed'] = 0;
        }

        if (!$result) {
            $email[$this->alias]['failed'] += 1;
        }

        $keep = Configure::read('Email.keep');
        if (!$result || !empty($keep)) {
            $email = array($this->alias => array(
                'id' => $id,
                'processed' => date('Y-m-d H:i:s'),
                'failed' => $email[$this->alias]['failed'],
                'sent' => ($result ? date('Y-m-d H:i:s') : null)
            ));

            $this->id = $id;
            $this->save($email, true, array_keys($email[$this->alias]));
        } else if ($result) {
            $engine = Configure::read('Email.templateEngine');
            if ($engine !== 'db') {
                $emailTemplateBinding = $this->belongsTo['EmailTemplate'];
                $this->unbindModel(array('belongsTo' => array('EmailTemplate')), false);
            }

            $this->delete($id);

            if (!empty($emailTemplateBinding)) {
                $this->bindModel(array('belongsTo' => array('EmailTemplate' => $emailTemplateBinding)), false);
            }
        }

        if (!empty($variables['callback'])) {
            $url = $variables['callback'];
            if (is_array($url)) {
                $url = Router::url($url);
            }
            $url = str_ireplace(array('${id}', '${success}'), array($id, !empty($result) ? 1 : 0), $url);
            $this->requestAction($url);
        }

        $retry = $variables['retry'];
        if (!$result && $retry['enabled'] && $email[$this->alias]['failed'] < $retry['max']) {
            if (!isset($this->RobotTask)) {
                $this->RobotTask = ClassRegistry::init('Robot.RobotTask');
            }
            if (isset($this->RobotTask)) {
                $scheduled = strtotime('now') + $retry['interval'][$email[$this->alias]['failed'] - 1];
                $this->schedule($id, $scheduled);
            }
        }

        if (!$result) {
            throw new EmailSendException($mail, $failedEmails);
        }
    }

    /**
     * Perform the actual email sending
     *
     * @param array $email Indexed array with: 'from', 'replyTo', 'destinations', 'subject', 'html', 'text', 'attachments'
     * @return mixed true if success, or array with failing addresses if failed
     */
    protected function mail($email) {
        if (!isset($this->mailer)) {
            if (!defined('SWIFT_LIB_DIRECTORY')) {
                define('SWIFT_LIB_DIRECTORY', dirname(dirname(__FILE__)) . DS . 'vendors' . DS . 'swift' . DS . 'lib');
            }
            if (!include_once(SWIFT_LIB_DIRECTORY . DS . 'swift_required.php')) {
                throw new Exception(__('SWIFT library is not installed', true));
            }

            $transport = array(
                'type' => 'mail',
                'command' => null,
                'host' => null,
                'port' => 25,
                'user' => null,
                'password' => null,
                'encryption' => null
            );

            $settings = Configure::read('Email');
            if (!empty($settings)) {
                $transport = array_merge($transport, array_intersect_key($settings, $transport));
            }

            if (!in_array($transport['type'], array('mail', 'sendmail', 'smtp'))) {
                throw new Exception(sprintf(__('Can\'t recognize transport "%s"', true), $transport['type']));
            }

            switch($transport['type']) {
                case 'mail':
                    $this->mailer = Swift_MailTransport::newInstance();
                    break;
                case 'sendmail':
                    $this->mailer = Swift_SendmailTransport::newInstance($transport['command']);
                    break;
                case 'smtp':
                    $this->mailer = Swift_SmtpTransport::newInstance();
                    $this->mailer->setHost($transport['host']);
                    $this->mailer->setPort($transport['port']);
                    if (!empty($transport['user'])) {
                        $this->mailer->setUsername($transport['user']);
                    }
                    if (!empty($transport['password'])) {
                        $this->mailer->setPassword($transport['password']);
                    }
                    if (!empty($transport['encryption'])) {
                        $this->mailer->setEncryption($transport['encryption']);
                    }

                    try {
                        $debug = Configure::read('debug');
                        Configure::write('debug', 0);
                        $this->mailer->start();
                        Configure::write('debug', $debug);
                    } catch(Exception $e) {
                        throw new Exception($e->getMessage());
                    }
                    break;
            }
        }

        $emailHeaders = (array) Configure::read('Email.AdditionalHeaders');

        $mail = Swift_Message::newInstance();
        $mail->setFrom(array($email['from']['email'] => $email['from']['name']));
        if (!empty($email['sender']['email'])) {
          $mail->setSender($email['sender']['email']);
        } else {
          $mail->setSender($email['from']['email']);
        }
        if (!empty($email['replyTo']) && !empty($email['replyTo']['email'])) {
            $mail->setReplyTo(array($email['replyTo']['email'] => $email['replyTo']['name']));
        }
        $mail->setSubject($email['subject']);

        $sendAllEmailTo = Configure::read('Email.SendAllEmailTo');
        if (!empty($sendAllEmailTo)) {
            foreach($email['destinations'] as $i => $destination) {
                $emailHeaders['X-EmailPlugin-Original-' . $destination['type'] . '-' . $i] = $destination['name'] . ' <' . $destination['email'] . '>';
            }
            $mail->setTo($sendAllEmailTo);
        } else {
            $methods = array('cc' => 'addCc', 'bcc' => 'addBcc', 'to' => 'addTo');
            foreach($email['destinations'] as $destination) {
                $method = $methods[$destination['type']];
                $mail->$method($destination['email'], $destination['name']);
            }
        }

        if (!empty($emailHeaders)) {
            $headers = $mail->getHeaders();
            foreach ($emailHeaders as $header => $value) {
                $headers->addTextHeader($header, $value);
            }
        }

        $contentTypes = array('html' => 'text/html', 'text' => 'text/plain');
        if (!empty($email['html']) && !empty($email['text'])) {
            $mail->setBody($email['html'], $contentTypes['html']);
            $mail->addPart($email['text'], $contentTypes['text']);
        } else {
            foreach($contentTypes as $type => $contentType) {
                if (empty($email[$type])) {
                    continue;
                }
                $mail->setBody($email[$type], $contentType);
            }
        }

        if (!empty($email['attachments'])) {
            foreach($email['attachments'] as $attachment) {
                $mail->attach(Swift_Attachment::fromPath($attachment));
            }
        }

        if (!$this->mailer->send($mail, $failures)) {
            return $failures;
        }

        return true;
    }

    /**
     * Render an email into an array with replaced variables
     *
     * @param mixed $email Either an Email ID, or array with email information
     * @param array $variables Replacement variables
     * @param bool $force If true, recompile body, otherwise look to see if it was already processed
     * @return array Array with indexes: 'from', 'subject', 'html', 'text'
     */
    public function render($email, $variables = array(), $force = false) {
        if (!is_array($email)) {
            $findOptions = array();
            if ($this->EmailTemplate->engine === 'db') {
                $findOptions['contain'] = array('EmailTemplate');
            } else {
                $findOptions['recursive'] = -1;
            }
            $email = $this->find('first', array_merge($findOptions, array(
                'conditions' => array($this->alias . '.' . $this->primaryKey => $email)
            )));
        } else if (empty($email[$this->alias])) {
            $email = array($this->alias => $email);
        }

        if (!$force && (!empty($email[$this->alias]['html']) || !empty($email[$this->alias]['text']))) {
            return array(
                'from' => array(
                    'name' => $email[$this->alias]['from_name'],
                    'email' => $email[$this->alias]['from_email']
                    ),
                'subject' => $email[$this->alias]['subject'],
                'html' => $email[$this->alias]['html'],
                'text' => $email[$this->alias]['text']
            );
        }

        if (empty($email['EmailTemplate']['key'])) {
            $emailTemplate = null;
            if ($this->EmailTemplate->engine === 'db' && !empty($email[$this->alias]['email_template_id'])) {
                $emailTemplate = $this->EmailTemplate->find('first', array(
                    'conditions' => array('EmailTemplate.id' => $email[$this->alias]['email_template_id']),
                    'recursive' => -1
                ));
            } elseif ($this->EmailTemplate->engine !== 'db' && !empty($email[$this->alias]['template'])) {
                $emailVariables = array();
                if (!empty($email[$this->alias]['variables'])) {
                    $emailVariables = (array) unserialize($email[$this->alias]['variables']);
                }
                $emailVariables = $this->variables($email, $emailVariables);
                $emailEscape = isset($emailVariables['escape']) ? $emailVariables['escape'] : null;

                $emailTemplate = $this->EmailTemplate->get(
                    $email[$this->alias]['template'],
                    $emailVariables,
                    $emailEscape
                );
            }
            if (!empty($emailTemplate)) {
                $email = array_merge($email, $emailTemplate);
            }
        }

        if (empty($email)) {
            return false;
        }

        $variables = $this->variables($email, $variables);
        $escape = isset($variables['escape']) ? $variables['escape'] : null;

        foreach($variables as $field => $value) {
            if (!is_string($value)) {
                continue;
            }

            $fieldVariables = $variables;
            if (in_array($field, array('html', 'text'))) {
                $formatVariablesKey = $field.'Variables';
                if (array_key_exists($formatVariablesKey, $fieldVariables)) {
                    $fieldVariables = Set::merge(
                        array_diff_key($fieldVariables, array('htmlVariables'=>null, 'textVariables'=>null)),
                        $fieldVariables[$formatVariablesKey]
                    );
                }
            }

            $variables[$field] = $this->EmailTemplate->replace(
                $value,
                $fieldVariables,
                is_null($escape) ? ($field === 'html') : $escape
            );
        }

        if (!empty($variables['layout'])) {
            foreach(array('html', 'text') as $type) {
                if (empty($variables[$type])) {
                    continue;
                }

                $parameters = array(
                    'title' => !empty($variables['subject']) ? $variables['subject'] : ''
				);

				$variables[$type] = $this->EmailTemplate->renderLayout(
					$variables[$type],
					$variables['layout'],
					$type,
					$variables,
					$parameters
				);
			}
		}

		$id = $email[$this->alias][$this->primaryKey];
		$email = array(
			'from' => array('name' => null, 'email' => null),
			'replyTo' => null,
			'sender' => null,
			'subject' => null,
			'text' => null,
			'html' => null
		);

		$email = Set::merge($email, array_intersect_key($variables, $email));
		$saveEmail = $email;
		foreach(array('name', 'email') as $field) {
			if (!isset($email['from'][$field])) {
				continue;
			}
			$saveEmail['from_' . $field] = $email['from'][$field];
		}
		unset($saveEmail['from']);

		$this->id = $id;
		$this->save(array($this->alias => $saveEmail), array_keys($saveEmail));

		return $email;
	}

	/**
	 * Get replacement variables (not replaced) for email
	 *
	 * @param array $email Email record
	 * @param array $variables Replacement variables to override
	 * @return array Array with indexes: 'from', 'subject', 'html', 'text', 'callback'
	 */
	protected function variables($email, $variables = array()) {
		$variables = array_merge(array(
			'from' => array('name' => null, 'email' => null),
			'replyTo' => array('name' => null, 'email' => null),
			'sender' => array('name' => null, 'email' => null),
			'subject' => null,
			'layout' => null,
			'html' => null,
			'text' => null,
			'callback' => null,
			'retry' => false
		), $variables);

		$emailVariables = Configure::read('Email');
		if (!empty($emailVariables)) {
			$variables = Set::merge($variables, array_intersect_key($emailVariables, $variables));
		}

		if (!empty($email['EmailTemplate'])) {
			$email['EmailTemplate']['from'] = array();
			foreach(array('from_name', 'from_email') as $field) {
				if (!array_key_exists($field, $email['EmailTemplate'])) {
					continue;
				}
				$email['EmailTemplate']['from'][preg_replace('/^from_/i', '', $field)] = trim($email['EmailTemplate'][$field]);
				unset($email['EmailTemplate'][$field]);
			}
			$email['EmailTemplate']['from'] = array_filter($email['EmailTemplate']['from']);

			foreach(array_intersect_key($email['EmailTemplate'], $variables) as $field => $value) {
				if (empty($email['EmailTemplate'][$field])) {
					continue;
				}
				$variables[$field] = $email['EmailTemplate'][$field];
			}
		}

		if (!empty($email[$this->alias]['variables'])) {
			$variables = array_merge(
				$variables,
				(array) unserialize($email[$this->alias]['variables'])
			);
		}

		foreach(array('from', 'replyTo', 'sender') as $variable) {
			if (!empty($variables[$variable])) {
				if (!is_array($variables[$variable])) {
					$variables[$variable] = array('email' => $variables[$variable]);
				}

				$variables[$variable] = array_merge(array(
					'name' => null,
					'email' => null
				), $variables[$variable]);

				if (empty($variables[$variable]['name'])) {
					$variables[$variable]['name'] = $variables[$variable]['email'];
				}
			}
		}

		if (!is_array($variables['retry'])) {
			$variables['retry'] = array('enabled' => $variables['retry']);
		}

		$variables['retry'] = array_merge(array(
			'enabled' => false,
			'max' => 10,
			'interval' => array(60, 120, 600, 1200, 2400, 4800, 9600, 19200, 38400)	// seconds
		), $variables['retry']);

		if (empty($variables['retry']['interval']) || !is_array($variables['retry']['interval'])) {
			$variables['retry']['interval'] = array(empty($variables['retry']['interval']) ? 2400 : $variables['retry']['interval']);
		}

		reset($variables['retry']['interval']);
		$interval = current($variables['retry']['interval']);
		for($i=0, $limiti=$variables['retry']['max']; $i < $limiti; $i++) {
			if (isset($variables['retry']['interval'][$i])) {
				continue;
			}
			$variables['retry']['interval'][$i] =
				$i > 0 ?
				2 * $variables['retry']['interval'][$i - 1] :
			   	($i + 1) * $interval;
		}

		return $variables;
	}
}
?>
