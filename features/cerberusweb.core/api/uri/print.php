<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
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
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
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

				// Comment parent addresses
				$comment_addresses = array();
				foreach($comments as $comment) { /* @var $comment Model_Comment */
					$address_id = intval($comment->address_id);
					if(!isset($comment_addresses[$address_id])) {
						$address = DAO_Address::get($address_id);
						$comment_addresses[$address_id] = $address;
					}
				}
				$tpl->assign('comment_addresses', $comment_addresses);				
				
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
				if(!isset($active_worker_memberships[$ticket->team_id])) {
					echo "<H1>" . $translate->_('common.access_denied') . "</H1>";
					return;
				}
				
				// Owners
				$context_workers = CerberusContexts::getWorkers(CerberusContexts::CONTEXT_TICKET, $ticket->id);
				$tpl->assign('context_workers', $context_workers);

				$tpl->assign('ticket', $ticket);
				
				$tpl->display('devblocks:cerberusweb.core::print/ticket.tpl');
				break;
				
			case 'message':
				@$id = array_shift($stack);
				@$message = DAO_Message::get($id);
				@$ticket = DAO_Ticket::get($message->ticket_id);
				
				// Make sure we're allowed to view this ticket or message
				if(!isset($active_worker_memberships[$ticket->team_id])) {
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
				
				// Owners
				$context_workers = CerberusContexts::getWorkers(CerberusContexts::CONTEXT_TICKET, $ticket->id);
				$tpl->assign('context_workers', $context_workers);
				
				$tpl->assign('message', $message);
				$tpl->assign('ticket', $ticket);
				
				$tpl->display('devblocks:cerberusweb.core::print/message.tpl');
				break;
		}
	}
};
