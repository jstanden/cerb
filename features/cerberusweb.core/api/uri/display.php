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
		
		// Tabs
		
		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.ticket.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);

		@$tab_selected = array_shift($stack);
		if(empty($tab_selected)) $tab_selected = 'conversation';
		$tpl->assign('tab_selected', $tab_selected);
		
		switch($tab_selected) {
			case 'conversation':
				@$mail_always_show_all = DAO_WorkerPref::get($active_worker->id,'mail_always_show_all',0);
				@$tab_option = array_shift($stack);
				
				if($mail_always_show_all || 0==strcasecmp("read_all",$tab_option)) {
					$tpl->assign('expand_all', true);
				}
				break;
		}
		
		// [JAS]: Translate Masks
		if(!is_numeric($id)) {
			$id = DAO_Ticket::getTicketIdByMask($id);
		}
		$ticket = DAO_Ticket::getTicket($id);
	
		if(empty($ticket)) {
			echo "<H1>".$translate->_('display.invalid_ticket')."</H1>";
			return;
		}

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
		
		// Next+Prev: Does a series exist?
		if(null != ($series_info = $visit->get('ch_display_series', null))) {
			@$series = $series_info['series'];
			$cur = 1;
			$found = false;
			
			// Is this ID part of the series?  If not, invalidate
			if(is_array($series))
			while($mask = current($series)) {
				// Stop if we find it.
				if($mask==$ticket->mask) {
					$found = true;
					break;
				}
				next($series);
				$cur++;
			}
			
			if(!$found) { // not found
				$visit->set('ch_display_series', null);
				
			} else { // found
				$series_stats = array(
					'title' => $series_info['title'],
					'total' => $series_info['total'],
					'count' => count($series)
				);
				
				$series_stats['cur'] = $cur;
				if(false !== prev($series)) {
					@$series_stats['prev'] = current($series);
					next($series); // skip to current
				} else {
					reset($series);
				}
				next($series); // next
				@$series_stats['next'] = current($series);
				
				$tpl->assign('series_stats', $series_stats);
			}			
		}
		
		$quick_search_type = $visit->get('quick_search_type');
		$tpl->assign('quick_search_type', $quick_search_type);
				
		// Comments [TODO] Eventually this can be cached on ticket.num_comments
		$comments_total = DAO_TicketComment::getCountByTicketId($id);
		$tpl->assign('comments_total', $comments_total);
		
		// Tasks Total [TODO] Eventually this can be ticket.num_tasks
		$tasks_total = DAO_Task::getCountBySourceObjectId('cerberusweb.tasks.ticket',$id);
		$tpl->assign('tasks_total', $tasks_total);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		// Log Activity
		DAO_Worker::logActivity(
			$active_worker->id,
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
	
	function browseAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		array_shift($stack); // display
		array_shift($stack); // browse
		
		@$id = array_shift($stack);
		
		// [JAS]: Mask
		if(!is_numeric($id)) {
			$id = DAO_Ticket::getTicketIdByMask($id);
		}
		$ticket = DAO_Ticket::getTicket($id);
	
		if(empty($ticket)) {
			echo "<H1>".$translate->_('display.invalid_ticket')."</H1>";
			return;
		}
		
		// Display series support (inherited paging from Display)
		@$view_id = array_shift($stack);
		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView($view_id);

			// Restrict to the active worker's groups
			$active_worker = CerberusApplication::getActiveWorker();
			$memberships = $active_worker->getMemberships();
			$view->params['tmp'] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID, 'in', array_keys($memberships)); 
			
			$range = 250; // how far to jump ahead of the current page
			$block_size = 250;
			$page = floor(($view->renderPage * $view->renderLimit)/$block_size);
			$index = array();
			$found = false;
			$full = false;

			do {
				list($series, $null) = DAO_Ticket::search(
					array(
						SearchFields_Ticket::TICKET_MASK,
					),
					$view->params,
					$block_size,
					$page,
					$view->renderSortBy,
					$view->renderSortAsc,
					false
				);
				
				// Index by mask
				foreach($series as $idx => $val) {
					// Find our match before we index anything
					if(!$found && $idx == $id) {
						$found = true;
					} elseif(!$found) {
						// Only keep a max of X things behind our match, reserve the most room ahead
						if(count($index) == 20)
							array_shift($index);
					}
					
					$index[] = $val[SearchFields_Ticket::TICKET_MASK];
					
					// Stop if we fill up our desired rows
					if(count($index)==$range) {
						$full = true;
						break;
					}
				}
				
				$page++;
				
			} while(!empty($series) && !$full);
			
			$series_info = array(
				'title' => $view->name,
				'total' => count($index),
				'series' => $index
			);
			$visit->set('ch_display_series', $series_info);
		}
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask)));
	}

	function getMessageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // message id
		@$hide = DevblocksPlatform::importGPC($_REQUEST['hide'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$message = DAO_Ticket::getMessage($id);
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
		$ticket = DAO_Ticket::getTicket($message->ticket_id);
		$tpl->assign('ticket', $ticket);
		$tpl->assign('requesters', $ticket->getRequesters());
		
		if(empty($hide)) {
			$content = DAO_MessageContent::get($id);
			$tpl->assign('content', $content);
			
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
		
		$tpl->register_modifier('makehrefs', array('CerberusUtils', 'smarty_modifier_makehrefs')); 
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
		
		@$ticket = DAO_Ticket::getTicket($id);
		
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
		
		DAO_Ticket::updateTicket($id, $properties);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$id)));
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

		$tpl->register_modifier('makehrefs', array('CerberusUtils', 'smarty_modifier_makehrefs')); 
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/modules/conversation/notes.tpl');
	}
	
	function addNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->assign('id',$id);
		
		$message = DAO_Ticket::getMessage($id);
		$ticket = DAO_Ticket::getTicket($message->ticket_id);
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

		if(null != ($ticket = DAO_Ticket::getTicket($ticket_id))) {
			
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
	
	function discardAndSurrenderAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$fields = array(
			DAO_Ticket::NEXT_WORKER_ID => 0, // anybody
			DAO_Ticket::UNLOCK_DATE => 0,
		);
		
		DAO_Ticket::updateWhere($fields, sprintf("%s = %d AND %s = %d",
			DAO_Ticket::ID,
			$ticket_id,
			DAO_Ticket::NEXT_WORKER_ID,
			$active_worker->id
		));
	}
	
	function replyAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['forward'],'integer',0);

		$settings = DevblocksPlatform::getPluginSettingsService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->assign('id',$id);
		$tpl->assign('is_forward',$is_forward);
		
		$message = DAO_Ticket::getMessage($id);
		$tpl->assign('message',$message);
		
		$ticket = DAO_Ticket::getTicket($message->ticket_id);
		$tpl->assign('ticket',$ticket);

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
		
		if(null != ($worker = CerberusApplication::getActiveWorker())) { /* @var $worker CerberusWorker */
			/* [JAS]:
			 * If the worker is replying to an unassigned ticket, assign it to them to warn
			 * other workers.  By default the 'next worker' followup propery will revert back 
			 * to 'anybody' when desired.
			 * 
			 * We're intentionally not telling the template about the new owner.
			 */
			if(0 == $ticket->next_worker_id) {
				DAO_Ticket::updateTicket($ticket->id,array(
					DAO_Ticket::NEXT_WORKER_ID => $worker->id
				));
			}

			// Signatures
			if(!empty($ticket_team) && !empty($ticket_team->signature)) {
	            $signature = $ticket_team->signature;
			} else {
			    // [TODO] Default signature
		        $signature = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_SIGNATURE);
			}

			$tpl->assign('signature', str_replace(
			        array('#first_name#','#last_name#','#title#'),
			        array($worker->first_name,$worker->last_name,$worker->title),
			        $signature
			));
			
		    $signature_pos = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_SIGNATURE_POS,0);
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
	    
	    $worker = CerberusApplication::getActiveWorker();
	    
		$properties = array(
		    'message_id' => DevblocksPlatform::importGPC(@$_REQUEST['id']),
		    'ticket_id' => $ticket_id,
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
		
		CerberusMail::sendTicketMessage($properties);

        DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$ticket_id)));
	}
	
	function showConversationAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$expand_all = DevblocksPlatform::importGPC($_REQUEST['expand_all'],'integer','0');

		@$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('expand_all', $expand_all);
		
		$ticket = DAO_Ticket::getTicket($id);
		$tpl->assign('ticket', $ticket);
		$tpl->assign('requesters', $ticket->getRequesters());

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
		foreach($messages as $message_id => $message) { /* @var $message CerberusMessage */
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
		
		$tpl->register_modifier('makehrefs', array('CerberusUtils', 'smarty_modifier_makehrefs')); 
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/modules/conversation/index.tpl');
	}
	
	function showCommentsAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$tpl->assign('ticket_id', $ticket_id);
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
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
		
		$tpl->register_modifier('makehrefs', array('CerberusUtils', 'smarty_modifier_makehrefs'));		
		
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
		
		@$ticket = DAO_Ticket::getTicket($ticket_id);
		
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
		
