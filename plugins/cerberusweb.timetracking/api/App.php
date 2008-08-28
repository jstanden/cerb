<?php
class ChTimeTrackingPlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

if (class_exists('Extension_AppPreBodyRenderer',true)):
	class ChTimeTrackingPreBodyRenderer extends Extension_AppPreBodyRenderer {
		function render() {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
			$tpl->assign('path', $tpl_path);
			$tpl->cache_lifetime = "0";
			
			$tpl->assign('current_timestamp', time());
			
			$tpl->display('file:' . $tpl_path . 'timetracking/renderers/prebody.tpl.php');
		}
	};
endif;

if (class_exists('Extension_TicketToolbarItem',true)):
	class ChTimeTrackingTicketToolbarTimer extends Extension_TicketToolbarItem {
		function render($ticket) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
			$tpl->assign('path', $tpl_path);
			$tpl->cache_lifetime = "0";
			
			$tpl->assign('ticket', $ticket);
			
			$tpl->display('file:' . $tpl_path . 'timetracking/renderers/ticket_toolbar_timer.tpl.php');
		}
	};
endif;

class ChTimeTrackingTab extends Extension_TicketTab {
	function showTab() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

//		$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket_id', $ticket_id);
		
//		if(null == ($view = C4_AbstractViewLoader::getView('', 'ticket_opps'))) {
//			$view = new C4_CrmOpportunityView();
//			$view->id = 'ticket_opps';
//		}
//
//		if(!empty($address->contact_org_id)) { // org
//			@$org = DAO_ContactOrg::get($address->contact_org_id);
//			
//			$view->name = "Org: " . $org->name;
//			$view->params = array(
//				SearchFields_CrmOpportunity::ORG_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_ID,'=',$org->id) 
//			);
//		}
//		
//		C4_AbstractViewLoader::setView($view->id, $view);
//		
//		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $tpl_path . 'timetracking/ticket_tab/index.tpl.php');
	}
	
	function saveTab() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		
		if(isset($_SESSION['timetracking'])) {
			@$time = intval($_SESSION['timetracking']);
//			echo "Ran for ", (time()-$time) , "secs <BR>";
			unset($_SESSION['timetracking']);
		} else {
			$_SESSION['timetracking'] = time();
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask,'timetracking')));
	}
};

//class ChTimeTrackingEventListener extends DevblocksEventListenerExtension {
//    function __construct($manifest) {
//        parent::__construct($manifest);
//    }
//
//    /**
//     * @param Model_DevblocksEvent $event
//     */
//    function handleEvent(Model_DevblocksEvent $event) {
//        switch($event->id) {
////            case 'cron.maint':
////            	DAO_TicketAuditLog::maint();
////            	break;
//            	
//            case 'ticket.reply.outbound':
//            	@$ticket_id = $event->params['ticket_id'];
//            	@$message_id = $event->params['message_id'];
//            	@$worker_id = $event->params['worker_id'];
//            	
//            	if(null == ($ticket = DAO_Ticket::getTicket($ticket_id)))
//            		return;
//
//            	$requester_list = array();
//            	$ticket_requesters = $ticket->getRequesters();
//            	
//            	if(is_array($ticket_requesters))
//            	foreach($ticket_requesters as $addy) { /* @var $addy Model_Address */
//            		$requester_list[] = $addy->email;
//            	}
//            	
//            	self::logToTimeTracking(sprintf("-- %s --\r\nReplied to %s on ticket: [#%s] %s",
//            		date('r', time()),
//            		implode(', ', $requester_list),
//            		$ticket->mask,
//            		$ticket->subject
//            	));
//            		
//            	break;
//        }
//    }
//    
//    // [TODO] Where does this static best belong?
//    static function logToTimeTracking($log) {
//    	if(!isset($_SESSION['timetracking_worklog']))
//        	$_SESSION['timetracking_worklog'] = array();
//        	
//        $_SESSION['timetracking_worklog'][] = $log;
//    }
//};

class ChTimeTrackingAjaxController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('timetracking','timetracking.controller.ajax');
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
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		if(!$this->isVisible())
			return;
		
	    $path = $request->path;
		$controller = array_shift($path); // timetracking

	    @$action = DevblocksPlatform::strAlphaNumDash(array_shift($path)) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
	            break;
	    }
	}
	
	private function _startTimer() {
		@$time = intval($_SESSION['timetracking_started']);
		
		if(empty($time))
			$_SESSION['timetracking_started'] = time();
	}
	
	private function _stopTimer() {
		@$time = intval($_SESSION['timetracking_started']);
		
		// If a timer was running
		if(!empty($time)) {
			$elapsed = time() - $time;
			unset($_SESSION['timetracking_started']);
			@$_SESSION['timetracking_total'] = intval($_SESSION['timetracking_total']) + $elapsed;
		}

		@$total = $_SESSION['timetracking_total'];
		if(empty($total))
			return false;
		
		return $total;
	}
	
	private function _destroyTimer() {
		unset($_SESSION['timetracking_started']);
		unset($_SESSION['timetracking_total']);
	}
	
	function startTimerAction() {
		@$origin = DevblocksPlatform::importGPC($_REQUEST['origin'],'string',''); // urldecode?
		
		// [TODO] Pass this and link it to the entry in the session for pulling org + DB, etc.
//		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$_SESSION['timetracking_origin'] = $origin;
		
		$this->_startTimer();
	}
	
	function pauseTimerAction() {
		$total = $this->_stopTimer();
	}
	
	function getStopTimerPanelAction() {
		$total_secs = $this->_stopTimer();
		$this->_destroyTimer();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";
		
		// Time
		$tpl->assign('total_secs', $total_secs);
		$tpl->assign('total_mins', ceil($total_secs/60));
		
		// Work log automation
//		@$worklog = $_SESSION['timetracking_worklog'];
		
//		$str_worklog = '';
//		if(!empty($worklog))
//			$str_worklog  = implode("\r\n\r\n", $worklog) . "\r\n\r\n";
//			
//		unset($_SESSION['timetracking_worklog']);
//		$tpl->assign('worklog', $str_worklog);
		
		@$origin = $_SESSION['timetracking_origin'];
		$tpl->assign('origin', $origin."\r\n");
		
		$tpl->display('file:' . $tpl_path . 'timetracking/rpc/time_entry_panel.tpl.php');
	}
	
//	function writeResponse(DevblocksHttpResponse $response) {
//		if(!$this->isVisible())
//			return;
//	}
};

?>