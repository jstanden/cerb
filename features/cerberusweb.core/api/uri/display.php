<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class ChDisplayPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

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
				
		$requesters = DAO_Ticket::getRequestersByTicket($ticket->id);
		$tpl->assign('requesters', $requesters);
		
		// Workers
		$context_workers = CerberusContexts::getWorkers(CerberusContexts::CONTEXT_TICKET, $ticket->id);
		$tpl->assign('context_workers', $context_workers);
		
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
		
		$tpl->display('devblocks:cerberusweb.core::display/index.tpl');
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
			$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $message->ticket_id);
			$message_notes = array();
			// Index notes by message id
			if(is_array($notes))
			foreach($notes as $note) {
				if(!isset($message_notes[$note->context_id]))
					$message_notes[$note->context_id] = array();
				$message_notes[$note->context_id][$note->id] = $note;
			}
			$tpl->assign('message_notes', $message_notes);
		}

		// Message toolbar items
		$messageToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.message.toolbaritem', true);
		if(!empty($messageToolbarItems))
			$tpl->assign('message_toolbaritems', $messageToolbarItems);
		
		// [TODO] Workers?
		
		$tpl->assign('expanded', (empty($hide) ? true : false));
		
		$tpl->display('devblocks:cerberusweb.core::display/modules/conversation/message.tpl');
	}

	function updatePropertiesAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // ticket id
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer',0);
		@$spam = DevblocksPlatform::importGPC($_REQUEST['spam'],'integer',0);
		@$deleted = DevblocksPlatform::importGPC($_REQUEST['deleted'],'integer',0);
		@$do_take = DevblocksPlatform::importGPC($_REQUEST['do_take'],'integer',0);
		@$do_surrender = DevblocksPlatform::importGPC($_REQUEST['do_surrender'],'integer',0);
		@$bucket = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string');
		
		if(null == ($ticket = DAO_Ticket::get($id)))
			return;
		
		// Group security
		if(!$active_worker->isTeamMember($ticket->team_id))
			return;
			
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
		
		// Don't double set the closed property (auto-close replies)
		if(isset($properties[DAO_Ticket::IS_CLOSED]) && $properties[DAO_Ticket::IS_CLOSED]==$ticket->is_closed)
			unset($properties[DAO_Ticket::IS_CLOSED]);
		
		DAO_Ticket::update($id, $properties);
		
		// Context workers
		if($do_take)
			CerberusContexts::addWorkers(CerberusContexts::CONTEXT_TICKET, $id, array($active_worker->id));
		if($do_surrender)
			CerberusContexts::removeWorkers(CerberusContexts::CONTEXT_TICKET, $id, array($active_worker->id));

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask)));
		exit;
	}

	function showMergePanelAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$active_worker->hasPriv('core.ticket.view.actions.merge')) {
			return;
		}
		
		$tpl = DevblocksPlatform::getTemplateService();
				
		$tpl->assign('ticket_id', $ticket_id);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/merge_panel.tpl');
	}
	
	function saveMergePanelAction() {
		@$src_ticket_id = DevblocksPlatform::importGPC($_REQUEST['src_ticket_id'],'integer',0);
		@$dst_ticket_ids = DevblocksPlatform::importGPC($_REQUEST['dst_ticket_id'],'array');
		
		$active_worker = CerberusApplication::getActiveWorker();

		if(null == ($src_ticket = DAO_Ticket::get($src_ticket_id)))
			return;
			
		// Group security
		if(!$active_worker->isTeamMember($src_ticket->team_id))
			return;
		
		$refresh_id = !empty($src_ticket) ? $src_ticket->mask : $src_ticket_id;
		
		// ACL
		if(!$active_worker->hasPriv('core.ticket.view.actions.merge')) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$refresh_id)));
			exit;
		}
		
		// Load and filter by the current worker permissions
		$active_worker_memberships = $active_worker->getMemberships();
		
		$dst_tickets = DAO_Ticket::getTickets($dst_ticket_ids);
		foreach($dst_tickets as $dst_ticket_id => $dst_ticket) {
			if($active_worker->is_superuser 
				|| (isset($active_worker_memberships[$dst_ticket->team_id]))) {
					// Permission
			} else {
				unset($dst_tickets[$dst_ticket_id]);
			}
		}
		
		// Load the merge IDs
		$merge_ids = array_merge(array($src_ticket_id), array_keys($dst_tickets));

		// Abort if we don't have a source and at least one target
		if(count($merge_ids) < 2) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$refresh_id)));
		}
		
		if(false != ($oldest_id = DAO_Ticket::merge($merge_ids))) {
			if($oldest_id == $src_ticket->id)
				$refresh_id = $src_ticket->mask;
			elseif(isset($dst_tickets[$oldest_id]))
				$refresh_id = $dst_tickets[$oldest_id]->mask;
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
				$tpl->assign('message_id', $message_id);
		
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_MESSAGE, $message_id);
		$message_notes = array();
		
		// [TODO] DAO-ize? (shared in render())
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($message_notes[$note->context_id]))
				$message_notes[$note->context_id] = array();
			$message_notes[$note->context_id][$note->id] = $note;
		}
		$tpl->assign('message_notes', $message_notes);
				
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$tpl->display('devblocks:cerberusweb.core::display/modules/conversation/notes.tpl');
	}
	
	// [TODO] Merge w/ the new comments functionality?
	function addNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
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
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/add_note.tpl');
	}
	
	function doAddNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$worker = CerberusApplication::getActiveWorker();
		
		$fields = array(
			DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_MESSAGE,
			DAO_Comment::CONTEXT_ID => $id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::ADDRESS_ID => $worker->getAddress()->id,
			DAO_Comment::COMMENT => $content,
		);
		$note_id = DAO_Comment::create($fields);
		
		if(null != ($ticket = DAO_Ticket::get($ticket_id))) {
			// Notifications
			$url_writer = DevblocksPlatform::getUrlService();
			@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
			if(is_array($notify_worker_ids) && !empty($notify_worker_ids))
			foreach($notify_worker_ids as $notify_worker_id) {
				$fields = array(
					DAO_Notification::CREATED_DATE => time(),
					DAO_Notification::WORKER_ID => $notify_worker_id,
					DAO_Notification::URL => $url_writer->write('c=display&id='.$ticket->mask,true),
					DAO_Notification::MESSAGE => sprintf("%s left a note for you on a ticket.",
						$worker->getName()
					),
					DAO_Notification::IS_READ => 0,
				);
				DAO_Notification::create($fields);
			}
		}
		
		$this->_renderNotes($id);
	}
	
	function replyAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['forward'],'integer',0);

		$settings = DevblocksPlatform::getPluginSettingsService();
		$active_worker = CerberusApplication::getActiveWorker();  /* @var $active_worker Model_Worker */
		
		$tpl = DevblocksPlatform::getTemplateService();
				$tpl->assign('id',$id);
		$tpl->assign('is_forward',$is_forward);
		
		$message = DAO_Message::get($id);
		$tpl->assign('message',$message);
		
		$ticket = DAO_Ticket::get($message->ticket_id);
		$tpl->assign('ticket',$ticket);

		// Workers
		$context_workers = CerberusContexts::getWorkers(CerberusContexts::CONTEXT_TICKET, $ticket->id);
		$tpl->assign('context_workers', $context_workers);
		
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
		
		$groups = DAO_Group::getAll();
		$tpl->assign('teams', $groups);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);

		if(null != $active_worker) {
			// Signatures
			@$ticket_group = $groups[$ticket->team_id]; /* @var $ticket_group Model_Group */
			
			if(!empty($ticket_group)) {
				$signature = $ticket_group->getReplySignature($ticket->category_id, $active_worker);
				$tpl->assign('signature', $signature);
			}

			$signature_pos = DAO_WorkerPref::get($active_worker->id, 'mail_signature_pos', 2);
			$tpl->assign('signature_pos', $signature_pos);
		}
		
		$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));
		
		$kb_topics = DAO_KbCategory::getWhere(sprintf("%s = %d",
			DAO_KbCategory::PARENT_ID,
			0
		));
		$tpl->assign('kb_topics', $kb_topics);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/reply.tpl');
	}
	
	function sendReplyAction() {
	    @$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
	    @$ticket_mask = DevblocksPlatform::importGPC($_REQUEST['ticket_mask'],'string');
	    @$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer');
	    @$is_forward = DevblocksPlatform::importGPC($_REQUEST['is_forward'],'integer',0);
	    @$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
	    
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
		    'closed' => DevblocksPlatform::importGPC(@$_REQUEST['closed'],'integer',0),
		    'bucket_id' => DevblocksPlatform::importGPC(@$_REQUEST['bucket_id'],'string',''),
		    'ticket_reopen' => DevblocksPlatform::importGPC(@$_REQUEST['ticket_reopen'],'string',''),
		    'context_workers' => $worker_ids,
		    'agent_id' => @$worker->id,
		    'forward_files' => DevblocksPlatform::importGPC(@$_REQUEST['forward_files'],'array',array()),
		);
		
		if(CerberusMail::sendTicketMessage($properties)) {
			if(!empty($draft_id))
				DAO_MailQueue::delete($draft_id);
		}

		$ticket_uri = !empty($ticket_mask) ? $ticket_mask : $ticket_id;
		
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_uri)));
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
			$html = $tpl->fetch('devblocks:cerberusweb.core::mail/queue/saved.tpl');
			
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
		
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $id);
		arsort($comments);
		$tpl->assign('comments', $comments);
		
		// build a chrono index of comments
		foreach($comments as $comment_id => $comment) { /* @var $comment Model_Comment */
			$key = $comment->created . '_c' . $comment_id;
			$convo_timeline[$key] = array('c',$comment_id);
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
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_MESSAGE, array_keys($messages));
		$message_notes = array();
		// Index notes by message id
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($message_notes[$note->context_id]))
				$message_notes[$note->context_id] = array();
			$message_notes[$note->context_id][$note->id] = $note;
		}
		$tpl->assign('message_notes', $message_notes);
		
		// Message toolbar items
		$messageToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.message.toolbaritem', true);
		if(!empty($messageToolbarItems))
			$tpl->assign('message_toolbaritems', $messageToolbarItems);

		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('devblocks:cerberusweb.core::display/modules/conversation/index.tpl');
	}
	
	function doDeleteMessageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('core.display.message.actions.delete'))
			return;
		
		if(null == ($message = DAO_Message::get($id)))
			return;
			
		if(null == ($ticket = DAO_Ticket::get($message->ticket_id)))
			return;
			
		DAO_Message::delete($id);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display', $ticket->mask)));
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
				$view->addParams(array(
					SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID,'=',$contact->contact_org_id),
					SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				), true);
				$view->name = ucwords($translate->_('contact_org.name')) . ": " . $contact_org->name;
				break;
				
			case 'domain':
				$view->addParams(array(
					SearchFields_Ticket::REQUESTER_ADDRESS => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ADDRESS,'like','*@'.$email_parts[1]),
					SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				), true);
				$view->name = ucwords($translate->_('common.email')) . ": *@" . $email_parts[1];
				break;
				
			default:
			case 'email':
				$scope = 'email';
				$view->addParams(array(
					SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',array_keys($requesters)),
					SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				), true);
				$view->name = sprintf("History: %d recipient(s)", count($requesters));
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
		
		$tpl->display('devblocks:cerberusweb.core::display/modules/history/index.tpl');
	}

	// Requesters
	
	function showRequestersPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$tpl->assign('ticket_id', $ticket_id);
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
		$tpl->assign('requesters', $requesters);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/requester_panel.tpl');
	}
	
	function saveRequestersPanelAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer');
		@$address_ids = DevblocksPlatform::importGPC($_POST['address_id'],'array',array());
		@$lookup_str = DevblocksPlatform::importGPC($_POST['lookup'],'string','');

		if(empty($ticket_id))
			return;
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
		
		// Delete requesters we've removed
		foreach($requesters as $req_id => $req_addy) {
			if(false === array_search($req_id, $address_ids))
				DAO_Ticket::deleteRequester($ticket_id, $req_id);
		}
		
		// Add chooser requesters
		foreach($address_ids as $id) {
			if(is_numeric($id) && !isset($requesters[$id])) {
				if(null != ($address = DAO_Address::get($id)))
					DAO_Ticket::createRequester($address->email, $ticket_id);
			}
		}
		
		// Perform lookups
		if(!empty($lookup_str)) {
			$lookups = DevblocksPlatform::parseCsvString($lookup_str);
			foreach($lookups as $lookup) {
				// Create if a valid email and we haven't heard of them
				if(null != ($address = DAO_Address::lookupAddress($lookup, true)))
					DAO_Ticket::createRequester($address->email, $ticket_id);
			}
		}
		
		exit;
	}
	
	function requesterAddAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string');
		
		DAO_Ticket::createRequester($email, $ticket_id);
	}
	
	function requesterRemoveAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$address_id = DevblocksPlatform::importGPC($_REQUEST['address_id'],'integer');
		
		DAO_Ticket::deleteRequester($ticket_id, $address_id);
	}
	
	function requestersRefreshAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);

		$tpl = DevblocksPlatform::getTemplateService();
				
		$tpl->assign('ticket_id', $ticket_id);
		$tpl->assign('requesters', $requesters);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/requester_list.tpl');
	}
	
};
