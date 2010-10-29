<?php
class UmScAjaxController extends Extension_UmScController {
	function __construct($manifest=null) {
		parent::__construct($manifest);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$umsession = UmPortalHelper::getSession();
		
        @$active_contact = $umsession->getProperty('sc_login',null);
        $tpl->assign('active_contact', $active_contact);

		// Usermeet Session
		if(null == ($fingerprint = UmPortalHelper::getFingerprint())) {
			die("A problem occurred.");
		}
        $tpl->assign('fingerprint', $fingerprint);
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		@$path = $request->path;
		@$a = DevblocksPlatform::importGPC($_REQUEST['a'],'string');
	    
		if(empty($a)) {
    	    @$action = array_shift($path) . 'Action';
		} else {
	    	@$action = $a . 'Action';
		}
		
	    switch($action) {
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array($this, $action), new DevblocksHttpRequest($path)); // Pass HttpRequest as arg
				}
	            break;
	    }
	}
	
	function viewRefreshAction(DevblocksHttpRequest $request) {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		
		if(null != ($view = UmScAbstractViewLoader::getView('', $view_id))) {
			$view->render();
		}
	}

	function viewPageAction(DevblocksHttpRequest $request) {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		@$page = DevblocksPlatform::importGPC($_REQUEST['page'],'integer',0);
		
		if(null != ($view = UmScAbstractViewLoader::getView('', $view_id))) {
			$view->renderPage = $page;
			UmScAbstractViewLoader::setView($view->id, $view);
			
			$view->render();
		}
	}
	
	function viewSortByAction(DevblocksHttpRequest $request) {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		@$sort_by = DevblocksPlatform::importGPC($_REQUEST['sort_by'],'string','');
		
		if(null != ($view = UmScAbstractViewLoader::getView('', $view_id))) {
			$fields = $view->getColumnsAvailable();
			if(isset($fields[$sort_by])) {
				if(0==strcasecmp($view->renderSortBy,$sort_by)) { // clicked same col?
					$view->renderSortAsc = !(bool)$view->renderSortAsc; // flip order
				} else {
					$view->renderSortBy = $sort_by;
					$view->renderSortAsc = true;
				}
				
				$view->renderPage = 0;
				
				UmScAbstractViewLoader::setView($view->id, $view);
			}
			
			$view->render();
		}
	}
	
	function viewFilterAddAction(DevblocksHttpRequest $request) {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'string','');
		@$oper = DevblocksPlatform::importGPC($_REQUEST['oper'],'string','');
		@$value = DevblocksPlatform::importGPC($_REQUEST['value'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null != ($view = UmScAbstractViewLoader::getView('', $view_id))) {
			$view->doSetCriteria($field, $oper, $value);
			UmScAbstractViewLoader::setView($view->id, $view);
			
			$tpl->assign('view', $view);
			$tpl->assign('reload_view', true);
			$tpl->display('devblocks:cerberusweb.support_center::support_center/internal/view/view_filters.tpl');
		}
		
		exit;
	}
	
	function viewFilterGetAction(DevblocksHttpRequest $request) {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null != ($view = UmScAbstractViewLoader::getView('', $view_id))) {
			$view->renderCriteria($field);
			//UmScAbstractViewLoader::setView($view->id, $view);
		}
		
		exit;
	}
	
	function viewFiltersDoAction(DevblocksHttpRequest $request) {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		@$do = DevblocksPlatform::importGPC($_REQUEST['do'],'string','');
		@$filters = DevblocksPlatform::importGPC($_REQUEST['filters'],'array',array());
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null != ($view = UmScAbstractViewLoader::getView('', $view_id))) {
			switch($do) {
				case 'remove':
					foreach($filters as $filter_key) {
						$view->doRemoveCriteria($filter_key);
					}
					UmScAbstractViewLoader::setView($view->id, $view);
					break;
					
				case 'reset':
					$view->doResetCriteria();
					UmScAbstractViewLoader::setView($view->id, $view);
					break;
			}
			
			$tpl->assign('view', $view);
			$tpl->assign('reload_view', true);
			$tpl->display('devblocks:cerberusweb.support_center::support_center/internal/view/view_filters.tpl');
		}
		
		exit;
	}	
	
	function downloadFileAction(DevblocksHttpRequest $request) {
		$umsession = UmPortalHelper::getSession();
		$stack = $request->path;
		
        if(null == ($active_contact = $umsession->getProperty('sc_login',null)))
			return;

		// [TODO] API/model ::getAddresses()
		$addresses = array();
		if(!empty($active_contact) && !empty($active_contact->id)) {
			$addresses = DAO_Address::getWhere(sprintf("%s = %d",
				DAO_Address::CONTACT_PERSON_ID,
				$active_contact->id
			));
		}
			
		// Attachment ID + display name
		@$ticket_mask = array_shift($stack);
		@$hash = array_shift($stack);
		@$display_name = array_shift($stack);
		
		if(empty($ticket_mask) || empty($hash) || empty($display_name))
			return;
			
		if(null == ($ticket_id = DAO_Ticket::getTicketIdByMask($ticket_mask)))
			return;
		
		// Load attachments by ticket mask
		list($attachments) = DAO_Attachment::search(
			array(
				SearchFields_Attachment::TICKET_MASK => new DevblocksSearchCriteria(SearchFields_Attachment::TICKET_MASK,'=',$ticket_mask), 
			),
			-1,
			0,
			null,
			null,
			false
		);

		$attachment = null;

		if(is_array($attachments))
		foreach($attachments as $possible_file) {
			// Compare the hash
			$fingerprint = md5($possible_file[SearchFields_Attachment::ID].$possible_file[SearchFields_Attachment::MESSAGE_ID].$possible_file[SearchFields_Attachment::DISPLAY_NAME]);
			if(0 == strcmp($fingerprint,$hash)) {
				if(null == ($attachment = DAO_Attachment::get($possible_file[SearchFields_Attachment::ID])))
					return;
				break;
			}
		}

		// No hit (bad hash)
		if(null == $attachment)
			return;

		// Load requesters		
		if(null == ($requesters = DAO_Ticket::getRequestersByTicket($ticket_id)))
			return;

		$authorized_addresses = array_intersect(array_keys($requesters), array_keys($addresses));
		
		// Security: Make sure the active user is a requester on the proper ticket
		if(!is_array($authorized_addresses) || 0 == count($authorized_addresses))
			return;
		
		$contents = $attachment->getFileContents();
			
		// Set headers
		header("Expires: Mon, 26 Nov 1962 00:00:00 GMT\n");
		header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT\n");
		header("Cache-control: private\n");
		header("Pragma: no-cache\n");
		header("Content-Type: " . $attachment->mime_type . "\n");
		header("Content-transfer-encoding: binary\n"); 
		header("Content-Length: " . strlen($contents) . "\n");
		
		// Dump contents
		echo $contents;
		unset($contents);
		exit;
	}
};