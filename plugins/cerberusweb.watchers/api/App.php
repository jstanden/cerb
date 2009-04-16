<?php
class ChWatchersEventListener extends DevblocksEventListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
        switch($event->id) {
            case 'bucket.delete':
				$this->_bucketDeleted($event);
            	break;
            	
            case 'group.delete':
				$this->_groupDeleted($event);
            	break;

            case 'ticket.property.pre_change':
				$this->_workerAssigned($event);
            	break;
				
			case 'ticket.comment.create':
				$this->_newTicketComment($event);
				break;
				
            case 'ticket.reply.inbound':
				$this->_sendForwards($event, true);
            	break;
            	
            case 'ticket.reply.outbound':
				$this->_sendForwards($event, false);
            	break;
            	
            case 'worker.delete':
				$this->_workerDeleted($event);
            	break;
        }
    }

	private function _getMailingListFromMatches($matches) {
		$workers = DAO_Worker::getAllActive();
		$helpdesk_senders = CerberusApplication::getHelpdeskSenders();
		
		$notify_emails = array();
		
		if(is_array($matches))
		foreach($matches as $filter) {
			if(!$filter instanceof Model_WatcherMailFilter)
				continue;
			
			// If the worker no longer exists or is disabled
			if(!isset($workers[$filter->worker_id]))
				continue;
				
			if(isset($filter->actions['email']['to']) && is_array($filter->actions['email']['to']))
			foreach($filter->actions['email']['to'] as $addy) {
				$addy = strtolower($addy);
				
				// Don't allow a worker to usurp a helpdesk address
				if(isset($helpdesk_senders[$addy]))
					continue;
				
				if(!isset($notify_emails[$addy]))
					$notify_emails[$addy] = $addy;
			}
		}
		
		return $notify_emails;
	}

	private function _newTicketComment($event) {
		@$comment_id = $event->params['comment_id'];
		@$ticket_id = $event->params['ticket_id'];
		@$address_id = $event->params['address_id'];
		@$comment = $event->params['comment'];
    	
    	if(empty($ticket_id) || empty($address_id) || empty($comment))
    		return;
    		
		// Resolve the address ID
		if(null == ($address = DAO_Address::get($address_id)))
			return;
			
		// Try to associate the author with a worker
		if(null == ($worker_addy = DAO_AddressToWorker::getByAddress($address->email)))
			return;
				
		if(null == ($worker = DAO_Worker::getAgent($worker_addy->worker_id)))
			return;
			
		$mail_service = DevblocksPlatform::getMailService();
		$mailer = null; // lazy load
    		
    	$settings = CerberusSettings::getInstance();
		$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM, '');
		$default_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL, '');

		if(null == ($ticket = DAO_Ticket::getTicket($ticket_id)))
			return;

		// Find all our matching filters
		if(false == ($matches = Model_WatcherMailFilter::getMatches(
			$ticket,
			'ticket_comment'
		)))
			return;
		
		// Remove any matches from the author
		foreach($matches as $idx => $filter) {
			if($filter->worker_id == $worker_addy->worker_id)
				unset($matches[$idx]);
		}
		
		// Sanitize and combine all the destination addresses
		$notify_emails = $this->_getMailingListFromMatches($matches);
		
		if(empty($notify_emails))
			return;
			
		if(null == (@$last_message = end($ticket->getMessages()))) { /* @var $last_message CerberusMessage */
			continue;
		}
		
		if(null == (@$last_headers = $last_message->getHeaders()))
			continue;
			
		$reply_to = $default_from;
		$reply_personal = $default_personal;
			
		// See if we need a group-specific reply-to
		if(!empty($ticket->team_id)) {
			@$group_from = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_FROM);
			if(!empty($group_from))
				$reply_to = $group_from;
				
			@$group_personal = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_PERSONAL);
			if(!empty($group_personal))
				$reply_personal = $group_personal;
		}
		
		if(is_array($notify_emails))
		foreach($notify_emails as $send_to) {
	    	try {
	    		if(null == $mailer)
					$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
				
		 		// Create the message
				$rcpt_to = new Swift_RecipientList();
				$a_rcpt_to = array();
				$mail_from = new Swift_Address($reply_to, $reply_personal);
				$rcpt_to->addTo($send_to);
				$a_rcpt_to = new Swift_Address($send_to);
					
				$mail = $mail_service->createMessage();
				$mail->setTo($a_rcpt_to);
				$mail->setFrom($mail_from);
				$mail->setReplyTo($reply_to);
				$mail->setSubject(sprintf("[comment #%s]: %s [comment]",
					$ticket->mask,
					$ticket->subject
				));
			
				if(false !== (@$in_reply_to = $last_headers['in-reply-to'])) {
				    $mail->headers->set('References', $in_reply_to);
				    $mail->headers->set('In-Reply-To', $in_reply_to);
				}
				
				// Build the body
				$comment_text = sprintf("%s (%s) comments:\r\n%s\r\n",
					$worker->getName(),
					$address->email,
					$comment
				);
				
				$mail->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
				$mail->headers->set('Precedence','List');
				$mail->headers->set('Auto-Submitted','auto-generated');
				
				$mail->attach(new Swift_Message_Part($comment_text, 'text/plain', 'base64', LANG_CHARSET_CODE));
			
				$mailer->send($mail, $rcpt_to, $mail_from);
				
	    	} catch(Exception $e) {
	    		//
			}
		}
	}

    private function _workerAssigned($event) {
    	@$ticket_ids = $event->params['ticket_ids'];
    	@$changed_fields = $event->params['changed_fields'];
    	
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

    	// Make sure the worker exists and is not disabled
//    	if(!isset($workers[$next_worker_id]))
//    		return;
    	
		$mail_service = DevblocksPlatform::getMailService();
		$mailer = null; // lazy load
    		
    	$settings = CerberusSettings::getInstance();
		$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM, '');
		$default_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL, '');

		// Loop through all assigned tickets
		$tickets = DAO_Ticket::getTickets($ticket_ids);
		foreach($tickets as $ticket) { /* @var $ticket CerberusTicket */
			// If the next worker value didn't change, skip
			if($ticket->next_worker_id == $next_worker_id)
				continue;
			
			// Find all our matching filters
			if(false == ($matches = Model_WatcherMailFilter::getMatches(
				$ticket,
				'ticket_assignment',
				$next_worker_id
			)))
				return;
				
			// Sanitize and combine all the destination addresses
			$notify_emails = $this->_getMailingListFromMatches($matches);
			
			if(empty($notify_emails))
				return;
				
			if(null == (@$last_message = end($ticket->getMessages()))) { /* @var $last_message CerberusMessage */
				continue;
			}
			
			if(null == (@$last_headers = $last_message->getHeaders()))
				continue;
				
			$reply_to = $default_from;
			$reply_personal = $default_personal;
				
			// See if we need a group-specific reply-to
			if(!empty($ticket->team_id)) {
				@$group_from = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_FROM);
				if(!empty($group_from))
					$reply_to = $group_from;
					
				@$group_personal = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_PERSONAL);
				if(!empty($group_personal))
					$reply_personal = $group_personal;
			}
			
			if(is_array($notify_emails))
			foreach($notify_emails as $send_to) {
		    	try {
		    		if(null == $mailer)
						$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
					
			 		// Create the message
					$rcpt_to = new Swift_RecipientList();
					$a_rcpt_to = array();
					$mail_from = new Swift_Address($reply_to, $reply_personal);
					$rcpt_to->addTo($send_to);
					$a_rcpt_to = new Swift_Address($send_to);
						
					$mail = $mail_service->createMessage();
					$mail->setTo($a_rcpt_to);
					$mail->setFrom($mail_from);
					$mail->setReplyTo($reply_to);
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
					$mail->attach(new Swift_Message_Part($last_message->getContent(), 'text/plain', 'base64', LANG_CHARSET_CODE));
				
					$mailer->send($mail, $rcpt_to, $mail_from);
					
		    	} catch(Exception $e) {
		    		//
				}
			}
		}
    }
    
    private function _workerDeleted($event) {
    	@$worker_ids = $event->params['worker_ids'];
    	DAO_WatcherMailFilter::deleteByWorkerIds($worker_ids);
    }
    
    private function _bucketDeleted($event) {
    	@$bucket_ids = $event->params['bucket_ids'];
    	DAO_WatcherMailFilter::deleteByBucketIds($bucket_ids);
    }
    
    private function _groupDeleted($event) {
    	@$group_ids = $event->params['group_ids'];
    	DAO_WatcherMailFilter::deleteByGroupIds($group_ids);
    }
    
    private function _sendForwards($event, $is_inbound) {
        @$ticket_id = $event->params['ticket_id'];
        @$send_worker_id = $event->params['worker_id'];
    	
		$ticket = DAO_Ticket::getTicket($ticket_id);

		// Find all our matching filters
		if(false == ($matches = Model_WatcherMailFilter::getMatches(
			$ticket,
			($is_inbound ? 'mail_incoming' : 'mail_outgoing')
		)))
			return;
		
		// Sanitize and combine all the destination addresses
		$notify_emails = $this->_getMailingListFromMatches($matches);
		
		if(empty($notify_emails))
			return;
		
		// [TODO] This could be more efficient
		$messages = DAO_Ticket::getMessagesByTicket($ticket_id);
		$message = end($messages); // last message
		unset($messages);
		$headers = $message->getHeaders();
			
		// The whole flipping Swift section needs wrapped to catch exceptions
		try {
			$settings = CerberusSettings::getInstance();
			$reply_to = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM, '');
			
			// See if we need a group-specific reply-to
			if(!empty($ticket->team_id)) {
				@$group_from = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_FROM, '');
				if(!empty($group_from))
					$reply_to = $group_from;
			}
			
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
				
			// Attachments
			$attachments = $message->getAttachments();
			$mime_attachments = array();
			if(is_array($attachments))
			foreach($attachments as $attachment) {
				if(0 == strcasecmp($attachment->display_name,'original_message.html'))
					continue;
					
				$attachment_path = APP_STORAGE_PATH . '/attachments/'; // [TODO] This is highly redundant in the codebase
				if(!file_exists($attachment_path . $attachment->filepath))
					continue;
				
				$file =& new Swift_File($attachment_path . $attachment->filepath);
				$mime_attachments[] =& new Swift_Message_Attachment($file, $attachment->display_name, $attachment->mime_type);
			}
	    	
	    	// Send copies
			if(is_array($notify_emails) && !empty($notify_emails)) {
				$mail_service = DevblocksPlatform::getMailService();
				$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
				
				foreach($notify_emails as $to) {
					// Proxy the message
					$rcpt_to = new Swift_RecipientList();
					$a_rcpt_to = array();
					$mail_from = new Swift_Address($sender->email);
					$rcpt_to->addTo($to);
					$a_rcpt_to = new Swift_Address($to);
					
					$mail = $mail_service->createMessage(); /* @var $mail Swift_Message */
					$mail->setTo($a_rcpt_to);
					$mail->setFrom($mail_from);
					$mail->setReplyTo($reply_to);
					$mail->setReturnPath($reply_to);
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
					$mail->attach(new Swift_Message_Part($message->getContent(), 'text/plain', 'base64', LANG_CHARSET_CODE));
	
					// Send message attachments with watcher
					if(is_array($mime_attachments))
					foreach($mime_attachments as $mime_attachment) {
						$mail->attach($mime_attachment);
					}
				
					$mailer->send($mail,$rcpt_to,$mail_from);
				}
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
	private $_TPL_PATH = null; 
	
    function __construct($manifest) {
        parent::__construct($manifest);
        $this->_TPL_PATH = dirname(dirname(__FILE__)).'/templates/';
    }
	
	// Ajax
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->cache_lifetime = "0";
		
		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Filters
		@$filters = DAO_WatcherMailFilter::getWhere(sprintf("%s = %d",
			DAO_WatcherMailFilter::WORKER_ID,
			$worker->id
		));
		$tpl->assign('filters', $filters);
		
		// Custom Field Sources
		$source_manifests = DevblocksPlatform::getExtensions('cerberusweb.fields.source', false);
		$tpl->assign('source_manifests', $source_manifests);

		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'preferences/watchers.tpl');
	}
    
	// Post
	function saveTab() {
		$worker = CerberusApplication::getActiveWorker();
		
		// Delete forwards
		@$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array', array());
		if(!empty($deletes))
			DAO_WatcherMailFilter::delete($deletes);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences','notifications')));
	}
	
	// Ajax
	function showWatcherPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($filter = DAO_WatcherMailFilter::get($id))) {
			$tpl->assign('filter', $filter);
		}
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);

		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		$memberships = $active_worker->getMemberships();
		$tpl->assign('memberships', $memberships);
		
		$addresses = DAO_AddressToWorker::getByWorker($active_worker->id);
		$tpl->assign('addresses', $addresses);

		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);

		// Custom Fields: Ticket
		$ticket_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$tpl->assign('ticket_fields', $ticket_fields);

		// Custom Fields: Address
		$address_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
		$tpl->assign('address_fields', $address_fields);
		
		// Custom Fields: Orgs
		$org_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);
		$tpl->assign('org_fields', $org_fields);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'preferences/peek.tpl');
	}
	
	function saveWatcherPanelAction() {
   		$translate = DevblocksPlatform::getTranslationService();
   		
   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
   		
	    @$active_worker = CerberusApplication::getActiveWorker();
//	    if(!$active_worker->is_superuser)
//	    	return;

	    /*****************************/
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
		
		if(empty($name))
			$name = $translate->_('Notification');
		
		$criterion = array();
		$actions = array();
		
		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		
		// Criteria
		if(is_array($rules))
		foreach($rules as $rule) {
			$rule = DevblocksPlatform::strAlphaNumDash($rule);
			@$value = DevblocksPlatform::importGPC($_POST['value_'.$rule],'string','');
			
			// [JAS]: Allow empty $value (null/blank checking)
			
			$criteria = array(
				'value' => $value,
			);
			
			// Any special rule handling
			switch($rule) {
				case 'dayofweek':
					// days
					$days = DevblocksPlatform::importGPC($_REQUEST['value_dayofweek'],'array',array());
					if(in_array(0,$days)) $criteria['sun'] = 'Sunday';
					if(in_array(1,$days)) $criteria['mon'] = 'Monday';
					if(in_array(2,$days)) $criteria['tue'] = 'Tuesday';
					if(in_array(3,$days)) $criteria['wed'] = 'Wednesday';
					if(in_array(4,$days)) $criteria['thu'] = 'Thursday';
					if(in_array(5,$days)) $criteria['fri'] = 'Friday';
					if(in_array(6,$days)) $criteria['sat'] = 'Saturday';
					unset($criteria['value']);
					break;
				case 'timeofday':
					$from = DevblocksPlatform::importGPC($_REQUEST['timeofday_from'],'string','');
					$to = DevblocksPlatform::importGPC($_REQUEST['timeofday_to'],'string','');
					$criteria['from'] = $from;
					$criteria['to'] = $to;
					unset($criteria['value']);
					break;
				case 'event':
					@$events = DevblocksPlatform::importGPC($_REQUEST['value_event'],'array',array());
					if(is_array($events))
					foreach($events as $event)
						$criteria[$event] = true;
					unset($criteria['value']);
					break;
				case 'groups':
					@$groups = DevblocksPlatform::importGPC($_REQUEST['value_groups'],'array',array());
					if(is_array($groups) && !empty($groups)) {
						$criteria['groups'] = array();
						
						foreach($groups as $group_id) {
							@$all = DevblocksPlatform::importGPC($_REQUEST['value_group'.$group_id.'_all'],'integer',0);
							
							// Did we only want to watch specific buckets in this group?
							$bucket_ids = array();
							if(!$all)
								@$bucket_ids = DevblocksPlatform::importGPC($_REQUEST['value_group'.$group_id.'_buckets'],'array',array());
							
							// Add to criteria (key=group id, val=array of bucket ids)
							$criteria['groups'][$group_id] = $bucket_ids;
						}					
					}
					unset($criteria['value']);
					break;
				case 'next_worker_id':
					break;
				case 'subject':
					break;
				case 'from':
					break;
//				case 'tocc':
//					break;
				case 'header1':
				case 'header2':
				case 'header3':
				case 'header4':
				case 'header5':
					if(null != (@$header = DevblocksPlatform::importGPC($_POST[$rule],'string',null)))
						$criteria['header'] = strtolower($header);
					break;
				case 'body':
					break;
				default: // ignore invalids // [TODO] Very redundant
					// Custom fields
					if("cf_" == substr($rule,0,3)) {
						$field_id = intval(substr($rule,3));
						
						if(!isset($custom_fields[$field_id]))
							continue;

						// [TODO] Operators
							
						switch($custom_fields[$field_id]->type) {
							case 'S': // string
							case 'T': // clob
							case 'U': // URL
								@$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','regexp');
								$criteria['oper'] = $oper;
								break;
							case 'D': // dropdown
							case 'M': // multi-dropdown
							case 'X': // multi-checkbox
							case 'W': // worker
								@$in_array = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id],'array',array());
								$out_array = array();
								
								// Hash key on the option for quick lookup later
								if(is_array($in_array))
								foreach($in_array as $k => $v) {
									$out_array[$v] = $v;
								}
								
								$criteria['value'] = $out_array;
								break;
							case 'E': // date
								@$from = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_from'],'string','0');
								@$to = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_to'],'string','now');
								$criteria['from'] = $from;
								$criteria['to'] = $to;
								unset($criteria['value']);
								break;
							case 'N': // number
								@$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','=');
								$criteria['oper'] = $oper;
								$criteria['value'] = intval($value);
								break;
							case 'C': // checkbox
								$criteria['value'] = intval($value);
								break;
						}
						
					} else {
						continue;
					}
					
					break;
			}
			
			$criterion[$rule] = $criteria;
		}
		
		// Actions
		if(is_array($do))
		foreach($do as $act) {
			$action = array();
			
			switch($act) {
				// Forward a copy to...
				case 'email':
					@$emails = DevblocksPlatform::importGPC($_REQUEST['do_email'],'array',array());
					if(!empty($emails)) {
						$action = array(
							'to' => $emails
						);
					}
					break;
			}
			
			$actions[$act] = $action;
		}

   		$fields = array(
   			DAO_WatcherMailFilter::NAME => $name,
   			DAO_WatcherMailFilter::CRITERIA_SER => serialize($criterion),
   			DAO_WatcherMailFilter::ACTIONS_SER => serialize($actions),
   		);

   		// Create
   		if(empty($id)) {
   			$fields[DAO_WatcherMailFilter::POS] = 0;
   			$fields[DAO_WatcherMailFilter::WORKER_ID] = $active_worker->id;
	   		$id = DAO_WatcherMailFilter::create($fields);
	   		
	   	// Update
   		} else {
   			DAO_WatcherMailFilter::update($id, $fields);
   		}
   		
   		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences','notifications')));
	}
	
};

