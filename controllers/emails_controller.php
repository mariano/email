<?php
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

		return $this->Email->sendNow($this->params['robot']['id']);
	}
}
?>
