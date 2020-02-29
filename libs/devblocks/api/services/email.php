<?php
/**
 * Email Management Singleton
 *
 * @static
 * @ingroup services
 */
class _DevblocksEmailManager {
	private static $instance = null;
	private $_lastErrorMessage = null;
	
	/**
	 * @private
	 */
	private function __construct() {
		
	}
	
	/**
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
	 *
	 * @return Swift_Message
	 */
	function createMessage() {
		return Swift_Message::newInstance();
	}
	
	function send(Swift_Message $message) {
		$from = array_keys($message->getFrom());
		$sender = reset($from);
		
		if(empty($sender)) {
			$this->_lastErrorMessage = "A 'From:' sender address is required.";
			return false;
		}
		
		if(false == ($replyto = DAO_Address::getByEmail($sender))) {
			$this->_lastErrorMessage = "The 'From:' sender address does not exist.";
			return false;
		}
		
		if(!DAO_Address::isLocalAddressId($replyto->id))
			$replyto = DAO_Address::getDefaultLocalAddress();
		
		if(false == ($model = $replyto->getMailTransport())) {
			$this->_lastErrorMessage = "The 'From:' sender address does not have a mail transport configured.";
			return false;
		}
		
		if(false == ($transport = $model->getExtension())) {
			$this->_lastErrorMessage = "The 'From:' sender address mail transport is invalid.";
			return false;
		}
		
		if(false == ($result = $transport->send($message, $model))) {
			$this->_lastErrorMessage = $transport->getLastError();
			
			if(!empty($this->_lastErrorMessage)) {
				/*
				 * Log activity (transport.delivery.error)
				 */
				$entry = array(
					// {{actor}} failed to deliver message: {{error}}
					'message' => 'activities.transport.delivery.error',
					'variables' => array(
						'error' => sprintf("%s", $this->_lastErrorMessage),
						),
					'urls' => array(
						)
				);
				CerberusContexts::logActivity('transport.delivery.error', CerberusContexts::CONTEXT_MAIL_TRANSPORT, $model->id, $entry, CerberusContexts::CONTEXT_MAIL_TRANSPORT, $model->id);
			}
		}
		
		return $result;
	}
	
	function getLastErrorMessage() {
		return $this->_lastErrorMessage;
	}
	
	function testMailbox($server, $port, $service, $username, $password, $ssl_ignore_validation=false, $auth_disable_plain=false, $timeout_secs=30, $max_msg_size_kb=0) {
		if (!extension_loaded("imap"))
			throw new Exception("PHP 'imap' extension is not loaded!");
		
		$imap_timeout = !empty($timeout_secs) ? $timeout_secs : 30;
		
		// Clear error stack
		imap_errors();
		imap_timeout(IMAP_OPENTIMEOUT, $imap_timeout);
		imap_timeout(IMAP_READTIMEOUT, $imap_timeout);
		imap_timeout(IMAP_CLOSETIMEOUT, $imap_timeout);
		
		$imap_options = array();
		
		if($auth_disable_plain)
			$imap_options['DISABLE_AUTHENTICATOR'] = 'PLAIN';
		
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
				!empty($password)?$password:"superuser",
				0,
				0,
				$imap_options
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
	
	public function getImageProxyBlocklist() {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null === ($blocklist_hash = $cache->load('mail_html_image_blocklist'))) {
			$image_blocklist = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::MAIL_HTML_IMAGE_PROXY_BLOCKLIST, '');
			
			$blocklist_items = DevblocksPlatform::parseCrlfString($image_blocklist);
			$blocklist_hash = [];
			
			foreach($blocklist_items as $idx => $blocklist_item) {
				if(DevblocksPlatform::strStartsWith($blocklist_item, '#'))
					continue;
				
				if(!DevblocksPlatform::strStartsWith($blocklist_item, ['http://', 'https://']))
					$blocklist_item = 'http://' . $blocklist_item;
				
				if(false == ($url_parts = parse_url($blocklist_item)))
					continue;
				
				if(!array_key_exists('host', $url_parts))
					continue;
				
				if(!array_key_exists($url_parts['host'], $blocklist_hash))
					$blocklist_hash[$url_parts['host']] = [];
				
				$blocklist_hash[$url_parts['host']][] = DevblocksPlatform::strToRegExp(sprintf('*://%s%s%s',
					DevblocksPlatform::strStartsWith($url_parts['host'],'.') ? '*' : '',
					$url_parts['host'],
					array_key_exists('path', $url_parts) ? ($url_parts['path'].'*') : '/*'
				));
			}
			
			$cache->save($blocklist_hash, 'mail_html_image_blocklist', [], 0);
		}
		
		return $blocklist_hash;
	}
	
	public function getLinksWhitelist() {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null === ($whitelist_hash = $cache->load('mail_html_links_whitelist'))) {
			$links_whitelist = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::MAIL_HTML_LINKS_WHITELIST, '');
			
			$whitelist_items = DevblocksPlatform::parseCrlfString($links_whitelist);
			$whitelist_hash = [];
			
			foreach($whitelist_items as $idx => $whitelist_item) {
				if (DevblocksPlatform::strStartsWith($whitelist_item, '#'))
					continue;
				
				if(!DevblocksPlatform::strStartsWith($whitelist_item, ['http://', 'https://']))
					$whitelist_item = 'http://' . $whitelist_item;
				
				if(false == ($url_parts = parse_url($whitelist_item)))
					continue;
				
				if(!array_key_exists('host', $url_parts))
					continue;
				
				if(!array_key_exists($url_parts['host'], $whitelist_hash))
					$whitelist_hash[$url_parts['host']] = [];
				
				$whitelist_hash[$url_parts['host']][] = DevblocksPlatform::strToRegExp(sprintf('*://%s%s%s',
					DevblocksPlatform::strStartsWith($url_parts['host'],'.') ? '*' : '',
					$url_parts['host'],
					array_key_exists('path', $url_parts) ? ($url_parts['path'].'*') : '/*'
				));
			}
			
			$cache->save($whitelist_hash, 'mail_html_links_whitelist', [], 0);
		}
		
		return $whitelist_hash;
	}
};