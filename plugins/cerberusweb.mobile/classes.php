<?php
// [TODO] Convert to a controller (not page)
class MobileController extends DevblocksControllerExtension {
    const ID = 'cerberusweb.controller.mobile';
	
    public function __construct($manifest) {
        parent::__construct($manifest);
        
        $router = DevblocksPlatform::getRoutingService();
        $router->addRoute('mobile', self::ID);
    }
    
	public function handleRequest(DevblocksHttpRequest $request) { /* @var $request DevblocksHttpRequest */
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		// [TODO] Implement a mobile login system
		
		if(empty($visit))
		    die("Not logged in.");
	    
		$stack = $request->path;
		$uri = array_shift($stack);		// $uri should be "mobile"
		$page = array_shift($stack);	// action to take (login, display, etc)
		
		switch ($page) {
			case "reply":
				@$message_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
				@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'content');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string'); // used by forward
				
				$properties = array(
					'message_id' => $message_id,
					'content' => $content,
			    );
			    
				switch (DevblocksPlatform::importGPC($_REQUEST['page_type'])) {
					case "comment":
						$properties['type'] = CerberusMessageType::COMMENT;
						break;
					case "display":
						$properties['type'] = CerberusMessageType::EMAIL;
						break;
					case "forward":
						$properties['type'] = CerberusMessageType::FORWARD;
						$properties['to'] = $to;
						break;
						
					default:
						break;
				}
				
				CerberusMail::sendTicketMessage($properties);
				
				DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array($uri,'home')));
				break;

			default:
				break;
		} // end switch (page)
	}
	
	public function writeResponse(DevblocksHttpResponse $response) { /* @var $response DevblocksHttpResponse */
		$stack = $response->path;
		$uri = array_shift($stack);		// $uri should be "mobile"
		$page = array_shift($stack);	// action to take (login, display, etc)
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		switch ($page) {
			default:
			case "home":
				$mytickets = DAO_Ticket::search(
					array(
						new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN)
					),
					25,
					0,
					SearchFields_Ticket::TICKET_UPDATED_DATE,
					0,
					false
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
					break;
				}
				
				if (!is_numeric($ticket_id)) {
					$ticket = DAO_Ticket::getTicketByMask($ticket_id);
				} else {
					$ticket = DAO_Ticket::getTicket($ticket_id);
				}
				$tpl->assign('ticket', $ticket);
				$tpl->assign('ticket_id', $ticket_id);
				$tpl->assign('message_id', $message_id);
				$tpl->assign('page_type', $page);

				if (0 == strcasecmp($message_id, 'full')) {
					$tpl->display('file:' . dirname(__FILE__) . '/templates/display.tpl.php');
				} else {
					$message = DAO_Ticket::getMessage($message_id);
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