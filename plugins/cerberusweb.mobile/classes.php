<?php

class MobileHandler extends CerberusModuleExtension implements DevblocksHttpRequestHandler {
	
	public function isVisible() {
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	public function handleRequest($request) { /* @var $request DevblocksHttpRequest */
		//print_r($request);echo("<hr>");print_r($_REQUEST);echo("<hr>");
		$stack = $request->path;
		$uri = array_shift($stack);		// $uri should be "mobile"
		$page = array_shift($stack);	// action to take (login, display, etc)
		
		switch ($page) {
			case "reply":
				switch (DevblocksPlatform::importGPC($_REQUEST['page_type'])) {
					case "comment":
						$type = CerberusMessageType::COMMENT;
						break;
					case "display":
						$type = CerberusMessageType::EMAIL;
						break;
					case "forward":
						$type = CerberusMessageType::FORWARD;
						break;
						
					default:
						break;
				} // end switch (page_type)
				CerberusApplication::sendMessage($type);
				DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array($uri,'home')));
				break;

			default:
				break;
		} // end switch (page)
	}
	
	public function writeResponse($response) { /* @var $response DevblocksHttpResponse */
		$stack = $response->path;
		$uri = array_shift($stack);		// $uri should be "mobile"
		$page = array_shift($stack);	// action to take (login, display, etc)
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		switch ($page) {
			default:
			case "home":
				$mytickets = CerberusSearchDAO::searchTickets(
					array(
						new CerberusSearchCriteria(CerberusSearchFields::TICKET_STATUS,'in',array(CerberusTicketStatus::OPEN))
					),
					25,
					0,
					CerberusSearchFields::TICKET_UPDATED_DATE,
					0
				);
				$tpl->assign('mytickets', $mytickets[0]);
				$tpl->display('file:' . dirname(__FILE__) . '/templates/my_tickets.tpl.php');
				break;

			case "login":
				break;
				
			case "comment":
			case "display":
			case "forward":
				$ticket_id = array_shift($stack);
				$message_id = array_shift($stack);
				
				if (empty($ticket_id)) {
					$session = DevblocksPlatform::getSessionService();
					$visit = $session->getVisit();
					print_r($session);
					echo("<hr>");
					print_r($visit);
					break;
				}
				
				if (!is_numeric($ticket_id)) {
					$ticket = CerberusTicketDAO::getTicketByMask($ticket_id);
				} else {
					$ticket = CerberusTicketDAO::getTicket($ticket_id);
				}
				$tpl->assign('ticket', $ticket);
				$tpl->assign('ticket_id', $ticket_id);
				$tpl->assign('message_id', $message_id);
				$tpl->assign('page_type', $page);

				if (0 == strcasecmp($message_id, 'full')) {
					$tpl->display('file:' . dirname(__FILE__) . '/templates/display.tpl.php');
				} else {
					$message = CerberusTicketDAO::getMessage($message_id);
					if (empty($message))
						$message = array_pop($ticket->getMessages());
					$tpl->assign('message', $message);
					$tpl->display('file:' . dirname(__FILE__) . '/templates/display_brief.tpl.php');
				}
				break;
		}
	}
};

?>