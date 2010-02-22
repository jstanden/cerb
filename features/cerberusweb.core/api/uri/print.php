<?php
class ChPrintController extends DevblocksControllerExtension {
	const ID = 'core.controller.print';
	
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
	
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
		$tpl->assign('path', $this->_TPL_PATH);
		
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
				@$ticket = is_numeric($id) ? DAO_Ticket::getTicket($id) : DAO_Ticket::getTicketByMask($id);

				$convo_timeline = array();
				$messages = $ticket->getMessages();		
				foreach($messages as $message_id => $message) { /* @var $message Model_Message */
					$key = $message->created_date . '_m' . $message_id;
					// build a chrono index of messages
					$convo_timeline[$key] = array('m',$message_id);
				}				
				@$mail_inline_comments = DAO_WorkerPref::get($active_worker->id,'mail_inline_comments',1);
				
				if($mail_inline_comments) { // if inline comments are enabled
					$comments = DAO_TicketComment::getByTicketId($ticket->id);
					arsort($comments);
					$tpl->assign('comments', $comments);
					
					// build a chrono index of comments
					foreach($comments as $comment_id => $comment) { /* @var $comment Model_TicketComment */
						$key = $comment->created . '_c' . $comment_id;
						$convo_timeline[$key] = array('c',$comment_id);
					}
				}

				ksort($convo_timeline);
				
				$tpl->assign('convo_timeline', $convo_timeline);

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
				
				// Message Notes
				$notes = DAO_MessageNote::getByTicketId($ticket->id);
				$message_notes = array();
				// Index notes by message id
				if(is_array($notes))
				foreach($notes as $note) {
					if(!isset($message_notes[$note->message_id]))
						$message_notes[$note->message_id] = array();
					$message_notes[$note->message_id][$note->id] = $note;
				}
				$tpl->assign('message_notes', $message_notes);
				
				
				// Make sure we're allowed to view this ticket or message
				if(!isset($active_worker_memberships[$ticket->team_id])) {
					echo "<H1>" . $translate->_('common.access_denied') . "</H1>";
					return;
				}

				$tpl->assign('ticket', $ticket);
				
				$tpl->display('file:' . $this->_TPL_PATH . 'print/ticket.tpl');
				break;
				
			case 'message':
				@$id = array_shift($stack);
				@$message = DAO_Ticket::getMessage($id);
				@$ticket = DAO_Ticket::getTicket($message->ticket_id);
				
				// Make sure we're allowed to view this ticket or message
				if(!isset($active_worker_memberships[$ticket->team_id])) {
					echo "<H1>" . $translate->_('common.access_denied') . "</H1>";
					return;
				}
				
				// Message Notes
				$notes = DAO_MessageNote::getByTicketId($ticket->id);
				$message_notes = array();
				// Index notes by message id
				if(is_array($notes))
				foreach($notes as $note) {
					if(!isset($message_notes[$note->message_id]))
						$message_notes[$note->message_id] = array();
					$message_notes[$note->message_id][$note->id] = $note;
				}
				$tpl->assign('message_notes', $message_notes);				
				
				$tpl->assign('message', $message);
				$tpl->assign('ticket', $ticket);
				
				$tpl->display('file:' . $this->_TPL_PATH . 'print/message.tpl');
				break;
		}
	}
};
