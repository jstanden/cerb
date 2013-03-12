<?php
class UmScAjaxController extends Extension_UmScController {
	function __construct($manifest=null) {
		parent::__construct($manifest);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$umsession = ChPortalHelper::getSession();
		
		@$active_contact = $umsession->getProperty('sc_login',null);
		$tpl->assign('active_contact', $active_contact);

		// Usermeet Session
		if(null == ($fingerprint = ChPortalHelper::getFingerprint())) {
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
	
	function portalAction(DevblocksHttpRequest $request) {
		$stack = $request->path;
		@$uri = array_shift($stack);
		if (empty($uri))
			return;
		$registry = DevblocksPlatform::getExtensionRegistry();
		$controller = NULL;
		foreach ($registry as $controller_mft) {
			if ($controller_mft->point !== 'usermeet.sc.controller' || empty($controller_mft->params['uri']))
				continue;
			if ($controller_mft->params['uri'] == $uri) {
				$controller = $controller_mft->createInstance();
				break;
			}
		}
		if (NULL !== $controller) {
			array_unshift($stack, $uri);
			$controller->writeResponse(new DevblocksHttpResponse($stack));
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
			$tpl->display('devblocks:cerberusweb.support_center:portal_'.ChPortalHelper::getCode().':support_center/internal/view/view_filters.tpl');
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
			$tpl->display('devblocks:cerberusweb.support_center:portal_'.ChPortalHelper::getCode().':support_center/internal/view/view_filters.tpl');
		}
		
		exit;
	}
	
	function downloadFileAction(DevblocksHttpRequest $request) {
		$umsession = ChPortalHelper::getSession();
		$stack = $request->path;
		
		// Attachment ID + display name
		@$guid = array_shift($stack);
		@$display_name = array_shift($stack);
		
		if(empty($guid) || empty($display_name))
			return;

		// Attachment link
		if(null == ($link = DAO_AttachmentLink::getByGUID($guid)))
			return;

		switch($link->context) {
			case CerberusContexts::CONTEXT_MESSAGE:
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
				
				// Message
				if(null == ($message = DAO_Message::get($link->context_id)))
					return;
		
				// Requesters
				if(null == ($requesters = DAO_Ticket::getRequestersByTicket($message->ticket_id)))
					return;
		
				// Security: Make sure the active user is a requester on the proper ticket
				$authorized_addresses = array_intersect(array_keys($requesters), array_keys($addresses));
				if(!is_array($authorized_addresses) || 0 == count($authorized_addresses))
					return;
				
				break;
				
			case CerberusContexts::CONTEXT_KB_ARTICLE:
				// Allow
				break;
				
			default:
				return;
				break;
		}

		$attachment = $link->getAttachment();
		$contents = $attachment->getFileContents();
			
		// Set headers
		header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Accept-Ranges: bytes");
//		header("Keep-Alive: timeout=5, max=100");
//		header("Connection: Keep-Alive");
		header("Content-Type: " . $attachment->mime_type);
		header("Content-Length: " . strlen($contents));
		
		// Dump contents
		echo $contents;
		unset($contents);
		exit;
	}
};