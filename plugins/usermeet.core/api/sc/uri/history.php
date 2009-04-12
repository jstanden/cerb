<?php
class UmScHistoryController extends Extension_UmScController {
	
	function isVisible() {
		$umsession = $this->getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		return !empty($active_user);
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';
		
		$umsession = $this->getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		$stack = $response->path;
		array_shift($stack); // history
		$mask = array_shift($stack);
		
		if(empty($mask)) {
			list($open_tickets) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0)
				),
				-1,
				0,
				SearchFields_Ticket::TICKET_UPDATED_DATE,
				false,
				false
			);
			$tpl->assign('open_tickets', $open_tickets);
			
			list($closed_tickets) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',1)
				),
				-1,
				0,
				SearchFields_Ticket::TICKET_UPDATED_DATE,
				false,
				false
			);
			$tpl->assign('closed_tickets', $closed_tickets);
			$tpl->display("file:${tpl_path}portal/sc/internal/history/index.tpl");
			
		} else {
			// Secure retrieval (address + mask)
			list($tickets) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
				),
				1,
				0,
				null,
				null,
				false
			);
			$ticket = array_shift($tickets);
			
			// Security check (mask compare)
			if(0 == strcasecmp($ticket[SearchFields_Ticket::TICKET_MASK],$mask)) {
				$messages = DAO_Ticket::getMessagesByTicket($ticket[SearchFields_Ticket::TICKET_ID]);
				$messages = array_reverse($messages, true);						
				
				$tpl->assign('ticket', $ticket);
				$tpl->assign('messages', $messages);
				$tpl->display("file:${tpl_path}portal/sc/internal/history/display.tpl");						
			}
		}
				
	}
	
	function saveTicketPropertiesAction() {
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer','0');
		
		$umsession = $this->getSession();
		$active_user = $umsession->getProperty('sc_login', null);

		// Secure retrieval (address + mask)
		list($tickets) = DAO_Ticket::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
			),
			1,
			0,
			null,
			null,
			false
		);
		$ticket = array_shift($tickets);
		$ticket_id = $ticket[SearchFields_Ticket::TICKET_ID];

		$fields = array(
			DAO_Ticket::IS_CLOSED => ($closed) ? 1 : 0
		);
		DAO_Ticket::updateTicket($ticket_id,$fields);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'history',$ticket[SearchFields_Ticket::TICKET_MASK])));		
	}
	
	function doReplyAction() {
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$umsession = $this->getSession();
		$active_user = $umsession->getProperty('sc_login', null);

		// Secure retrieval (address + mask)
		list($tickets) = DAO_Ticket::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
			),
			1,
			0,
			null,
			null,
			false
		);
		$ticket = array_shift($tickets);
		
		$messages = DAO_Ticket::getMessagesByTicket($ticket[SearchFields_Ticket::TICKET_ID]);
		$last_message = array_pop($messages); /* @var $last_message CerberusMessage */
		$last_message_headers = $last_message->getHeaders();
		unset($messages);

		// Helpdesk settings
		$settings = CerberusSettings::getInstance();
		$global_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM,null);
		
		// Ticket group settings
		$group_id = $ticket[SearchFields_Ticket::TICKET_TEAM_ID];
		@$group_from = DAO_GroupSettings::get($group_id, DAO_GroupSettings::SETTING_REPLY_FROM, '');
		
		// Headers
		$to = !empty($group_from) ? $group_from : $global_from;
		@$in_reply_to = $last_message_headers['message-id'];
		@$message_id = CerberusApplication::generateMessageId();
		
		$message = new CerberusParserMessage();
		$message->headers['from'] = $active_user->email;
		$message->headers['to'] = $to;
		$message->headers['date'] = gmdate('r');
		$message->headers['subject'] = 'Re: ' . $ticket[SearchFields_Ticket::TICKET_SUBJECT];
		$message->headers['message-id'] = $message_id;
		$message->headers['in-reply-to'] = $in_reply_to;
		
		$message->body = sprintf(
			"%s",
			$content
		);
   
		CerberusParser::parseMessage($message,array('no_autoreply'=>true));
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'history',$ticket[SearchFields_Ticket::TICKET_MASK])));
	}
	
};