//		@$worker_id = CerberusApplication::getActiveWorker()->id;
		
		if(empty($ticket_id) || empty($comment_id)) // empty($worker_id) || 
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id)));
		
		@$active_worker = CerberusApplication::getActiveWorker();

		$comment = DAO_TicketComment::get($comment_id);
		
		if(!empty($active_worker) && ($active_worker->is_superuser || $comment->getAddress()->email==$active_worker->email))
			DAO_TicketComment::delete($comment_id);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id,'comments')));
	}
	
	function showPropertiesAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$tpl->assign('ticket_id', $ticket_id);
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
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
		
		@$ticket = DAO_Ticket::getTicket($ticket_id);
		
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
					$fields[DAO_Ticket::IS_WAITING] = 0;
					$fields[DAO_Ticket::IS_CLOSED] = 0;
					$fields[DAO_Ticket::IS_DELETED] = 0;
					$fields[DAO_Ticket::DUE_DATE] = 0;
					break;
				case 1: // closed
					$fields[DAO_Ticket::IS_WAITING] = 0;
					$fields[DAO_Ticket::IS_CLOSED] = 1;
					$fields[DAO_Ticket::IS_DELETED] = 0;
					
					if(isset($ticket_reopen)) {
						@$time = intval(strtotime($ticket_reopen));
						$fields[DAO_Ticket::DUE_DATE] = $time;
					}
					break;
				case 2: // waiting
					$fields[DAO_Ticket::IS_WAITING] = 1;
					$fields[DAO_Ticket::IS_CLOSED] = 0;
					$fields[DAO_Ticket::IS_DELETED] = 0;
					
					if(isset($ticket_reopen)) {
						@$time = intval(strtotime($ticket_reopen));
						$fields[DAO_Ticket::DUE_DATE] = $time;
					}
					break;
				case 3: // deleted
					$fields[DAO_Ticket::IS_WAITING] = 0;
					$fields[DAO_Ticket::IS_CLOSED] = 1;
					$fields[DAO_Ticket::IS_DELETED] = 1;
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
			DAO_Ticket::updateTicket($ticket_id, $fields);
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
						
						if(null != ($req_addy = CerberusApplication::hashLookupAddress($addy, true)))
							DAO_Ticket::createRequester($req_addy->id, $ticket_id);
					}
				}
			}
		}
		
		if(!empty($remove) && is_array($remove)) {
			foreach($remove as $address_id) {
				$addy = DAO_Address::get($address_id);
				DAO_Ticket::deleteRequester($ticket_id, $address_id);
//				echo "Removed <b>" . $addy->email . "</b> as a recipient.<br>";
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask)));
	}
	
	function doSplitMessageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		if(null == ($orig_message = DAO_Ticket::getMessage($id)))
			return;
		
		if(null == ($orig_headers = $orig_message->getHeaders()))
			return;
			
		if(null == ($orig_ticket = DAO_Ticket::getTicket($orig_message->ticket_id)))
			return;

		if(null == ($messages = DAO_Ticket::getMessagesByTicket($orig_message->ticket_id)))
			return;
			
		// Create a new ticket
		$new_ticket_mask = CerberusApplication::generateTicketMask();
		
		$new_ticket_id = DAO_Ticket::createTicket(array(
			DAO_Ticket::CREATED_DATE => $orig_message->created_date,
			DAO_Ticket::UPDATED_DATE => $orig_message->created_date,
			DAO_Ticket::CATEGORY_ID => $orig_ticket->category_id,
			DAO_Ticket::FIRST_MESSAGE_ID => $orig_message->id,
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
		foreach($orig_requesters as $orig_req_id => $orig_req_addy) {
			DAO_Ticket::createRequester($orig_req_id, $new_ticket_id);
		}
		
		// Pull the message off the ticket (reparent)
		unset($messages[$orig_message->id]);
		
		DAO_Message::update($orig_message->id,array(
			DAO_Message::TICKET_ID => $new_ticket_id
		));
		
		// Reindex the original ticket (last wrote, etc.)
		$last_message = end($messages); /* @var CerberusMessage $last_message */
		
		DAO_Ticket::updateTicket($orig_ticket->id, array(
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

		$ticket = DAO_Ticket::getTicket($ticket_id);

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask,'history')));
	}
	
	function showContactHistoryAction() {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$translate = DevblocksPlatform::getTranslationService();
		
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		// Ticket
		$ticket = DAO_Ticket::getTicket($ticket_id);
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
		$defaults->class_name = 'C4_TicketView';
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
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket', $ticket);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'C4_TaskView';
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

	// Ajax
	function showTemplatesPanelAction() {
		@$txt_name = DevblocksPlatform::importGPC($_REQUEST['txt_name'],'string','');
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('reply_id', $reply_id);
		$tpl->assign('txt_name', $txt_name);
		$tpl->assign('type', $type);
		
		$folders = DAO_MailTemplate::getFolders($type);
		$tpl->assign('folders', $folders);

		$where = null;
		if(empty($folder)) {
			$where = sprintf("%s = %d",
				DAO_MailTemplate::TEMPLATE_TYPE,
				$type
			);
		} 
		
		$templates = DAO_MailTemplate::getWhere($where);
		$tpl->assign('templates', $templates);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/rpc/email_templates/templates_panel.tpl');
	}
	
	// Ajax
	function showTemplateEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
//		@$txt_name = DevblocksPlatform::importGPC($_REQUEST['txt_name'],'string','');		
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('reply_id', $reply_id);
//		$tpl->assign('txt_name', $txt_name);
		$tpl->assign('type', $type);
		
		$folders = DAO_MailTemplate::getFolders($type);
		$tpl->assign('folders', $folders);
		
		$template = DAO_MailTemplate::get($id);
		$tpl->assign('template', $template);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/rpc/email_templates/template_edit_panel.tpl');
	}
	
	// Ajax
	function saveReplyTemplateAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
		@$description = DevblocksPlatform::importGPC($_REQUEST['description'],'string','');
		@$folder = DevblocksPlatform::importGPC($_REQUEST['folder'],'string','');
		@$folder_new = DevblocksPlatform::importGPC($_REQUEST['folder_new'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['template'],'string','');
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'integer',0);
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$worker = CerberusApplication::getActiveWorker();
		
		if(empty($delete)) {
			$fields = array(
				DAO_MailTemplate::TITLE => $title,
				DAO_MailTemplate::FOLDER => (!empty($folder)?$folder:$folder_new),
				DAO_MailTemplate::DESCRIPTION => $description,
				DAO_MailTemplate::CONTENT => $content,
				DAO_MailTemplate::TEMPLATE_TYPE => $type,
				DAO_MailTemplate::OWNER_ID => $worker->id,
			);
			
			if(empty($id)) { // new
				$id = DAO_MailTemplate::create($fields);
				
			} else { // edit
				DAO_MailTemplate::update($id, $fields);			
				
			}
			
		} else { // delete
			DAO_MailTemplate::delete($id);
		}
		
	}
	
	// Ajax
	function getTemplateAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');

		$template = DAO_MailTemplate::get($id);
		echo $template->getRenderedContent($reply_id);
	}

	// Ajax
	function getTemplatesAction() {
		@$txt_name = DevblocksPlatform::importGPC($_REQUEST['txt_name'],'string','');
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');
		@$folder = DevblocksPlatform::importGPC($_REQUEST['folder'],'string','');
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'integer',0);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('reply_id', $reply_id);
		$tpl->assign('txt_name', $txt_name);
		
		if(empty($folder)) {
			$where = sprintf("%s = %d",
				DAO_MailTemplate::TEMPLATE_TYPE,
				$type
			);
		} else {
			$where = sprintf("%s = %s AND %s = %d ",
				DAO_MailTemplate::FOLDER,
				$db->qstr($folder),
				DAO_MailTemplate::TEMPLATE_TYPE,
				$type
			);
		} 
		
		$templates = DAO_MailTemplate::getWhere($where);
		$tpl->assign('templates', $templates);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'display/rpc/email_templates/template_results.tpl');
	} 
	
	function showRequestersPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$msg_id = DevblocksPlatform::importGPC($_REQUEST['msg_id'],'integer');
		
		$tpl->assign('ticket_id', $ticket_id);
		$tpl->assign('msg_id', $msg_id);
		
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
					
					if(null != ($req_addy = CerberusApplication::hashLookupAddress($addy, true)))
						DAO_Ticket::createRequester($req_addy->id, $ticket_id);
				}
			}
		}
				
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);

		$list = array();		
		foreach($requesters as $requester) {
			$list[] = '<b>'.$requester->email.'</b>';
		}
		
		echo implode(', ', $list);
		exit;
	}
};
