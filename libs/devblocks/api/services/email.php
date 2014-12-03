<?php
/**
 * Email Management Singleton
 *
 * @static
 * @ingroup services
 */
class _DevblocksEmailManager {
	private static $instance = null;
	
	private $mailers = array();
	
	/**
	 * @private
	 */
	private function __construct() {
		
	}
	
	/**
	 * Enter description here...
	 *
	 * @return _DevblocksEmailManager
	 */
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksEmailManager();
		}
		return self::$instance;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return Swift_Message
	 */
	function createMessage() {
		return Swift_Message::newInstance();
	}
	
	/**
	 * @return Swift_Mailer
	 */
	function getMailer($options) {

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
		$hash = md5(sprintf("%s %s %s %s %s %d %d",
			$smtp_host,
			$smtp_user,
			$smtp_pass,
			$smtp_port,
			$smtp_enc,
			$smtp_max_sends,
			$smtp_timeout
		));
		
		if(!isset($this->mailers[$hash])) {
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
			$mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin($smtp_max_sends,1));
			
			$this->mailers[$hash] =& $mailer;
		}

		return $this->mailers[$hash];
	}
	
	function testMailbox($server, $port, $service, $username, $password, $ssl_ignore_validation=false, $timeout_secs=30, $max_msg_size_kb=0) {
		if (!extension_loaded("imap"))
			throw new Exception("PHP 'imap' extension is not loaded!");
		
		$imap_timeout = !empty($timeout_secs) ? $timeout_secs : 30;
		
		// Clear error stack
		imap_errors();
		imap_timeout(IMAP_OPENTIMEOUT, $imap_timeout);
		imap_timeout(IMAP_READTIMEOUT, $imap_timeout);
		imap_timeout(IMAP_CLOSETIMEOUT, $imap_timeout);
		
		switch($service) {
			default:
			case 'pop3': // 110
				$connect = sprintf("{%s:%d/pop3/notls}INBOX",
					$server,
					$port
				);
				break;
				 
			case 'pop3-ssl': // 995
				$connect = sprintf("{%s:%d/pop3/ssl%s}INBOX",
					$server,
					$port,
					$ssl_ignore_validation ? '/novalidate-cert' : ''
				);
				break;
				 
			case 'imap': // 143
				$connect = sprintf("{%s:%d/notls}INBOX",
					$server,
					$port
				);
				break;
				
			case 'imap-ssl': // 993
				$connect = sprintf("{%s:%d/imap/ssl%s}INBOX",
					$server,
					$port,
					$ssl_ignore_validation ? '/novalidate-cert' : ''
				);
				break;
		}
		
		try {
			$mailbox = @imap_open(
				$connect,
				!empty($username)?$username:"superuser",
				!empty($password)?$password:"superuser"
			);
	
			if($mailbox === FALSE)
				throw new Exception(imap_last_error());
			
			@imap_close($mailbox);
			
		} catch(Exception $e) {
			throw new Exception($e->getMessage());
		}
			
		return TRUE;
	}
	
	/**
	 * @return array
	 */
	function getErrors() {
		return imap_errors();
	}
	
};