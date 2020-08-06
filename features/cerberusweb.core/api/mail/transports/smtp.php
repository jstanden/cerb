<?php
class CerbMailTransport_Smtp extends Extension_MailTransport {
	const ID = 'core.mail.transport.smtp';
	
	private $_lastErrorMessage = null;
	private $_logger = null;
	
	function renderConfig(Model_MailTransport $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('model', $model);
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:cerberusweb.core::internal/mail_transport/smtp/config.tpl');
	}
	
	function testConfig(array $params, &$error=null) {
		@$host = $params['host'];
		@$port = $params['port'];
		@$encryption = $params['encryption'];
		@$auth_enabled = $params['auth_enabled'];
		@$auth_user = $params['auth_user'];
		@$auth_pass = $params['auth_pass'];
		
		if(empty($host)) {
			$error = 'The SMTP "host" parameter is required.';
			return false;
		}
		
		if(empty($port)) {
			$error = 'The SMTP "port" parameter is required.';
			return false;
		}
		
		// Try connecting
		
		$options = array(
			'host' => $host,
			'port' => $port,
			'enc' => $encryption,
			'timeout' => 10,
		);
		
		if($auth_enabled) {
			$options['auth_user'] = $auth_user;
			$options['auth_pass'] = $auth_pass;
		}
		
		try {
			$mailer = $this->_getMailer($options);
			
			@$transport = $mailer->getTransport();
			@$transport->start();
			@$transport->stop();
			return true;
			
		} catch(Exception $e) {
			$error = $e->getMessage();
			return false;
		}
		
		return true;
	}
	
	/**
	 * @param Swift_Message $message
	 * @return boolean
	 */
	function send(Swift_Message $message, Model_MailTransport $model) {
		$options = array(
			'host' => @$model->params['host'],
			'port' => @$model->params['port'],
			'auth_user' => @$model->params['auth_user'],
			'auth_pass' => @$model->params['auth_pass'],
			'enc' => @$model->params['encryption'],
			'max_sends' => @$model->params['max_sends'],
			'timeout' => @$model->params['timeout'],
		);
		
		if(false == ($mailer = $this->_getMailer($options)))
			return false;
		
		$failed_recipients = [];
		
		$result = $mailer->send($message, $failed_recipients);
		
		if(!$result) {
			$this->_lastErrorMessage = $this->_logger->getLastError();
		}
		
		$this->_logger->clear();
		
		return $result;
	}
	
	function getLastError() {
		return $this->_lastErrorMessage;
	}
	
	/**
	 * @return Swift_Mailer
	 */
	private function _getMailer(array $options) {
		static $connections = array();
		
		// Options
		$smtp_host = isset($options['host']) ? $options['host'] : '127.0.0.1';
		$smtp_port = isset($options['port']) ? $options['port'] : '25';
		$smtp_user = isset($options['auth_user']) ? $options['auth_user'] : null;
		$smtp_pass = isset($options['auth_pass']) ? $options['auth_pass'] : null;
		$smtp_enc = isset($options['enc']) ? $options['enc'] : 'None';
		$smtp_max_sends = isset($options['max_sends']) ? intval($options['max_sends']) : 20;
		$smtp_timeout = isset($options['timeout']) ? intval($options['timeout']) : 30;
		
		/*
		 * [JAS]: We'll cache connection info hashed by params and hold a persistent
		 * connection for the request cycle.  If we ask for the same params again
		 * we'll get the existing connection if it exists.
		 */

		$hash = md5(json_encode(array(
			$smtp_host,
			$smtp_user,
			$smtp_pass,
			$smtp_port,
			$smtp_enc,
			$smtp_max_sends,
			$smtp_timeout
		)));
		
		if(!isset($connections[$hash])) {
			// Encryption
			switch($smtp_enc) {
				case 'TLS':
					$smtp_enc = 'tls';
					break;
					
				case 'SSL':
					$smtp_enc = 'ssl';
					break;
					
				default:
					$smtp_enc = null;
					break;
			}
			
			$smtp = Swift_SmtpTransport::newInstance($smtp_host, $smtp_port, $smtp_enc);
			$smtp->setTimeout($smtp_timeout);
			
			if(!empty($smtp_user)) {
				$smtp->setUsername($smtp_user);
				$smtp->setPassword($smtp_pass);
			}
			
			$mailer = Swift_Mailer::newInstance($smtp);
			$mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin($smtp_max_sends, 1));
			
			$this->_logger = new Cerb_SwiftPlugin_TransportExceptionLogger();
			$mailer->registerPlugin($this->_logger);
			
			$connections[$hash] = $mailer;
		}
		
		if($connections[$hash])
			return $connections[$hash];
		
		return null;
	}
}
