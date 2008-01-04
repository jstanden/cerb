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


class ChMobileTicketsPage  extends CerberusMobilePageExtension  {
    
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();
		
		$response = DevblocksPlatform::getHttpResponse();
		@$section = $response->path[1];
		
		
		//print_r($_REQUEST);exit();
		//@$page = DevblocksPlatform::importGPC($_GET['password']);
		@$page = DevblocksPlatform::importGPC($_REQUEST['page'],'integer');
		if($page==NULL) $page=0;
		
		if(isset($_POST['a2'])) {
			@$section = $_POST['a2'];
		}
		else {
			@$section = $response->path[2];	
		}
		
		//print_r($section);
		//echo $section;
		switch($section) {
			case 'search':
				$title = 'Search';
				$query = $_POST['query'];
				if($query && false===strpos($query,'*'))
					$query = '*' . $query . '*';
				
				if(!is_null($query)) {
					$params = array();
					$type = $_POST['type'];
					switch($type) {
			            case "mask":
			                $params[SearchFields_Ticket::TICKET_MASK] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,DevblocksSearchCriteria::OPER_LIKE,strtoupper($query));
			                break;
			                
			            case "sender":
			                $params[SearchFields_Ticket::TICKET_FIRST_WROTE] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
			                break;
			                
			            case "subject":
			                $params[SearchFields_Ticket::TICKET_SUBJECT] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_SUBJECT,DevblocksSearchCriteria::OPER_LIKE,$query);               
			                break;
			                
			            case "content":
			                $params[SearchFields_Ticket::TICKET_MESSAGE_CONTENT] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_CONTENT,DevblocksSearchCriteria::OPER_LIKE,$query);               
			                break;
					}
				}
				else {
					//show the search form because no search has been submitted
					$tpl->display('file:' . dirname(__FILE__) . '/../../templates/tickets/search.tpl.php');
					return;
				}
			break;
			case 'overview':
			default:
				$params = array(
						new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
						new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',0),
						new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_SPAM_SCORE,'<=','0.9000'),
						new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'in',array_keys($memberships))					
				);
				$title = "Overview";				
			break;
		}
		
		
		$mobileView = new C4_MobileTicketView();//C4_TicketView();
		$mobileView->id = "VIEW_MOBILE";
		$mobileView->name = $title;
		$mobileView->dashboard_id = 0;
		$mobileView->view_columns = array(SearchFields_Ticket::TICKET_LAST_ACTION_CODE);
		$mobileView->params = $params;
		$mobileView->renderLimit = 10;//$overViewDefaults->renderLimit;
		$mobileView->renderPage = $page;
		$mobileView->renderSortBy = SearchFields_Ticket::TICKET_SLA_PRIORITY;
		$mobileView->renderSortAsc = 0;
		
		C4_AbstractViewLoader::setView($mobileView->id,$mobileView);		
		
		$views[] = $mobileView;
	
		$tpl->assign('views', $views);

		$tpl->assign('tickets', $tickets[0]);
		$tpl->assign('next_page', $page+1);
		$tpl->assign('prev_page', $page-1);
		
		//print_r($tickets);exit();
		$tpl->display('file:' . dirname(__FILE__) . '/../../templates/tickets.tpl.php');
	}
	
}


?>