<?php
class CerbMailTransport_Null extends Extension_MailTransport {
	const ID = 'core.mail.transport.null';
	
	private $_lastErrorMessage = null;
	private $_logger = null;
	
	function renderConfig(Model_MailTransport $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('model', $model);
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:cerberusweb.core::internal/mail_transport/null/config.tpl');
	}
	
	function testConfig(array $params, &$error=null) {
		return true;
	}
	
	/**
	 * @param Swift_Message $message
	 * @return boolean
	 */
	function send(Swift_Message $message, Model_MailTransport $model) {
		if(false == ($mailer = $this->_getMailer()))
			return false;
		
		//error_log($message->toString());
		
		$result = $mailer->send($message);
		
		if(!$result) {
			$this->_lastErrorMessage = $this->_logger->getLastError();
		}
		
		$this->_logger->clear();
		
		return $result;
	}
	
	function getLastError() {
		return $this->_lastErrorMessage;
	}
	
	private function _getMailer() {
		static $mailer = null;
		
		if(is_null($mailer)) {
			$null = Swift_NullTransport::newInstance();
			$mailer = Swift_Mailer::newInstance($null);
			
			$this->_logger = new Cerb_SwiftPlugin_TransportExceptionLogger();
			$mailer->registerPlugin($this->_logger);
		}
		
		return $mailer;
	}
}