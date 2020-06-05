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
	
	/**
	 * @param $server
	 * @param $port
	 * @param $service
	 * @param $username
	 * @param $password
	 * @param int $timeout_secs
	 * @param int $connected_account_id
	 * @return bool
	 * @throws Exception
	 */
	private function _testMailboxImap($server, $port, $service, $username, $password, $timeout_secs=30, $connected_account_id=0) {
		$imap_timeout = !empty($timeout_secs) ? $timeout_secs : 30;
		
		try {
			$options = [
				'username' => $username,
				'password' => $password,
				'hostspec' => $server,
				'port' => $port,
				'timeout' => $imap_timeout,
				'secure' => false,
			];
			
			if($service == 'imap-ssl') {
				$options['secure'] = 'tlsv1';
			} else if($service == 'imap-starttls') {
				$options['secure'] = 'tls';
			}
			
			// Are we using a connected account for XOAUTH2?
			if($connected_account_id) {
				if(false == ($connected_account = DAO_ConnectedAccount::get($connected_account_id)))
					throw new Exception("Failed to load the connected account");
					
				if(false == ($service = $connected_account->getService()))
					throw new Exception("Failed to load the connected service");
				
				if(false == ($service_extension = $service->getExtension()))
					throw new Exception("Failed to load the connected service extension");
				
				if(!($service_extension instanceof ServiceProvider_OAuth2))
					throw new Exception("The connected account is not an OAuth2 provider");
				
				/** @var $service_extension ServiceProvider_OAuth2 */
				if(false == ($access_token = $service_extension->getAccessToken($connected_account)))
					throw new Exception("Failed to load the access token");
				
				$options['xoauth2_token'] = new Horde_Imap_Client_Password_Xoauth2($username, $access_token->getToken());
				
				if(!$options['password'])
					$options['password'] = 'XOAUTH2';
			}
			
			$client = new Horde_Imap_Client_Socket($options);
			
			$mailbox = 'INBOX';
			
			$client->status($mailbox);
			
		} catch (Horde_Imap_Client_Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		return TRUE;
	}
	
	private function _testMailboxPop3($server, $port, $service, $username, $password, $timeout_secs=30) {
		$imap_timeout = !empty($timeout_secs) ? $timeout_secs : 30;
		
		try {
			$options = [
				'username' => $username,
				'password' => $password,
				'hostspec' => $server,
				'port' => $port,
				'timeout' => $imap_timeout,
				'secure' => false,
			];
			
			if($service == 'pop3-ssl') {
				$options['secure'] = 'tlsv1';
			} else if($service == 'pop3-starttls') {
				$options['secure'] = 'tls';
			}
			
			$client = new Horde_Imap_Client_Socket_Pop3($options);
			
			$mailbox = 'INBOX';
			
			$client->status($mailbox);
			
		} catch (Horde_Imap_Client_Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		return TRUE;
	}
	
	function testMailbox($server, $port, $service, $username, $password, $timeout_secs=30, $connected_account_id=0) {
		switch($service) {
			default:
			case 'pop3':
			case 'pop3-ssl':
			case 'pop3-starttls':
				return $this->_testMailboxPop3($server, $port, $service, $username, $password, $timeout_secs);
				
			case 'imap':
			case 'imap-ssl':
			case 'imap-starttls':
				return $this->_testMailboxImap($server, $port, $service, $username, $password, $timeout_secs, $connected_account_id);
		}
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
				
				if(!array_key_exists('path', $url_parts))
					$url_parts['path'] = '/';
				
				$whitelist_hash[$url_parts['host']][] = DevblocksPlatform::strToRegExp(sprintf('*://%s%s%s',
					DevblocksPlatform::strStartsWith($url_parts['host'],'.') ? '*' : '',
					$url_parts['host'],
					$url_parts['path'].'*'
				));
			}
			
			$cache->save($whitelist_hash, 'mail_html_links_whitelist', [], 0);
		}
		
		return $whitelist_hash;
	}
};