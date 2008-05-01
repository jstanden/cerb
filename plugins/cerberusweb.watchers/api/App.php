<?php
$path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR;

class ChWatchersPlugin extends DevblocksPlugin {
	const WORKER_PREF_ASSIGN_EMAIL = 'watchers_assign_email';
	
	function load(DevblocksPluginManifest $manifest) {
	}
};

class ChWatchersEventListener extends DevblocksEventListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
        switch($event->id) {
            case 'worker.delete':
				$this->_workerDeleted($event);
            	break;
            	
            case 'ticket.property.pre_change':
				$this->_workerAssigned($event);
            	break;
            	
            case 'ticket.reply.inbound':
				$this->_sendForwards($event, true);
            	break;
            	
            case 'ticket.reply.outbound':
				$this->_sendForwards($event, false);
            	break;
        }
    }

    private function _workerAssigned($event) {
    	@$ticket_ids = $event->params['ticket_ids'];
    	@$changed_fields = $event->params['changed_fields'];
    	
    	/*
    	 * [TODO] Refuse to send notifications to the helpdesk
    	 */
    	
    	if(empty($ticket_ids) || empty($changed_fields))
    		return;
    		
    	@$next_worker_id = $changed_fields[DAO_Ticket::NEXT_WORKER_ID];

    	// Make sure a next worker was assigned
    	if(empty($next_worker_id))
    		return;

    	@$active_worker = CerberusApplication::getActiveWorker();
    		
    	// Make sure we're not assigning work to ourselves, if so then bail
    	if(null != $active_worker && $active_worker->id == $next_worker_id) {
    		return;
    	}
    		
		$mail_service = DevblocksPlatform::getMailService();
		$mailer = $mail_service->getMailer();
    		
    	$settings = CerberusSettings::getInstance();
		$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM, '');
		$default_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL, '');

		@$worker_prefs = DAO_WorkerPref::getSettings($next_worker_id);
		@$worker_notify_email = $worker_prefs[$next_worker_id][ChWatchersPlugin::WORKER_PREF_ASSIGN_EMAIL];

		// If our next worker doesn't have an assignment pref
		if(empty($worker_notify_email))
			return;

		// Send notifications to this worker for each ticket
		$tickets = DAO_Ticket::getTickets($ticket_ids);

		foreach($tickets as $ticket) { /* @var $ticket CerberusTicket */
			// If the next worker value didn't change, skip
			if($ticket->next_worker_id == $next_worker_id)
				continue;
			
			if(null == (@$last_message = end($ticket->getMessages()))) /* @var $last_message CerberusMessage */
				continue;
			
			if(null == (@$last_headers = $last_message->getHeaders()))
				continue;
				
	    	try {
		 		// Create the message
				$rcpt_to = new Swift_RecipientList();
				$a_rcpt_to = array();
				$mail_from = new Swift_Address($default_from, $default_personal);
				$rcpt_to->addTo($worker_notify_email);
				$a_rcpt_to = new Swift_Address($worker_notify_email);
					
				$mail = $mail_service->createMessage();
				$mail->setTo($a_rcpt_to);
				$mail->setFrom($mail_from);
				$mail->setReplyTo($default_from);
				$mail->setSubject(sprintf("[assignment #%s]: %s",
					$ticket->mask,
					$ticket->subject
				));
			
				if(false !== (@$in_reply_to = $last_headers['in-reply-to'])) {
				    $mail->headers->set('References', $in_reply_to);
				    $mail->headers->set('In-Reply-To', $in_reply_to);
				}
				
				$mail->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
				$mail->headers->set('Precedence','List');
				$mail->headers->set('Auto-Submitted','auto-generated');
				$mail->attach(new Swift_Message_Part($last_message->getContent(), 'text/plain', 'base64', 'ISO-8859-1'));
			
				$mailer->send($mail, $rcpt_to, $mail_from);
				
	    	} catch(Exception $e) {
	    		//
			}
			
		}
			
    }
    
    private function _workerDeleted($event) {
    	@$worker_id = $event->params['worker_id'];
    	
    	DAO_WorkerMailForward::deleteByWorkerIds($worker_id);
    }
    
    private function _sendForwards($event, $is_inbound) {
        @$ticket_id = $event->params['ticket_id'];
        @$message_id = $event->params['message_id'];
        @$send_worker_id = $event->params['worker_id'];
    	
		$ticket = DAO_Ticket::getTicket($ticket_id);
		$message = DAO_Ticket::getMessage($message_id);
		$headers = $message->getHeaders();
		
		// [JAS]: Don't send obvious spam to watchers.
		if($ticket->spam_score > 0.9000) {
			return true;
		}
		
		// The whole flipping Swift section needs wrapped to catch exceptions
		try {
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer();
	
			$settings = CerberusSettings::getInstance();
			$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM, '');
			$default_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL, '');
			
	//		$group_settings = DAO_GroupSettings::getSettings(); // Cache
			
			$sender = DAO_Address::get($message->address_id);
	
			$sender_email = strtolower($sender->email);
			$sender_split = explode('@', $sender_email);
	
			if(!is_array($sender_split) || count($sender_split) != 2)
				return;
	
			// If return-path is blank
			if(isset($headers['return-path']) && $headers['return-path'] == '<>')
				return;
				
			// Ignore bounces
			if($sender_split[1]=="postmaster" || $sender_split[1] == "mailer-daemon")
				return;
			
			// Ignore autoresponses autoresponses
			if(isset($headers['auto-submitted']) && $headers['auto-submitted'] != 'no')
				return;
				
			// Headers
			//==========
	
			$send_to = array();
			
			@$notifications = DAO_WorkerMailForward::getWhere(sprintf("%s = %d",
				DAO_WorkerMailForward::GROUP_ID,
				$ticket->team_id
			));
					
			// Build mailing list
			foreach($notifications as $n) { /* @var $n Model_WorkerMailForward */
				if(!isset($n->group_id) || !isset($n->bucket_id))
					continue;
				
				if($n->group_id == $ticket->team_id && ($n->bucket_id==-1 || $n->bucket_id==$ticket->category_id)) {
					// Event checking
					if(($is_inbound && ($n->event=='i' || $n->event=='io'))
						|| (!$is_inbound && ($n->event=='o' || $n->event=='io'))
						|| ($is_inbound && $n->event=='r' && $ticket->next_worker_id==$n->worker_id)) {
						$send_to[$n->email] = true;
					}
				}
	    	}
	    	
	    	// Send copies
			if(is_array($send_to) && !empty($send_to))
			foreach($send_to as $to => $bool) {
				// Proxy the message
				$rcpt_to = new Swift_RecipientList();
				$a_rcpt_to = array();
				$mail_from = new Swift_Address($sender->email);
				$rcpt_to->addTo($to);
				$a_rcpt_to = new Swift_Address($to);
				
				$mail = $mail_service->createMessage();
				$mail->setTo($a_rcpt_to);
				$mail->setFrom($mail_from);
				$mail->setReplyTo($default_from);
				$mail->setSubject(sprintf("[%s #%s]: %s",
					($is_inbound ? 'inbound' : 'outbound'),
					$ticket->mask,
					$ticket->subject
				));
				
				if(false !== (@$msgid = $headers['message-id'])) {
					$mail->headers->set('Message-Id',$msgid);
				}
				
				if(false !== (@$in_reply_to = $headers['in-reply-to'])) {
				    $mail->headers->set('References', $in_reply_to);
				    $mail->headers->set('In-Reply-To', $in_reply_to);
				}
				
				$mail->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
				$mail->headers->set('Precedence','List');
				$mail->headers->set('Auto-Submitted','auto-generated');
				$mail->attach(new Swift_Message_Part($message->getContent(), 'text/plain', 'base64', 'ISO-8859-1'));
				
				// [TODO] Send attachments with watcher
			
				$mailer->send($mail,$rcpt_to,$mail_from);
			}
		}
		catch(Exception $e) {
			$fields = array(
				DAO_MessageNote::MESSAGE_ID => $message_id,
				DAO_MessageNote::CREATED => time(),
				DAO_MessageNote::WORKER_ID => 0,
				DAO_MessageNote::CONTENT => 'Exception thrown while sending watcher email: ' . $e->getMessage(),
				DAO_MessageNote::TYPE => Model_MessageNote::TYPE_ERROR,
			);
			DAO_MessageNote::create($fields);
		}
    }
};

