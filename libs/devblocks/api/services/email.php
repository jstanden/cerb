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
	function createMessage() : Swift_Message {
		return new Swift_Message();
	}
	
	function send(Swift_Message $message) {
		$metrics = DevblocksPlatform::services()->metrics();
		
		$to = $message->getTo();
		$from = array_keys($message->getFrom());
		$sender = reset($from);
		
		if(empty($to)) {
			$this->_lastErrorMessage = "At least one 'To:' recipient address is required.";
			return false;
		}
		
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
			
			// Increment mail transport error metric
			$metrics->increment(
				'cerb.mail.transport.failures',
				1,
				[
					'transport_id' => $model->id,
					'sender_id' => $replyto->id,
				]
			);
			
		} else {
			// Increment mail transport success metric
			$metrics->increment(
				'cerb.mail.transport.deliveries',
				1,
				[
					'transport_id' => $model->id,
					'sender_id' => $replyto->id,
				]
			);
			
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
		
		//$fp_log = fopen('php://memory', 'w');
		
		try {
			$options = [
				'username' => $username,
				'password' => $password,
				'hostspec' => $server,
				'port' => $port,
				'timeout' => $imap_timeout,
				'secure' => false,
				//'debug' => $fp_log,
				//'capability_ignore' => ['LOGIN','PLAIN','NTLM','GSSAPI','XOAUTH2','AUTHENTICATE'],
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
			
		} finally {
//			fseek($fp_log, 0);
//			error_log(fread($fp_log, 1024000));
//			fclose($fp_log);
		}
		
		return TRUE;
	}
	
	private function _testMailboxPop3($server, $port, $service, $username, $password, $timeout_secs=30) {
		$imap_timeout = !empty($timeout_secs) ? $timeout_secs : 30;
		
		//$fp_log = fopen('php://memory', 'w');
		
		try {
			$options = [
				'username' => $username,
				'password' => $password,
				'hostspec' => $server,
				'port' => $port,
				'timeout' => $imap_timeout,
				'secure' => false,
				//'debug' => $fp_log,
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
			
		} finally {
//			fseek($fp_log, 0);
//			error_log(fread($fp_log, 1024000));
//			fclose($fp_log);
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
	
	public function runRoutingKata(array $routing, DevblocksDictionaryDelegate $routing_dict) : array|false {
		foreach($routing as $rule_data) {
			$num_ifs = 0;
			
			foreach($rule_data as $node_key => $node_data) {
				$node_type = DevblocksPlatform::services()->string()->strBefore($node_key, '/');
				
				// Check conditions
				if('if' == $node_type) {
					$num_ifs++;
					$cond_passed = 0;
					$cond_count = 0;
					
					foreach($node_data as $cond_key => $cond_data) {
						$cond_type = DevblocksPlatform::services()->string()->strBefore($cond_key, '/');
						$cond_count++;
						
						if('recipients' == $cond_type) {
							if(is_string($cond_data) && str_contains($cond_data, ','))
								$cond_data = DevblocksPlatform::parseCsvString($cond_data);
							
							// Allow lazy mailbox@ wildcard patterns
							$patterns = array_map(
								fn($addy) => str_ends_with($addy, '@') ? ($addy . '*') : $addy,
								is_string($cond_data) ? [$cond_data] : $cond_data,
							);
							
							if(DevblocksPlatform::services()->string()->arrayMatches($routing_dict->get('recipients', []), $patterns, only_first_match: true))
								$cond_passed++;
							
						} else if('subject' == $cond_type) {
							if(DevblocksPlatform::services()->string()->arrayMatches($routing_dict->get('subject', ''), $cond_data, only_first_match: true))
								$cond_passed++;
							
						} else if('body' == $cond_type) {
							if(DevblocksPlatform::services()->string()->arrayMatches($routing_dict->get('body', ''), $cond_data, only_first_match: true))
								$cond_passed++;
							
						} else if('sender_email' == $cond_type) {
							if(is_string($cond_data) && str_contains($cond_data, ','))
								$cond_data = DevblocksPlatform::parseCsvString($cond_data);
							
							// Allow lazy mailbox@ wildcard patterns
							$patterns = array_map(
								fn($addy) => str_ends_with($addy, '@') ? ($addy . '*') : $addy,
								is_string($cond_data) ? [$cond_data] : $cond_data,
							);
							
							if(DevblocksPlatform::services()->string()->arrayMatches($routing_dict->get('sender_email', ''), $patterns, only_first_match: true))
								$cond_passed++;
						} else if('spam_score' == $cond_type) {
							if(is_string($cond_data)) {
								$oper = DevblocksPlatform::strStartsWith($cond_data, ['>=','>','<=','<']);
								$spam_score = $routing_dict->get('spam_score', 0);
								$value = intval(DevblocksPlatform::strAlphaNum($cond_data))/100;
								if(
									($oper == '>=' && $spam_score >= $value)
									|| ($oper == '>' && $spam_score > $value)
									|| ($oper == '<=' && $spam_score <= $value)
									|| ($oper == '<' && $spam_score < $value)
								) $cond_passed++;
							}
							
						} else if('header' == $cond_type) {
							if(!is_array($cond_data))
								continue;
							
							// If every defined header pattern matches
							if(count($cond_data) == count(array_filter($cond_data, function($header_data, $header_key) use ($routing_dict) {
								$header_key = trim(DevblocksPlatform::strLower($header_key), ': ');
								return DevblocksPlatform::services()->string()->arrayMatches(
									$routing_dict->getKeyPath('headers:' . $header_key, '', ':'),
									$header_data,
									only_first_match: true
								);
							}, ARRAY_FILTER_USE_BOTH))) $cond_passed++;
							
						} else if('script' == $cond_type) {
							if(is_string($cond_data) && 1 == intval($cond_data))
								$cond_passed++;
							
						} else {
							DevblocksPlatform::noop();
						}
					}
					
					if($cond_passed == $cond_count) {
						return $rule_data['then'] ?? [];
					}
				}
			}
			
			// If the rule has no `if:` then always use it
			if(0 == $num_ifs)
				return $rule_data['then'] ?? [];
		}
		
		return false;
	}
	
	public function runRoutingKataActions(array $route_actions, CerberusParserModel $model) {
		$group_name = $route_actions['group'] ?? null;
		$bucket_name = $route_actions['bucket'] ?? null;
		$importance = $route_actions['importance'] ?? null;
		$owner = $route_actions['owner'] ?? null;
		$watchers = $route_actions['watchers'] ?? null;
		$comment = $route_actions['comment'] ?? null;
		
		// Comment
		if(!is_null($comment)) {
			DAO_Comment::create([
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
				DAO_Comment::CONTEXT_ID => $model->getTicketId(),
				DAO_Comment::CREATED => time()+5,
				DAO_Comment::IS_MARKDOWN => true,
				DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_GROUP,
				DAO_Comment::OWNER_CONTEXT_ID => $model->getRouteGroup()->id,
			]);
		}
		
		// Importance
		if(!is_null($importance))
			$model->getTicketModel()->importance = DevblocksPlatform::intClamp($importance, 0, 100);
		
		// Owner
		if(is_string($owner)) {
			// Look up the @mention
			if(($owner_worker = DAO_Worker::getByAtMention(ltrim($owner, '@'))))
				$model->getTicketModel()->owner_id = $owner_worker->id;
		}
		
		// Watchers
		if(!is_null($watchers)) {
			// A string with commas is a list
			if(is_string($watchers) && str_contains($watchers, ',')) {
				$watchers = DevblocksPlatform::parseCsvString($watchers);
			// A single string is a single element list
			} elseif(is_string($watchers)) {
				$watchers = [$watchers];
			}
			
			if(is_array($watchers)) {
				if(($watcher_workers = DAO_Worker::getByAtMentions($watchers)))
					$model->getMessage()->watcher_ids = array_keys($watcher_workers);
			}
		}
		
		// If we just have a bucket name, see if we have a group yet
		if($bucket_name && !$group_name && $model->getRouteGroup()) {
			$buckets = $model->getRouteGroup()->getBuckets();
			$bucket_name = DevblocksPlatform::strLower($bucket_name);
			
			$bucket_names = array_change_key_case(
				array_column($buckets, 'id', 'name'),
				CASE_LOWER
			);
			
			if(array_key_exists($bucket_name, $bucket_names)) {
				if(null != ($model->setRouteBucket($buckets[$bucket_names[$bucket_name]])))
					DevblocksPlatform::noop();
			}
		
		} else if($bucket_name && $group_name) {
			$group_id = DAO_Group::getByName($group_name);
			
			if(null != ($model->setRouteGroup($group_id))) {
				$buckets = $model->getRouteGroup()->getBuckets();
				$bucket_name = DevblocksPlatform::strLower($bucket_name);
				
				$bucket_names = array_change_key_case(
					array_column($buckets, 'id', 'name'),
					CASE_LOWER
				);
				
				if(array_key_exists($bucket_name, $bucket_names)) {
					if(null != ($model->setRouteBucket($buckets[$bucket_names[$bucket_name]])))
						DevblocksPlatform::noop();
				}
			}
			
		} elseif ($group_name) {
			if(null != ($model->setRouteGroup(DAO_Group::getByName($group_name))))
				DevblocksPlatform::noop();
		}
	}
};