class DAO_WatcherMailFilter extends DevblocksORMHelper {
	const ID = 'id';
	const POS = 'pos';
	const NAME = 'name';
	const CREATED = 'created';
	const WORKER_ID = 'worker_id';
	const CRITERIA_SER = 'criteria_ser';
	const ACTIONS_SER = 'actions_ser';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO watcher_mail_filter (id,created) ".
			"VALUES (%d,%d)",
			$id,
			time()
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'watcher_mail_filter', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_WatcherMailFilter[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, pos, name, created, worker_id, criteria_ser, actions_ser ".
			"FROM watcher_mail_filter ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY pos DESC";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WatcherMailFilter	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param ADORecordSet $rs
	 * @return Model_WatcherMailFilter[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_WatcherMailFilter();
			$object->id = $rs->fields['id'];
			$object->pos = $rs->fields['pos'];
			$object->name = $rs->fields['name'];
			$object->created = $rs->fields['created'];
			$object->worker_id = $rs->fields['worker_id'];
			
			if(null != (@$criteria_ser = $rs->fields['criteria_ser']))
				if(false === (@$object->criteria = unserialize($criteria_ser)))
					$object->criteria = array();

			if(null != (@$actions_ser = $rs->fields['actions_ser']))
				if(false === ($object->actions = unserialize($actions_ser)))
					$object->actions = array();
			
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	public static function increment($id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("UPDATE watcher_mail_filter SET pos = pos + 1 WHERE id = %d",
			$id
		));
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM watcher_mail_filter WHERE id IN (%s)", $ids_list));
		
		return true;
	}

	private static function _deleteWhere($where) {
		if(empty($where))
			return FALSE;
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM watcher_mail_filter WHERE %s", $where);
		$db->Execute($sql);
	}


	public static function deleteByWorkerIds($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		self::_deleteWhere(sprintf("%s IN (%s)",
			self::WORKER_ID,
			implode(',', $ids)
		));
	}
	
	public static function deleteByGroupIds($ids) {
		if(!is_array($ids)) $ids = array($ids);

		// [TODO] use cache
		$filters = self::getWhere();
		foreach($filters as $filter_id => $filter) {
			if(!isset($filter->criteria['groups']))
				continue;
				
			// If we're using the group being nuked...
			$changed = false;
			foreach($ids as $group_id) {
				if(isset($filter->criteria['groups']['groups'][$group_id])) {
					unset($filter->criteria['groups']['groups'][$group_id]);
					$changed = true;
				}
			}
			
			// If we changed the criteria of a filter, save it
			if($changed) {
				$fields = array(
					DAO_WatcherMailFilter::CRITERIA_SER => serialize($filter->criteria),
				);
				DAO_WatcherMailFilter::update($filter->id, $fields);
			}
		}
		
		// [TODO] invalidate cache
	}

	public static function deleteByBucketIds($ids) {
		if(!is_array($ids)) $ids = array($ids);

		// [TODO] use cache
		$filters = self::getWhere();
		foreach($filters as $filter_id => $filter) {
			if(!isset($filter->criteria['groups']['groups']))
				continue;	
			
			// If we're using the bucket being nuked...
			$changed = false;
			foreach($filter->criteria['groups']['groups'] as $group_id => $buckets) {
				foreach($ids as $bucket_id) {
					if(false !== ($pos = array_search($bucket_id, $buckets))) {
						unset($filter->criteria['groups']['groups'][$group_id][$pos]);
						$changed = true;
					}
				}
			}
			
			if($changed) {
				$fields = array(
					DAO_WatcherMailFilter::CRITERIA_SER => serialize($filter->criteria),
				);
				DAO_WatcherMailFilter::update($filter->id, $fields);
			}
		}
		
		// [TODO] invalidate cache
	}
	
};

class Model_WatcherMailFilter {
	public $id;
	public $pos;
	public $name;
	public $created;
	public $worker_id;
	public $criteria;
	public $actions;
	