class ChWatchersPreferences extends Extension_PreferenceTab {
	private $tpl_path = null; 
	
    function __construct($manifest) {
        parent::__construct($manifest);
        $this->tpl_path = realpath(dirname(__FILE__).'/../templates');
    }
	
	// Ajax
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		$tpl->cache_lifetime = "0";
		
		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		$memberships = $worker->getMemberships();
		$tpl->assign('memberships', $memberships);
		
		$addresses = DAO_AddressToWorker::getByWorker($worker->id);
		$tpl->assign('addresses', $addresses);
		
		@$notifications = DAO_WorkerMailForward::getWhere(sprintf("%s = %d",
			DAO_WorkerMailForward::WORKER_ID,
			$worker->id
		));
		$tpl->assign('notifications', $notifications);
		
		$assign_notify_email = DAO_WorkerPref::get($worker->id, ChWatchersPlugin::WORKER_PREF_ASSIGN_EMAIL, '');
		$tpl->assign('assign_notify_email', $assign_notify_email);
		
		$tpl->display('file:' . $this->tpl_path . '/preferences/watchers.tpl.php');
	}
    
	// Post
	function saveTab() {
		@$forward_bucket = DevblocksPlatform::importGPC($_REQUEST['forward_bucket'],'string', '');
		@$forward_address = DevblocksPlatform::importGPC($_REQUEST['forward_address'],'string', '');
		@$forward_event = DevblocksPlatform::importGPC($_REQUEST['forward_event'],'string', '');

		$worker = CerberusApplication::getActiveWorker();
		
		// Delete forwards
		@$forward_deletes = DevblocksPlatform::importGPC($_REQUEST['forward_deletes'],'array', array());
		if(!empty($forward_deletes))
			DAO_WorkerMailForward::delete($forward_deletes);
		
		// Add forward
		if(!empty($forward_bucket) && !empty($forward_address) && !empty($forward_event)) {
			@list($group_id, $bucket_id) = split('_', $forward_bucket);
			if(is_null($group_id) || is_null($bucket_id))
				break;
			
			$fields = array(
				DAO_WorkerMailForward::WORKER_ID => $worker->id,
				DAO_WorkerMailForward::GROUP_ID => $group_id,
				DAO_WorkerMailForward::BUCKET_ID => $bucket_id,
				DAO_WorkerMailForward::EMAIL => $forward_address,
				DAO_WorkerMailForward::EVENT => $forward_event,
			);
			DAO_WorkerMailForward::create($fields);
		}
		
		// Assignment notifications
		@$assign_notify_email = DevblocksPlatform::importGPC($_REQUEST['assign_notify_email'],'string', '');
		DAO_WorkerPref::set($worker->id, ChWatchersPlugin::WORKER_PREF_ASSIGN_EMAIL, $assign_notify_email);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences','notifications')));
	}
};

