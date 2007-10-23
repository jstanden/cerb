<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
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
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class ChMobilePlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

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
				
				$worker = CerberusApplication::getActiveWorker();
				
				$properties = array(
					'message_id' => $message_id,
					'content' => $content,
					'agent_id' => @$worker->id
			    );
				
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
					$ticket_id = DAO_Ticket::getTicketIdByMask($ticket_id);
				}
				$ticket = DAO_Ticket::getTicket($ticket_id);
				
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