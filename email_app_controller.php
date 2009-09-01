<?php
class EmailAppController extends AppController {
	/**
	 * beforeFilter callback
	 */
	public function beforeFilter() {
		if (isset($this->Auth) && isset($this->params['robot'])) {
			$this->Auth->allow('*');
		}
		return parent::beforeFilter();
	}
}
?>