class DAO_WorkerMailForward extends DevblocksORMHelper {
	const ID = 'id';
	const WORKER_ID = 'worker_id';
	const GROUP_ID = 'group_id';
	const BUCKET_ID = 'bucket_id';
	const EMAIL = 'email';
	const EVENT = 'event';
	
	public static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO worker_mail_forward (id, worker_id, group_id, bucket_id, email, event) ".
			"VALUES (%d, 0, 0, 0, '', '')",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	/**
	 * @return Model_WorkerMailForward[]
	 */
	public static function getWhere($where) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, worker_id, group_id, bucket_id, email, event ".
			"FROM worker_mail_forward ".
			(!empty($where)?sprintf("WHERE %s ",$where):" ").
			"ORDER BY worker_id, id "
			;
		$rs = $db->Execute($sql);
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	static private function _createObjectsFromResultSet($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
		    $object = new Model_WorkerMailForward();
		    $object->id = intval($rs->fields['id']);
		    $object->worker_id = intval($rs->fields['worker_id']);
		    $object->group_id = intval($rs->fields['group_id']);
		    $object->bucket_id = intval($rs->fields['bucket_id']);
		    $object->email = $rs->fields['email'];
		    $object->event = $rs->fields['event'];
		    $objects[$object->id] = $object;
		    $rs->MoveNext();
		}
		
		return $objects;
	}
			
	public static function update($ids, $fields) {
		parent::_update($ids, 'worker_mail_forward', $fields);
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM worker_mail_forward WHERE id IN (%s)", $ids_list));
	}
	
	public static function deleteByWorkerIds($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM worker_mail_forward WHERE worker_id IN (%s)", $ids_list));
	}
};

class Model_WorkerMailForward {
	public $id = '';
	public $worker_id = '';
	public $group_id = '';
	public $bucket_id = '';
	public $email = '';
	public $event = '';
};

?>