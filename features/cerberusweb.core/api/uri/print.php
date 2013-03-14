<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class ChPrintController extends DevblocksControllerExtension {
	const ID = 'core.controller.print';
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$worker = CerberusApplication::getActiveWorker();
		if(empty($worker)) return;
		
		$stack = $request->path;
		array_shift($stack); // print
		@$object = strtolower(array_shift($stack)); // ticket|message|etc
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$settings = DevblocksPlatform::getPluginSettingsService();
		$tpl->assign('settings', $settings);
		
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Security
		$active_worker = CerberusApplication::getActiveWorker();
		$active_worker_memberships = $active_worker->getMemberships();
		
		// [TODO] Make this pluggable
		// Subcontroller
		switch($object) {
			case 'ticket':
				@$id = array_shift($stack);
				@$ticket = is_numeric($id) ? DAO_Ticket::get($id) : DAO_Ticket::getTicketByMask($id);

				$convo_timeline = array();
				$messages = $ticket->getMessages();
				foreach($messages as $message_id => $message) { /* @var $message Model_Message */
					$key = $message->created_date . '_m' . $message_id;
					// build a chrono index of messages
					$convo_timeline[$key] = array('m',$message_id);
				}
				
				$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $ticket->id);
				arsort($comments);
				$tpl->assign('comments', $comments);
				
				// build a chrono index of comments
				foreach($comments as $comment_id => $comment) { /* @var $comment Model_Comment */
					$key = $comment->created . '_c' . $comment_id;
					$convo_timeline[$key] = array('c',$comment_id);
				}

				ksort($convo_timeline);
				
				$tpl->assign('convo_timeline', $convo_timeline);

				// Message Notes
				$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $ticket->id);
				$message_notes = array();
				// Index notes by message id
				if(is_array($notes))
				foreach($notes as $note) {
					if(!isset($message_notes[$note->context_id]))
						$message_notes[$note->context_id] = array();
					$message_notes[$note->context_id][$note->id] = $note;
				}
				$tpl->assign('message_notes', $message_notes);
				
				
				// Make sure we're allowed to view this ticket or message
				if(!isset($active_worker_memberships[$ticket->group_id])) {
					echo "<H1>" . $translate->_('common.access_denied') . "</H1>";
					return;
				}
				
				// Watchers
				$context_watchers = CerberusContexts::getWatchers(CerberusContexts::CONTEXT_TICKET, $ticket->id);
				$tpl->assign('context_watchers', $context_watchers);

				$tpl->assign('ticket', $ticket);
				
				$tpl->display('devblocks:cerberusweb.core::print/ticket.tpl');
				break;
				
			case 'message':
				@$id = array_shift($stack);
				@$message = DAO_Message::get($id);
				@$ticket = DAO_Ticket::get($message->ticket_id);
				
				// Make sure we're allowed to view this ticket or message
				if(!isset($active_worker_memberships[$ticket->group_id])) {
					echo "<H1>" . $translate->_('common.access_denied') . "</H1>";
					return;
				}
				
				// Message Notes
				$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $ticket->id);
				$message_notes = array();
				// Index notes by message id
				if(is_array($notes))
				foreach($notes as $note) {
					if(!isset($message_notes[$note->context_id]))
						$message_notes[$note->context_id] = array();
					$message_notes[$note->context_id][$note->id] = $note;
				}
				$tpl->assign('message_notes', $message_notes);
				
				// Watchers
				$context_watchers = CerberusContexts::getWatchers(CerberusContexts::CONTEXT_TICKET, $ticket->id);
				$tpl->assign('context_watchers', $context_watchers);
				
				$tpl->assign('message', $message);
				$tpl->assign('ticket', $ticket);
				
				$tpl->display('devblocks:cerberusweb.core::print/message.tpl');
				break;
		}
	}
};
