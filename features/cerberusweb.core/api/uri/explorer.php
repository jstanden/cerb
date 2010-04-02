<?php
class ChExplorerController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
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
		$worker = CerberusApplication::getActiveWorker();
		if(empty($worker)) return;
		
		$stack = $request->path;
		array_shift($stack); // explorer
		
	    @$action = array_shift($stack) . 'Action';

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
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(dirname(dirname(__FILE__))) . '/templates/';

		$stack = $response->path;
		array_shift($stack); // explorer
		$hashset = array_shift($stack); // set
		@$p = array_shift($stack); // item

		if(empty($p))
			$p = 1;
		
		$items = DAO_ExplorerSet::get($hashset, array(0, $p));
		$total = 0;
		
		$tpl->assign('hashset', $hashset);		
		
		if(isset($items['0'])) {
			$meta = $items['0'];
			$total = $meta->params['total'];
			$title = isset($meta->params['title']) ? $meta->params['title'] : '';
			$return_url = isset($meta->params['return_url']) ? $meta->params['return_url'] : '';
//			$created = $meta->params['created'];
//			$worker_id = $meta->params['worker_id'];
			
			$tpl->assign('title', $title);
			$tpl->assign('count', $total);
			$tpl->assign('return_url', $return_url);
			
			// Toolbar
			if(isset($meta->params['toolbar_extension_id'])) {
				$toolbar_extension_id = $meta->params['toolbar_extension_id'];
				if(!empty($toolbar_extension_id) && null !== ($toolbar_extension = DevblocksPlatform::getExtension($toolbar_extension_id, true))) {
					$tpl->assign('toolbar_extension', $toolbar_extension);
				}
			}
			
			// 20% of the time, update our timestamp
			if(3==mt_rand(1, 5)) {
				$meta->params['last_accessed'] = time();
				DAO_ExplorerSet::update($hashset, $meta->params);
			}
		}
		
		if(isset($items[$p])) {
			$item = $items[$p];
			$tpl->assign('item', $item);
			
			$tpl->assign('p', $p);
			$tpl->assign('url', $item->params['url']);
			
			// Next
			if($total > $p)
				$tpl->assign('next', $p+1);
				
			// Prev
			if($p > 1)
				$tpl->assign('prev', $p-1);
		}
		
		// Common scope
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
			
		$tpl->display('file:'.$path.'explorer/index.tpl');
	}
};