	/**
	 * @return Model_WatcherMailFilter[]|false
	 */
	static function getMatches(CerberusTicket $ticket, $event, $only_worker_id=null) {
		$matches = array();
		
		if(!empty($only_worker_id)) {
			$filters = DAO_WatcherMailFilter::getWhere(sprintf("%s = %d",
				DAO_WatcherMailFilter::WORKER_ID,
				$only_worker_id
			));
		} else {
			$filters = DAO_WatcherMailFilter::getWhere();
		}

		// [JAS]: Don't send obvious spam to watchers.
		if($ticket->spam_score >= 0.9000)
			return false;
			
		// Build our objects
		$ticket_from = DAO_Address::get($ticket->last_wrote_address_id);
		$ticket_group_id = $ticket->team_id;
		
		// [TODO] These expensive checks should only populate when needed
		$messages = DAO_Ticket::getMessagesByTicket($ticket->id);
		$message_headers = array();

		if(empty($messages))
			return false;
		
		if(null != (@$message_last = array_pop($messages))) { /* @var $message_last CerberusMessage */
			$message_headers = $message_last->getHeaders();
		}

		// Clear the rest of the message manifests
		unset($messages);
		
		$custom_fields = DAO_CustomField::getAll();
		
		// Lazy load when needed on criteria basis
		$ticket_field_values = null;
		$address_field_values = null;
		$org_field_values = null;
		
		// Worker memberships (for checking permissions)
		$workers = DAO_Worker::getAll();
		$group_rosters = DAO_Group::getRosters();
		
		// Check filters
		if(is_array($filters))
		foreach($filters as $filter) { /* @var $filter Model_WatcherMailFilter */
			$passed = 0;

			// check the worker's group memberships
			if(!isset($workers[$filter->worker_id]) // worker doesn't exist 
				|| (!$workers[$filter->worker_id]->is_superuser  // no a superuser
					&& !isset($group_rosters[$ticket->team_id][$filter->worker_id]))) { // no membership
				continue;
			}

			// check criteria
			foreach($filter->criteria as $rule_key => $rule) {
				@$value = $rule['value'];
							
				switch($rule_key) {
					case 'dayofweek':
						$current_day = strftime('%w');
						//$current_day = 1;

						// Forced to English abbrevs as indexes
						$days = array('sun','mon','tue','wed','thu','fri','sat');
						
						// Is the current day enabled?
						if(isset($rule[$days[$current_day]])) {
							$passed++;
						}
							
						break;
						
					case 'timeofday':
						$current_hour = strftime('%H');
						$current_min = strftime('%M');
						//$current_hour = 17;
						//$current_min = 5;

						if(null != ($from_time = @$rule['from']))
							list($from_hour, $from_min) = split(':', $from_time);
						
						if(null != ($to_time = @$rule['to']))
							if(list($to_hour, $to_min) = split(':', $to_time));

						// Do we need to wrap around to the next day's hours?
						if($from_hour > $to_hour) { // yes
							$to_hour += 24; // add 24 hrs to the destination (1am = 25th hour)
						}
							
						// Are we in the right 24 hourly range?
						if((integer)$current_hour >= $from_hour && (integer)$current_hour <= $to_hour) {
							// If we're in the first hour, are we minutes early?
							if($current_hour==$from_hour && (integer)$current_min < $from_min)
								break;
							// If we're in the last hour, are we minutes late?
							if($current_hour==$to_hour && (integer)$current_min > $to_min)
								break;
								
							$passed++;
						}
						break;
						
					case 'event': 
						if(!empty($event) && is_array($rule) && isset($rule[$event]))
							$passed++;
						break;					
						
					case 'groups':
						if(null !== (@$group_buckets = $rule['groups'][$ticket->team_id]) // group is set
							&& (empty($group_buckets) || in_array($ticket->category_id,$group_buckets)))
								$passed++;
						break;
						
					case 'next_worker_id': 
						if(intval($value)==intval($ticket->next_worker_id))
							$passed++;
						break;					

					case 'mask':
						$regexp_mask = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_mask, $ticket->mask)) {
							$passed++;
						}
						break;
						
					case 'from':
						$regexp_from = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_from, $ticket_from->email)) {
							$passed++;
						}
						break;
						
