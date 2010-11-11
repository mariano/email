<?php
ORM::import('Email.Email');

class EmailsController extends EmailAppController {
	public $uses = array('Email.Email');
	/**
	 * Sends a scheduled email, only to be called through robot plugin
	 *
	 * @return bool Success
	 */
	public function send() {
		if (empty($this->params['robot']['id'])) {
			$this->redirect('/');
		}

		$debug = null;
		try {
			$this->Email->sendNow($this->params['robot']['id']);
		} catch(EmailSendException $e) {
			$debug = array(
				'message' => $e->getMessage(),
				'mail' => $e->getMail(),
				'emails' => $e->getEmails()
			);
		} catch(Exception $e) {
			$debug = $e->getMessage();
		}

		if ($debug) {
			Configure::write('Robot.debug', $debug);
			return false;
		}
	}
}
?>