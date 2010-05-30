<?php
class ChDisplayPage extends CerberusPageExtension {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		$translate = DevblocksPlatform::getTranslationService();
		$url = DevblocksPlatform::getUrlService();
		
		$stack = $response->path;
		@array_shift($stack); // display
		
		@$id = array_shift($stack);
		
		// [JAS]: Translate Masks
		if(!is_numeric($id)) {
			$id = DAO_Ticket::getTicketIdByMask($id);
		}
		$ticket = DAO_Ticket::get($id);
	
		if(empty($ticket)) {
			echo "<H1>".$translate->_('display.invalid_ticket')."</H1>";
			return;
		}
		
		// Tabs
		
		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.ticket.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);

		@$tab_selected = array_shift($stack);
		
		if(empty($tab_selected))
			$tab_selected = 'conversation';
		
		switch($tab_selected) {
			case 'conversation':
				@$mail_always_show_all = DAO_WorkerPref::get($active_worker->id,'mail_always_show_all',0);
				@$tab_option = array_shift($stack);
				
				if($mail_always_show_all || 0==strcasecmp("read_all",$tab_option)) {
					$tpl->assign('expand_all', true);
				}
				break;
		}
		
		$tpl->assign('tab_selected', $tab_selected);
		
		// Permissions 
		
		$active_worker_memberships = $active_worker->getMemberships();
		
		// Check group membership ACL
		if(!isset($active_worker_memberships[$ticket->team_id])) {
			echo "<H1>".$translate->_('common.access_denied')."</H1>";
			return;
		}
		
		$tpl->assign('ticket', $ticket);

		// TicketToolbarItem Extensions
		$ticketToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.ticket.toolbaritem', true);
		if(!empty($ticketToolbarItems))
			$tpl->assign('ticket_toolbaritems', $ticketToolbarItems);
		
		$quick_search_type = $visit->get('quick_search_type');
		$tpl->assign('quick_search_type', $quick_search_type);
				
		// Comments [TODO] Eventually this can be cached on ticket.num_comments
		$comments_total = DAO_TicketComment::getCountByTicketId($id);
		$tpl->assign('comments_total', $comments_total);
		
		// Tasks Total [TODO] Eventually this can be ticket.num_tasks
		$tasks_total = DAO_Task::getCountBySourceObjectId('cerberusweb.tasks.ticket',$id);
		$tpl->assign('tasks_total', $tasks_total);
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket->id);
		$tpl->assign('requesters', $requesters);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		// Log Activity
		DAO_Worker::logActivity(
			new Model_Activity('activity.display_ticket',array(
				sprintf("<a href='%s' title='[%s] %s'>#%s</a>",
		    		$url->write("c=display&id=".$ticket->mask),
		    		htmlspecialchars(@$teams[$ticket->team_id]->name, ENT_QUOTES, LANG_CHARSET_CODE),
		    		htmlspecialchars($ticket->subject, ENT_QUOTES, LANG_CHARSET_CODE),
		    		$ticket->mask
		    	)
			))
		);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_TicketTab) {
			$inst->showTab();
		}
	}
	
	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_TicketTab) {
			$inst->saveTab();
		}
	}
	
	/*
	 * [TODO] Proxy any func requests to be handled by the tab directly, 
	 * instead of forcing tabs to implement controllers.  This should check 
	 * for the *Action() functions just as a handleRequest would
	 */
	/*
	function handleTabActionAction() {
	}
	*/

	function getMessageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // message id
		@$hide = DevblocksPlatform::importGPC($_REQUEST['hide'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$message = DAO_Message::get($id);
		$tpl->assign('message', $message);
		$tpl->assign('message_id', $message->id);
		
		// Sender info
		$message_senders = array();
		$message_sender_orgs = array();
		
		if(null != ($sender_addy = DAO_Address::get($message->address_id))) {
			$message_senders[$sender_addy->id] = $sender_addy;
			
			if(null != $sender_org = DAO_ContactOrg::get($sender_addy->contact_org_id)) {
				$message_sender_orgs[$sender_org->id] = $sender_org;
			}
		}

		$tpl->assign('message_senders', $message_senders);
		$tpl->assign('message_sender_orgs', $message_sender_orgs);
		
		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Ticket
		$ticket = DAO_Ticket::get($message->ticket_id);
		$tpl->assign('ticket', $ticket);
		$tpl->assign('requesters', $ticket->getRequesters());
		
		if(empty($hide)) {
			$notes = DAO_MessageNote::getByTicketId($message->ticket_id);
			$message_notes = array();
			// Index notes by message id
			if(is_array($notes))
			foreach($notes as $note) {
				if(!isset($message_notes[$note->message_id]))
					$message_notes[$note->message_id] = array();
				$message_notes[$note->message_id][$note->id] = $note;
			}
			$tpl->assign('message_notes', $message_notes);
		}

		// Message toolbar items
		$messageToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.message.toolbaritem', true);
		if(!empty($messageToolbarItems))
			$tpl->assign('message_toolbaritems', $messageToolbarItems);
		
		// [TODO] Workers?
		
		$tpl->assign('expanded', (empty($hide) ? true : false));
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/modules/conversation/message.tpl');
	}

	function updatePropertiesAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // ticket id
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer',0);
		@$spam = DevblocksPlatform::importGPC($_REQUEST['spam'],'integer',0);
		@$deleted = DevblocksPlatform::importGPC($_REQUEST['deleted'],'integer',0);
		@$bucket = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string');
		@$next_worker_id = DevblocksPlatform::importGPC($_REQUEST['next_worker_id'],'integer',0);
		@$unlock_date = DevblocksPlatform::importGPC($_REQUEST['unlock_date'],'integer',0);
		
		@$ticket = DAO_Ticket::get($id);
		
		// Anti-Spam
		if(!empty($spam)) {
		    CerberusBayes::markTicketAsSpam($id);
		    // [mdf] if the spam button was clicked override the default params for deleted/closed
		    $closed=1;
		    $deleted=1;
		}

		$categories = DAO_Bucket::getAll();

		// Properties
		$properties = array(
			DAO_Ticket::IS_CLOSED => intval($closed),
			DAO_Ticket::IS_DELETED => intval($deleted),
		);

		// Undeleting?
		if(empty($spam) && empty($closed) && empty($deleted) 
			&& $ticket->spam_training == CerberusTicketSpamTraining::SPAM && $ticket->is_closed) {
				$score = CerberusBayes::calculateTicketSpamProbability($id);
				$properties[DAO_Ticket::SPAM_SCORE] = $score['probability']; 
				$properties[DAO_Ticket::SPAM_TRAINING] = CerberusTicketSpamTraining::BLANK;
		}
		
		// Team/Category
		if(!empty($bucket)) {
			list($team_id,$bucket_id) = CerberusApplication::translateTeamCategoryCode($bucket);

			if(!empty($team_id)) {
			    $properties[DAO_Ticket::TEAM_ID] = $team_id;
			    $properties[DAO_Ticket::CATEGORY_ID] = $bucket_id;
			}
		}
		
		if($next_worker_id != $ticket->next_worker_id) {
			$properties[DAO_Ticket::NEXT_WORKER_ID] = $next_worker_id;
		}
		
		// Reset the unlock date (next worker "until")
		$properties[DAO_Ticket::UNLOCK_DATE] = $unlock_date;
		
		// Don't double set the closed property (auto-close replies)
		if(isset($properties[DAO_Ticket::IS_CLOSED]) && $properties[DAO_Ticket::IS_CLOSED]==$ticket->is_closed)
			unset($properties[DAO_Ticket::IS_CLOSED]);
		
		DAO_Ticket::update($id, $properties);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$id)));
	}

	function showMergePanelAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$active_worker->hasPriv('core.ticket.view.actions.merge')) {
			return;
		}
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('ticket_id', $ticket_id);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/rpc/merge_panel.tpl');
	}
	
	function saveMergePanelAction() {
		@$src_ticket_id = DevblocksPlatform::importGPC($_REQUEST['src_ticket_id'],'integer',0);
		@$dst_ticket_id = DevblocksPlatform::importGPC($_REQUEST['dst_ticket_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$dst_ticket = null;

		$src_ticket = DAO_Ticket::get($src_ticket_id);
		
		$refresh_id = !empty($src_ticket) ? $src_ticket->mask : $src_ticket_id;
		
		// ACL
		if(!$active_worker->hasPriv('core.ticket.view.actions.merge')) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$refresh_id)));
			exit;
		}
			
		// Check that the tickets exist
		if(is_numeric($dst_ticket_id))
			$dst_ticket = DAO_Ticket::get($dst_ticket_id);
		else
			$dst_ticket = DAO_Ticket::getTicketByMask($dst_ticket_id);
			
		if(empty($src_ticket) || empty($dst_ticket)) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$refresh_id)));
		}
		
		// Check the current worker permissions
		$active_worker_memberships = $active_worker->getMemberships();
			
		if($active_worker->is_superuser 
			|| (isset($active_worker_memberships[$src_ticket->team_id]) && isset($active_worker_memberships[$dst_ticket->team_id]))) {

			if(false != ($oldest_id = DAO_Ticket::merge(array($src_ticket->id, $dst_ticket->id)))) {
				if($oldest_id == $src_ticket->id)
					$refresh_id = $src_ticket->mask;
				elseif($oldest_id = $dst_ticket->id)
					$refresh_id = $dst_ticket->mask;
			}
		}
		
		// Redisplay
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display', $refresh_id)));
	}
	
	/**
	 * Enter description here...
	 * @param string $message_id
	 */
	private function _renderNotes($message_id) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->assign('message_id', $message_id);
		
		$notes = DAO_MessageNote::getByMessageId($message_id);
		$message_notes = array();
		
		// [TODO] DAO-ize? (shared in render())
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($message_notes[$note->message_id]))
				$message_notes[$note->message_id] = array();
			$message_notes[$note->message_id][$note->id] = $note;
		}
		$tpl->assign('message_notes', $message_notes);
				
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$tpl->display('file:' . $this->_TPL_PATH . 'display/modules/conversation/notes.tpl');
	}
	
	function addNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->assign('id',$id);
		
		$message = DAO_Message::get($id);
		$ticket = DAO_Ticket::get($message->ticket_id);
		$tpl->assign('message',$message);
		$tpl->assign('ticket',$ticket);
		
		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
		$active_workers = DAO_Worker::getAllActive();
		$tpl->assign('active_workers', $active_workers);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/rpc/add_note.tpl');
	}
	
	function doAddNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$worker = CerberusApplication::getActiveWorker();
		
		$fields = array(
			DAO_MessageNote::MESSAGE_ID => $id,
			DAO_MessageNote::CREATED => time(),
			DAO_MessageNote::WORKER_ID => $worker->id,
			DAO_MessageNote::CONTENT => $content,
		);
		$note_id = DAO_MessageNote::create($fields);
		
		// [TODO] This really should use an anchor to go back to the message (#r100)
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$ticket_id)));

		if(null != ($ticket = DAO_Ticket::get($ticket_id))) {
			
			// Notifications
			$url_writer = DevblocksPlatform::getUrlService();
			@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
			if(is_array($notify_worker_ids) && !empty($notify_worker_ids))
			foreach($notify_worker_ids as $notify_worker_id) {
				$fields = array(
					DAO_WorkerEvent::CREATED_DATE => time(),
					DAO_WorkerEvent::WORKER_ID => $notify_worker_id,
					DAO_WorkerEvent::URL => $url_writer->write('c=display&id='.$ticket->mask,true),
					DAO_WorkerEvent::TITLE => 'New Ticket Note', // [TODO] Translate
					DAO_WorkerEvent::CONTENT => sprintf("#%s: %s\n%s notes: %s", $ticket->mask, $ticket->subject, $worker->getName(), $content), // [TODO] Translate
					DAO_WorkerEvent::IS_READ => 0,
				);
				DAO_WorkerEvent::create($fields);
			}
		}
		
		$this->_renderNotes($id);
	}
	
	function deleteNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$note = DAO_MessageNote::get($id);
		$message_id = $note->message_id;
		DAO_MessageNote::delete($id);
		
		$this->_renderNotes($message_id);
	}
	
	function replyAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['forward'],'integer',0);

		$settings = DevblocksPlatform::getPluginSettingsService();
		$active_worker = CerberusApplication::getActiveWorker();  /* @var $active_worker Model_Worker */
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->assign('id',$id);
		$tpl->assign('is_forward',$is_forward);
		
		$message = DAO_Message::get($id);
		$tpl->assign('message',$message);
		
		$ticket = DAO_Ticket::get($message->ticket_id);
		$tpl->assign('ticket',$ticket);

		// Are we continuing a draft?
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer',0);
		if(!empty($draft_id)) {
			// Drafts
			$drafts = DAO_MailQueue::getWhere(sprintf("%s = %d AND %s = %d AND (%s = %s OR %s = %s) AND %s = %d",
				DAO_MailQueue::TICKET_ID,
				$message->ticket_id,
				DAO_MailQueue::WORKER_ID,
				$active_worker->id,
				DAO_MailQueue::TYPE,
				C4_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_REPLY),
				DAO_MailQueue::TYPE,
				C4_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_FORWARD),
				DAO_MailQueue::ID,
				$draft_id
			));
			
			if(isset($drafts[$draft_id])) {
				$tpl->assign('draft', $drafts[$draft_id]);
			}
		}
		
		// ReplyToolbarItem Extensions
		$replyToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.reply.toolbaritem', true);
		if(!empty($replyToolbarItems))
			$tpl->assign('reply_toolbaritems', $replyToolbarItems);
		
		// Show attachments for forwarded messages
		if($is_forward) {
			$forward_attachments = $message->getAttachments();
			$tpl->assign('forward_attachments', $forward_attachments);
		}
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);

		@$ticket_team = $teams[$ticket->team_id];
		
		if(null != $active_worker) {
			// Signatures
			if(!empty($ticket_team) && !empty($ticket_team->signature)) {
	            $signature = $ticket_team->signature;
			} else {
			    // [TODO] Default signature
		        $signature = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_SIGNATURE,CerberusSettingsDefaults::DEFAULT_SIGNATURE);
			}

			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $token_labels, $token_values);
			$tpl->assign('signature', $tpl_builder->build($signature, $token_values));
			
		    $signature_pos = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_SIGNATURE_POS,CerberusSettingsDefaults::DEFAULT_SIGNATURE_POS);
			$tpl->assign('signature_pos', $signature_pos);
		}
		
		$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));
		
		$kb_topics = DAO_KbCategory::getWhere(sprintf("%s = %d",
			DAO_KbCategory::PARENT_ID,
			0
		));
		$tpl->assign('kb_topics', $kb_topics);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/rpc/reply.tpl');
	}
	
	function sendReplyAction() {
	    @$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
	    @$ticket_mask = DevblocksPlatform::importGPC($_REQUEST['ticket_mask'],'string');
	    @$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer');
	    @$is_forward = DevblocksPlatform::importGPC($_REQUEST['is_forward'],'integer',0);
	    
	    $worker = CerberusApplication::getActiveWorker();
	    
		$properties = array(
		    'draft_id' => $draft_id,
		    'message_id' => DevblocksPlatform::importGPC(@$_REQUEST['id']),
		    'ticket_id' => $ticket_id,
		    'is_forward' => $is_forward,
		    'to' => DevblocksPlatform::importGPC(@$_REQUEST['to']),
		    'cc' => DevblocksPlatform::importGPC(@$_REQUEST['cc']),
		    'bcc' => DevblocksPlatform::importGPC(@$_REQUEST['bcc']),
		    'subject' => DevblocksPlatform::importGPC(@$_REQUEST['subject'],'string'),
		    'content' => DevblocksPlatform::importGPC(@$_REQUEST['content']),
		    'files' => @$_FILES['attachment'],
		    'next_worker_id' => DevblocksPlatform::importGPC(@$_REQUEST['next_worker_id'],'integer',0),
		    'closed' => DevblocksPlatform::importGPC(@$_REQUEST['closed'],'integer',0),
		    'bucket_id' => DevblocksPlatform::importGPC(@$_REQUEST['bucket_id'],'string',''),
		    'ticket_reopen' => DevblocksPlatform::importGPC(@$_REQUEST['ticket_reopen'],'string',''),
		    'unlock_date' => DevblocksPlatform::importGPC(@$_REQUEST['unlock_date'],'string',''),
		    'agent_id' => @$worker->id,
		    'forward_files' => DevblocksPlatform::importGPC(@$_REQUEST['forward_files'],'array',array()),
		);
		
		if(CerberusMail::sendTicketMessage($properties)) {
			if(!empty($draft_id))
				DAO_MailQueue::delete($draft_id);
		}

        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_mask)));
	}
	
	function saveDraftReplyAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		@$is_ajax = DevblocksPlatform::importGPC($_REQUEST['is_ajax'],'integer',0);
		 
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0); 
		@$msg_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0); 
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer',0); 
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['is_forward'],'integer',0); 
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer',0); 
		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string',''); 
		@$cc = DevblocksPlatform::importGPC($_REQUEST['cc'],'string',''); 
		@$bcc = DevblocksPlatform::importGPC($_REQUEST['bcc'],'string',''); 
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string',''); 
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string',''); 
		
		// Validate
		if(empty($msg_id) 
			|| empty($ticket_id) 
			|| null == ($ticket = DAO_Ticket::get($ticket_id)))
			return;
		
		// Params
		$params = array();
		
		if(!empty($to))
			$params['to'] = $to;
		if(!empty($cc))
			$params['cc'] = $cc;
		if(!empty($bcc))
			$params['bcc'] = $bcc;
		if(!empty($group_id))
			$params['group_id'] = $group_id;
		if(!empty($msg_id))
			$params['in_reply_message_id'] = $msg_id;
		
		// Hint to
		$hint_to = '';
		if(!empty($to)) {
			$hint_to = $to;
		} else {
			$reqs = $ticket->getRequesters();
			$addys = array();
			if(is_array($reqs))
			foreach($reqs as $addy) {
				$addys[] = $addy->email;
			}
			if(!empty($addys))
				$hint_to = implode(', ', $addys);
			unset($reqs);
			unset($addys);
		}
			
		// Fields
		$fields = array(
			DAO_MailQueue::TYPE => empty($is_forward) ? Model_MailQueue::TYPE_TICKET_REPLY : Model_MailQueue::TYPE_TICKET_FORWARD, 
			DAO_MailQueue::TICKET_ID => $ticket_id,
			DAO_MailQueue::WORKER_ID => $active_worker->id,
			DAO_MailQueue::UPDATED => time(),
			DAO_MailQueue::HINT_TO => $hint_to,
			DAO_MailQueue::SUBJECT => $subject,
			DAO_MailQueue::BODY => $content,
			DAO_MailQueue::PARAMS_JSON => json_encode($params),
			DAO_MailQueue::IS_QUEUED => 0,
			DAO_MailQueue::QUEUE_PRIORITY => 0,
		);
		
		// Make sure the current worker is the draft author
		if(!empty($draft_id)) {
			$draft = DAO_MailQueue::getWhere(sprintf("%s = %d AND %s = %d",
				DAO_MailQueue::ID,
				$draft_id,
				DAO_MailQueue::WORKER_ID,
				$active_worker->id
			));
			
			if(!isset($draft[$draft_id]))
				$draft_id = null;
		}
		
		// Save
		if(empty($draft_id)) {
			$draft_id = DAO_MailQueue::create($fields);
		} else {
			DAO_MailQueue::update($draft_id, $fields);
		}
		
		if($is_ajax) {
			// Template
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('timestamp', time());
			$html = $tpl->fetch('file:' . $this->_TPL_PATH . 'mail/queue/saved.tpl');
			
			// Response
			echo json_encode(array('draft_id'=>$draft_id, 'html'=>$html));
		} else {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask)));
		}
	}
	
	function showConversationAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$expand_all = DevblocksPlatform::importGPC($_REQUEST['expand_all'],'integer','0');

		@$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('expand_all', $expand_all);
		
		$ticket = DAO_Ticket::get($id);
		$tpl->assign('ticket', $ticket);
		$tpl->assign('requesters', $ticket->getRequesters());

		// Drafts
		$drafts = DAO_MailQueue::getWhere(sprintf("%s = %d AND (%s = %s OR %s = %s)",
			DAO_MailQueue::TICKET_ID,
			$id,
			DAO_MailQueue::TYPE,
			C4_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_REPLY),
			DAO_MailQueue::TYPE,
			C4_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_FORWARD)
		));
		
		if(!empty($drafts))
			$tpl->assign('drafts', $drafts);
		
		// Only unqueued drafts
		$pending_drafts = array();
		
		if(!empty($drafts) && is_array($drafts))
		foreach($drafts as $draft_id => $draft) {
			if(!$draft->is_queued)
				$pending_drafts[$draft_id] = $draft;
		}
		
		if(!empty($pending_drafts))
			$tpl->assign('pending_drafts', $pending_drafts);
		
		// Messages
		$messages = $ticket->getMessages();
		
		arsort($messages);
				
		$tpl->assign('latest_message_id',key($messages));
		$tpl->assign('messages', $messages);

		// Thread comments and messages on the same level
		$convo_timeline = array();

		// Track senders and their orgs
		$message_senders = array();
		$message_sender_orgs = array();

		// Loop messages
		foreach($messages as $message_id => $message) { /* @var $message Model_Message */
			$key = $message->created_date . '_m' . $message_id;
			// build a chrono index of messages
			$convo_timeline[$key] = array('m',$message_id);
			
			// If we haven't cached this sender address yet
			if(!isset($message_senders[$message->address_id])) {
				if(null != ($sender_addy = DAO_Address::get($message->address_id))) {
					$message_senders[$sender_addy->id] = $sender_addy;	

					// If we haven't cached this sender org yet
					if(!isset($message_sender_orgs[$sender_addy->contact_org_id])) {
						if(null != ($sender_org = DAO_ContactOrg::get($sender_addy->contact_org_id))) {
							$message_sender_orgs[$sender_org->id] = $sender_org;
						}
					}
				}
			}
		}
		
		$tpl->assign('message_senders', $message_senders);
		$tpl->assign('message_sender_orgs', $message_sender_orgs);
		
		@$mail_inline_comments = DAO_WorkerPref::get($active_worker->id,'mail_inline_comments',1);
		
		if($mail_inline_comments) { // if inline comments are enabled
			$comments = DAO_TicketComment::getByTicketId($id);
			arsort($comments);
			$tpl->assign('comments', $comments);
			
			// build a chrono index of comments
			foreach($comments as $comment_id => $comment) { /* @var $comment Model_TicketComment */
				$key = $comment->created . '_c' . $comment_id;
				$convo_timeline[$key] = array('c',$comment_id);
			}
		}
		
		// Thread drafts into conversation
		if(!empty($drafts)) {
			foreach($drafts as $draft_id => $draft) { /* @var $draft Model_MailQueue */
				$key = $draft->updated . '_d' . $draft_id;
				$convo_timeline[$key] = array('d', $draft_id);
			}
		}
		
		// sort the timeline
		if(!$expand_all) {
			krsort($convo_timeline);
		} else {
			ksort($convo_timeline);
		}
		$tpl->assign('convo_timeline', $convo_timeline);
		
		// Message Notes
		$notes = DAO_MessageNote::getByTicketId($id);
		$message_notes = array();
		// Index notes by message id
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($message_notes[$note->message_id]))
				$message_notes[$note->message_id] = array();
			$message_notes[$note->message_id][$note->id] = $note;
		}
		$tpl->assign('message_notes', $message_notes);
		
		// Message toolbar items
		$messageToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.message.toolbaritem', true);
		if(!empty($messageToolbarItems))
			$tpl->assign('message_toolbaritems', $messageToolbarItems);

		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/modules/conversation/index.tpl');
	}
	
	function showCommentsAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$tpl->assign('ticket_id', $ticket_id);
		
		$ticket = DAO_Ticket::get($ticket_id);
		$tpl->assign('ticket', $ticket);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$active_workers = DAO_Worker::getAllActive();
		$tpl->assign('active_workers', $active_workers);
		
		$comments = DAO_TicketComment::getByTicketId($ticket_id);
		arsort($comments);
		$tpl->assign('comments', $comments);

		// Comment parent addresses
		$comment_addresses = array();
		foreach($comments as $comment) { /* @var $comment Model_TicketComment */
			$address_id = intval($comment->address_id);
			if(!isset($comment_addresses[$address_id])) {
				$address = DAO_Address::get($address_id);
				$comment_addresses[$address_id] = $address;
			}
		}
		$tpl->assign('comment_addresses', $comment_addresses);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/modules/comments/index.tpl');
	}
	
	function saveCommentAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		
		// Worker is logged in
		if(null === ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id)));
		
		$worker_email = $active_worker->email;
		
		// Worker address exists
		if(null === ($address = CerberusApplication::hashLookupAddress($active_worker->email,true)))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id)));
		
		// Form was filled in
		if(empty($ticket_id) || empty($comment))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id)));
			
		$fields = array(
			DAO_TicketComment::CREATED => time(),
			DAO_TicketComment::TICKET_ID => $ticket_id,
			DAO_TicketComment::ADDRESS_ID => $address->id,
			DAO_TicketComment::COMMENT => $comment,
		);
		$comment_id = DAO_TicketComment::create($fields);
		
		@$ticket = DAO_Ticket::get($ticket_id);
		
		// Notifications
		$url_writer = DevblocksPlatform::getUrlService();
		@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
		if(is_array($notify_worker_ids) && !empty($notify_worker_ids))
		foreach($notify_worker_ids as $notify_worker_id) {
			$fields = array(
				DAO_WorkerEvent::CREATED_DATE => time(),
				DAO_WorkerEvent::WORKER_ID => $notify_worker_id,
				DAO_WorkerEvent::URL => $url_writer->write('c=display&id='.$ticket->mask,true),
				DAO_WorkerEvent::TITLE => 'New Ticket Comment', // [TODO] Translate
				DAO_WorkerEvent::CONTENT => sprintf("#%s: %s\n%s comments: %s", $ticket->mask, $ticket->subject, $active_worker->getName(), $comment), // [TODO] Translate
				DAO_WorkerEvent::IS_READ => 0,
			);
			DAO_WorkerEvent::create($fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask,'comments')));
	}
	
	function deleteCommentAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$comment_id = DevblocksPlatform::importGPC($_REQUEST['comment_id'],'integer',0);
		
		if(empty($ticket_id) || empty($comment_id)) 
			return;
		
		@$active_worker = CerberusApplication::getActiveWorker();

		$comment = DAO_TicketComment::get($comment_id);
		
		if(!empty($active_worker) && ($active_worker->is_superuser || $comment->getAddress()->email==$active_worker->email))
			DAO_TicketComment::delete($comment_id);
	}
	
	function showPropertiesAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$tpl->assign('ticket_id', $ticket_id);
		
		$ticket = DAO_Ticket::get($ticket_id);
		$tpl->assign('ticket', $ticket);

		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
		$tpl->assign('requesters', $requesters);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Groups (for custom fields)
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		// Custom fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$tpl->assign('ticket_fields', $fields);
		
		$field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $ticket_id);
		
		if(isset($field_values[$ticket->id]))
			$tpl->assign('ticket_field_values', $field_values[$ticket->id]);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/modules/properties/index.tpl');
	}
	
	// Post
	function savePropertiesAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer',0);
		@$remove = DevblocksPlatform::importGPC($_POST['remove'],'array',array());
		@$next_worker_id = DevblocksPlatform::importGPC($_POST['next_worker_id'],'integer',0);
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		@$unlock_date = DevblocksPlatform::importGPC($_POST['unlock_date'],'string','');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','');
		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'closed',0);
		
		@$ticket = DAO_Ticket::get($ticket_id);
		
		if(empty($ticket_id) || empty($ticket))
			return;
		
		$fields = array();
		
		// Properties

		if(empty($next_worker_id))
			$unlock_date = "";
		
		// Status
		if(isset($closed)) {
			switch($closed) {
				case 0: // open
					if(array(0,0,0)!=array($ticket->is_waiting,$ticket->is_closed,$ticket->is_deleted)) {
						$fields[DAO_Ticket::IS_WAITING] = 0;
						$fields[DAO_Ticket::IS_CLOSED] = 0;
						$fields[DAO_Ticket::IS_DELETED] = 0;
						$fields[DAO_Ticket::DUE_DATE] = 0;
					}
					break;
				case 1: // closed
					if(array(0,1,0)!=array($ticket->is_waiting,$ticket->is_closed,$ticket->is_deleted)) {
						$fields[DAO_Ticket::IS_WAITING] = 0;
						$fields[DAO_Ticket::IS_CLOSED] = 1;
						$fields[DAO_Ticket::IS_DELETED] = 0;
					}
					
					if(isset($ticket_reopen)) {
						@$time = intval(strtotime($ticket_reopen));
						$fields[DAO_Ticket::DUE_DATE] = $time;
					}
					break;
				case 2: // waiting
					if(array(1,0,0)!=array($ticket->is_waiting,$ticket->is_closed,$ticket->is_deleted)) {
						$fields[DAO_Ticket::IS_WAITING] = 1;
						$fields[DAO_Ticket::IS_CLOSED] = 0;
						$fields[DAO_Ticket::IS_DELETED] = 0;
					}
					
					if(isset($ticket_reopen)) {
						@$time = intval(strtotime($ticket_reopen));
						$fields[DAO_Ticket::DUE_DATE] = $time;
					}
					break;
				case 3: // deleted
					if(array(0,1,1)!=array($ticket->is_waiting,$ticket->is_closed,$ticket->is_deleted)) {
						$fields[DAO_Ticket::IS_WAITING] = 0;
						$fields[DAO_Ticket::IS_CLOSED] = 1;
						$fields[DAO_Ticket::IS_DELETED] = 1;
					}
					$fields[DAO_Ticket::DUE_DATE] = 0;
					break;
			}
		}
			
		if(isset($next_worker_id))
			$fields[DAO_Ticket::NEXT_WORKER_ID] = $next_worker_id;
			
		if(isset($unlock_date)) {
			@$time = intval(strtotime($unlock_date));
			$fields[DAO_Ticket::UNLOCK_DATE] = $time;
		}

		if(!empty($subject))
			$fields[DAO_Ticket::SUBJECT] = $subject;

		if(!empty($fields)) {
			DAO_Ticket::update($ticket_id, $fields);
		}

		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Ticket::ID, $ticket_id, $field_ids);
		
		// Requesters
		@$req_list = DevblocksPlatform::importGPC($_POST['add'],'string','');
		if(!empty($req_list)) {
			$req_list = DevblocksPlatform::parseCrlfString($req_list);
			$req_list = array_unique($req_list);
			
			// [TODO] This is redundant with the Requester Peek on Reply
			if(is_array($req_list) && !empty($req_list)) {
				foreach($req_list as $req) {
					if(empty($req))
						continue;
						
					$rfc_addys = imap_rfc822_parse_adrlist($req, 'localhost');
					
					foreach($rfc_addys as $rfc_addy) {
						$addy = $rfc_addy->mailbox . '@' . $rfc_addy->host;
						DAO_Ticket::createRequester($addy, $ticket_id);
					}
				}
			}
		}
		
		if(!empty($remove) && is_array($remove)) {
			foreach($remove as $address_id) {
				$addy = DAO_Address::get($address_id);
				DAO_Ticket::deleteRequester($ticket_id, $address_id);
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask)));
	}
	
	function doSplitMessageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		if(null == ($orig_message = DAO_Message::get($id)))
			return;
		
		if(null == ($orig_headers = $orig_message->getHeaders()))
			return;
			
		if(null == ($orig_ticket = DAO_Ticket::get($orig_message->ticket_id)))
			return;

		if(null == ($messages = DAO_Message::getMessagesByTicket($orig_message->ticket_id)))
			return;
			
		// Create a new ticket
		$new_ticket_mask = CerberusApplication::generateTicketMask();
		
		$new_ticket_id = DAO_Ticket::createTicket(array(
			DAO_Ticket::CREATED_DATE => $orig_message->created_date,
			DAO_Ticket::UPDATED_DATE => $orig_message->created_date,
			DAO_Ticket::CATEGORY_ID => $orig_ticket->category_id,
			DAO_Ticket::FIRST_MESSAGE_ID => $orig_message->id,
			DAO_Ticket::LAST_MESSAGE_ID => $orig_message->id,
			DAO_Ticket::FIRST_WROTE_ID => $orig_message->address_id,
			DAO_Ticket::LAST_WROTE_ID => $orig_message->address_id,
			DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_OPENED,
			DAO_Ticket::IS_CLOSED => CerberusTicketStatus::OPEN,
			DAO_Ticket::IS_DELETED => 0,
			DAO_Ticket::MASK => $new_ticket_mask,
			DAO_Ticket::SUBJECT => (isset($orig_headers['subject']) ? $orig_headers['subject'] : $orig_ticket->subject),
			DAO_Ticket::TEAM_ID => $orig_ticket->team_id,
		));

		// [TODO] SLA?
		
		// Copy all the original tickets requesters
		$orig_requesters = DAO_Ticket::getRequestersByTicket($orig_ticket->id);
		foreach($orig_requesters as $orig_req_addy) {
			DAO_Ticket::createRequester($orig_req_addy->email, $new_ticket_id);
		}
		
		// Pull the message off the ticket (reparent)
		unset($messages[$orig_message->id]);
		
		DAO_Message::update($orig_message->id,array(
			DAO_Message::TICKET_ID => $new_ticket_id
		));
		
		// Reindex the original ticket (last wrote, etc.)
		$last_message = end($messages); /* @var Model_Message $last_message */
		
		DAO_Ticket::update($orig_ticket->id, array(
			DAO_Ticket::LAST_MESSAGE_ID => $last_message->id,
			DAO_Ticket::LAST_WROTE_ID => $last_message->address_id
		));
		
		// Remove requester if they don't still have messages on the original ticket
		reset($messages);
		$found = false;
		
		if(is_array($messages))
		foreach($messages as $msgid => $msg) {
			if($msg->address_id == $orig_message->address_id) {
				$found = true;	
				break;
			}
		}
		
		if(!$found)
			DAO_Ticket::deleteRequester($orig_ticket->id,$orig_message->address_id);		
			
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$new_ticket_mask)));
	}
	
	function doTicketHistoryScopeAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','');
		
		$visit = CerberusApplication::getVisit();
		$visit->set('display.history.scope', $scope);

		$ticket = DAO_Ticket::get($ticket_id);

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask,'history')));
	}
	
	function showContactHistoryAction() {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$translate = DevblocksPlatform::getTranslationService();
		
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		// Ticket
		$ticket = DAO_Ticket::get($ticket_id);
		$tpl->assign('ticket', $ticket);
		
		$requesters = $ticket->getRequesters();
		
		// Addy
		$contact = DAO_Address::get($ticket->first_wrote_address_id);
		$tpl->assign('contact', $contact);

		// Scope
		$scope = $visit->get('display.history.scope', '');
		
		// [TODO] Sanitize scope preference
		
		// Defaults
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Ticket';
		$defaults->id = 'contact_history';
		$defaults->name = $translate->_('addy_book.history.view.title');
		$defaults->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_CREATED_DATE,
			SearchFields_Ticket::TICKET_TEAM_ID,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
		);
		$defaults->params = array(
		);
		$defaults->renderLimit = 10;
		$defaults->renderSortBy = SearchFields_Ticket::TICKET_CREATED_DATE;
		$defaults->renderSortAsc = false;
		
		// View
		$view = C4_AbstractViewLoader::getView('contact_history', $defaults);
		
		// Sanitize scope options
		if('org'==$scope) {
			if(empty($contact->contact_org_id))
				$scope = '';
				
			if(null == ($contact_org = DAO_ContactOrg::get($contact->contact_org_id)))
				$scope = '';
		}
		if('domain'==$scope) {
			$email_parts = explode('@', $contact->email);
			if(!is_array($email_parts) || 2 != count($email_parts))
				$scope = '';
		}

		switch($scope) {
			case 'org':
				$view->params = array(
					SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID,'=',$contact->contact_org_id),
					SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				);
				$view->name = ucwords($translate->_('contact_org.name')) . ": " . $contact_org->name;
				break;
				
			case 'domain':
				$view->params = array(
					SearchFields_Ticket::REQUESTER_ADDRESS => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ADDRESS,'like','*@'.$email_parts[1]),
					SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				);
				$view->name = ucwords($translate->_('common.email')) . ": *@" . $email_parts[1];
				break;
				
			default:
			case 'email':
				$scope = 'email';
				$view->params = array(
					SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',array_keys($requesters)),
					SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				);
				$view->name = ucwords($translate->_('common.email')) . ": " . $contact->email;
				break;
		}

		$tpl->assign('scope', $scope);		

		$view->renderPage = 0;
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id,$view);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/modules/history/index.tpl');
	}

	function showTasksAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$ticket = DAO_Ticket::get($ticket_id);
		$tpl->assign('ticket', $ticket);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Task';
		$defaults->id = 'ticket_tasks';
		$defaults->name = $translate->_('tasks.ticket.tab.view');
		$defaults->view_columns = array(
			SearchFields_Task::SOURCE_EXTENSION,
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::WORKER_ID,
		);
		
		$view = C4_AbstractViewLoader::getView('ticket_tasks', $defaults);
		$view->params = array(
			SearchFields_Task::SOURCE_EXTENSION => new DevblocksSearchCriteria(SearchFields_Task::SOURCE_EXTENSION,'=','cerberusweb.tasks.ticket'),
			SearchFields_Task::SOURCE_ID => new DevblocksSearchCriteria(SearchFields_Task::SOURCE_ID,'=',$ticket_id),
		);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/modules/tasks/index.tpl');
	}

	function showSnippetsAction() {
		@$text = DevblocksPlatform::importGPC($_REQUEST['text'],'string','');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('text_element', $text);
		$tpl->assign('context_id', $id);
		
		// [TODO] Most frequently used (by this worker)
		// [TODO] Most frequently used (by all workers)
		// [TODO] Most recently used (by this worker)
		// [TODO] Favorites
		// [TODO] Content search
		
		$view = C4_AbstractViewLoader::getView('snippets_chooser');
		
		if(null == $view) {
			$view = new View_Snippet();
			$view->id = 'snippets_chooser';
		}
		
		$contexts = DevblocksPlatform::parseCsvString(DevblocksPlatform::importGPC($_REQUEST['contexts'],'string',''));
		// Always plaintext too
		if(false === array_search('cerberusweb.snippet.plaintext', $contexts))
			$contexts[] = 'cerberusweb.contexts.plaintext';
					
		$view->name = 'Favorite Snippets';
		$view->renderTemplate = 'chooser';
		$view->view_columns = array(
			SearchFields_Snippet::TITLE,
			SearchFields_Snippet::LAST_UPDATED,
			SearchFields_Snippet::LAST_UPDATED_BY,
			SearchFields_Snippet::USAGE_HITS,
		);
		$view->renderSortBy = SearchFields_Snippet::USAGE_HITS;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->params = array(
			SearchFields_Snippet::CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::CONTEXT, DevblocksSearchCriteria::OPER_IN, $contexts),
		);
		
		C4_AbstractViewLoader::setView($view->id,$view);
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'mail/snippets/chooser.tpl');
	}
	
	function filterSnippetsChooserAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$term = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;

		if(!empty($term)) {	
			$view->params[SearchFields_Snippet::TITLE] = new DevblocksSearchCriteria(SearchFields_Snippet::TITLE, 'like', '%'.$term.'%');
		} else {
			unset($view->params[SearchFields_Snippet::TITLE]);
		}
		
		$view->renderPage = 0;
		$view->render();
	}
	
	function getSnippetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);

		// [TODO] Make sure the worker is allowed to view this context+ID
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		if(null != ($snippet = DAO_Snippet::get($id))) {
			switch($snippet->context) {
				case 'cerberusweb.contexts.plaintext':
					$token_values = array();
					break;
				case 'cerberusweb.contexts.ticket':
					CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $context_id, $token_labels, $token_values);
					break;
				case 'cerberusweb.contexts.worker':
					CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $token_labels, $token_values);
					break;
			}
			
			$snippet->incrementUse($active_worker->id);
		}
		
		$output = $tpl_builder->build($snippet->content, $token_values);
		
		if(!empty($output))
			echo rtrim($output,"\r\n"),"\n";
	}
	
	function showRequestersPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$div = DevblocksPlatform::importGPC($_REQUEST['div'],'string');
		
		$tpl->assign('ticket_id', $ticket_id);
		$tpl->assign('div', $div);
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
		$tpl->assign('requesters', $requesters);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/rpc/requester_panel.tpl');
	}
	
	function saveRequestersPanelAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer');
		@$msg_id = DevblocksPlatform::importGPC($_POST['msg_id'],'integer');

		// Dels
		@$req_deletes = DevblocksPlatform::importGPC($_POST['req_deletes'],'array',array());
		if(!empty($req_deletes))
		foreach($req_deletes as $del_id) {
			DAO_Ticket::deleteRequester($ticket_id, $del_id);
		}		

		// Adds
		@$req_adds = DevblocksPlatform::importGPC($_POST['req_adds'],'string','');
		$req_list = DevblocksPlatform::parseCrlfString($req_adds);
		$req_addys = array();
		
		if(is_array($req_list) && !empty($req_list)) {
			foreach($req_list as $req) {
				if(empty($req))
					continue;
					
				$rfc_addys = imap_rfc822_parse_adrlist($req, 'localhost');
				
				foreach($rfc_addys as $rfc_addy) {
					$addy = $rfc_addy->mailbox . '@' . $rfc_addy->host;
					DAO_Ticket::createRequester($addy, $ticket_id);
				}
			}
		}
				
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('requesters', $requesters);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/rpc/requester_list.tpl');
		
		exit;
	}
};