					case 'subject':
						$regexp_subject = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_subject, $ticket->subject)) {
							$passed++;
						}
						break;
						
					case 'body':
						if(null == ($message_body = $message_last->getContent()))
							break;
							
						// Line-by-line body scanning (sed-like)
						$lines = split("[\r\n]", $message_body);
						if(is_array($lines))
						foreach($lines as $line) {
							if(@preg_match($value, $line)) {
								$passed++;
								break;
							}
						}
						break;
						
					case 'header1':
					case 'header2':
					case 'header3':
					case 'header4':
					case 'header5':
						@$header = strtolower($rule['header']);

						if(empty($header)) {
							$passed++;
							break;
						}
						
						if(empty($value)) { // we're checking for null/blanks
							if(!isset($message_headers[$header]) || empty($message_headers[$header])) {
								$passed++;
							}
							
						} elseif(isset($message_headers[$header]) && !empty($message_headers[$header])) {
							$regexp_header = DevblocksPlatform::strToRegExp($value);
							
							// Flatten CRLF
							if(@preg_match($regexp_header, str_replace(array("\r","\n"),' ',$message_headers[$header]))) {
								$passed++;
							}
						}
						
						break;
						
					default: // ignore invalids
						// Custom Fields
						if(0==strcasecmp('cf_',substr($rule_key,0,3))) {
							$field_id = substr($rule_key,3);

							// Make sure it exists
							if(null == (@$field = $custom_fields[$field_id]))
								continue;

							// Lazy values loader
							$field_values = array();
							switch($field->source_extension) {
								case ChCustomFieldSource_Address::ID:
									if(null == $address_field_values)
										$address_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Address::ID, $ticket_from->id));
									$field_values =& $address_field_values;
									break;
								case ChCustomFieldSource_Org::ID:
									if(null == $org_field_values)
										$org_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $ticket_from->contact_org_id));
									$field_values =& $org_field_values;
									break;
								case ChCustomFieldSource_Ticket::ID:
									if(null == $ticket_field_values)
										$ticket_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $ticket->id));
									$field_values =& $ticket_field_values;
									break;
							}
							
							// Type sensitive value comparisons
							// [TODO] Operators
							switch($field->type) {
								case 'S': // string
								case 'T': // clob
								case 'U': // URL
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : '';
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper == "=" && @preg_match(DevblocksPlatform::strToRegExp($value), $field_val))
										$passed++;
									elseif($oper == "!=" && @!preg_match(DevblocksPlatform::strToRegExp($value), $field_val))
										$passed++;
									break;
								case 'N': // number
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper=="=" && intval($field_val)==intval($value))
										$passed++;
									elseif($oper=="!=" && intval($field_val)!=intval($value))
										$passed++;
									elseif($oper==">" && $intval($field_val) > intval($value))
										$passed++;
									elseif($oper=="<" && $intval($field_val) < intval($value))
										$passed++;
									break;
								case 'E': // date
									$field_val = isset($field_values[$field_id]) ? intval($field_values[$field_id]) : 0;
									$from = isset($rule['from']) ? $rule['from'] : "0";
									$to = isset($rule['to']) ? $rule['to'] : "now";
									
									if(intval(@strtotime($from)) <= $field_val && intval(@strtotime($to)) >= $field_val) {
										$passed++;
									}
									break;
								case 'C': // checkbox
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									if(intval($value)==intval($field_val))
										$passed++;
									break;
								case 'D': // dropdown
								case 'X': // multi-checkbox
								case 'M': // multi-picklist
								case 'W': // worker
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : array();
									if(!is_array($value)) $value = array($value);
										
									if(is_array($field_val)) { // if multiple things set
										foreach($field_val as $v) { // loop through possible
											if(isset($value[$v])) { // is any possible set?
												$passed++;
												break;
											}
										}
										
									} else { // single
										if(isset($value[$field_val])) { // is our set field in possibles?
											$passed++;
											break;
										}
										
									}
									break;
							}
						}
						break;
				}
			}
			
			// If our rule matched every criteria, stop and return the filter
			if($passed == count($filter->criteria)) {
				DAO_WatcherMailFilter::increment($filter->id); // ++ the times we've matched
				$matches[$filter->id] = $filter;
			}
		}
		
		if(!empty($matches))
			return $matches;
		
		// No matches
		return false;
	}
};
